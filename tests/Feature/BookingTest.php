<?php

namespace Tests\Feature;

use App\Mail\AppointmentRequested;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\Product;
use App\Models\Staff;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private Product $product;
    private Carbon $mondayDate;

    protected function setUp(): void
    {
        parent::setUp();

        // Uma próxima segunda-feira fixa, para os testes não dependerem do dia em que rodam
        // nem do "agora" de teste mudar durante o próprio teste (ver monday() abaixo).
        $this->mondayDate = Carbon::parse('next monday');
        Carbon::setTestNow($this->mondayDate->copy()->setTimeFromTimeString('08:00'));

        $this->staff = Staff::create(['name' => 'Atendimento']);
        $this->staff->availabilityRules()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '11:00']);
        $this->product = Product::create(['name' => 'Corte', 'duration_minutes' => 30]);
    }

    private function monday(string $time): Carbon
    {
        return $this->mondayDate->copy()->setTimeFromTimeString($time);
    }

    /** Payload válido do formulário de agendamento, para os testes que não focam na validação dos dados. */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->product->id,
            'holder_name' => 'Maria Souza',
            'holder_document' => '111.444.777-35', // CPF válido (dígito verificador correto)
            'holder_email' => 'maria@example.com',
            'holder_phone' => '(12) 91234-5678',
            'validation_method' => Appointment::VALIDATION_PRESENCIAL,
            'terms' => '1',
        ], $overrides);
    }

    public function test_available_slots_respect_duration_and_step(): void
    {
        $slots = app(AvailabilityService::class)->slots($this->product, $this->staff, today(), today()->addWeek());
        $monday = $this->monday('09:00')->toDateString();

        $this->assertEquals(['09:00', '09:30', '10:00', '10:30'], $slots[$monday]->map->format('H:i')->all());
    }

    public function test_existing_appointment_removes_its_slot(): void
    {
        Appointment::create([
            'product_id' => $this->product->id, 'staff_id' => $this->staff->id,
            'starts_at' => $this->monday('09:30'), 'ends_at' => $this->monday('10:00'),
        ]);

        $slots = app(AvailabilityService::class)->slots($this->product, $this->staff, today(), today()->addWeek());

        $this->assertEquals(['09:00', '10:00', '10:30'], $slots[$this->monday('09:00')->toDateString()]->map->format('H:i')->all());
    }

    public function test_cancelled_appointment_frees_its_slot(): void
    {
        Appointment::create([
            'product_id' => $this->product->id, 'staff_id' => $this->staff->id,
            'starts_at' => $this->monday('09:30'), 'ends_at' => $this->monday('10:00'), 'status' => Appointment::STATUS_CANCELLED,
        ]);

        $slots = app(AvailabilityService::class)->slots($this->product, $this->staff, today(), today()->addWeek());

        $this->assertContains('09:30', $slots[$this->monday('09:00')->toDateString()]->map->format('H:i')->all());
    }

    public function test_blocked_period_removes_overlapping_slots(): void
    {
        BlockedPeriod::create(['starts_at' => $this->monday('09:45'), 'ends_at' => $this->monday('10:15')]);

        $slots = app(AvailabilityService::class)->slots($this->product, $this->staff, today(), today()->addWeek());

        $this->assertEquals(['09:00', '10:30'], $slots[$this->monday('09:00')->toDateString()]->map->format('H:i')->all());
    }

    public function test_slots_before_the_minimum_notice_are_excluded(): void
    {
        Carbon::setTestNow($this->monday('09:20'));

        $slots = app(AvailabilityService::class)->slots($this->product, $this->staff, today(), today()->addWeek());

        // min_notice_minutes = 10 por padrão: às 09:20, 09:30 fica exatamente nos 10 minutos (não vale, é estrito).
        $this->assertEquals(['10:00', '10:30'], $slots[$this->monday('09:00')->toDateString()]->map->format('H:i')->all());
    }

    public function test_exactly_the_minimum_notice_is_not_enough(): void
    {
        // O caso do pedido original: agora 14:50, tentando marcar 15:00 — faltam exatamente 10 minutos, não pode.
        $staff = Staff::create(['name' => 'Outro atendente']);
        $staff->availabilityRules()->create(['weekday' => 1, 'start_time' => '14:00', 'end_time' => '18:00']);
        $product = Product::create(['name' => 'Corte 2', 'duration_minutes' => 30]);

        Carbon::setTestNow($this->monday('14:50'));
        $this->assertFalse(app(AvailabilityService::class)->isAvailable($product, $staff, $this->monday('15:00')));

        Carbon::setTestNow($this->monday('14:49'));
        $this->assertTrue(app(AvailabilityService::class)->isAvailable($product, $staff, $this->monday('15:00')));
    }

    public function test_guest_sees_the_booking_page_with_product_and_available_times(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Corte')->assertSee('09:00');
    }

    public function test_guest_can_book_an_available_slot_without_logging_in(): void
    {
        $start = $this->monday('09:00');

        $this->post(route('booking.store'), $this->validPayload([
            'starts_at' => $start->format('Y-m-d H:i'),
        ]))->assertRedirect(route('home'));

        $appointment = Appointment::firstOrFail();
        $this->assertNull($appointment->user_id);
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertTrue($appointment->starts_at->equalTo($start));
        $this->assertSame('Maria Souza', $appointment->holder_name);
        $this->assertSame('11144477735', $appointment->holder_document); // gravado só com dígitos
        $this->assertSame('12912345678', $appointment->holder_phone);
        $this->assertNotNull($appointment->terms_accepted_at);
    }

    public function test_customer_receives_a_confirmation_email_after_booking(): void
    {
        Mail::fake();

        $this->post(route('booking.store'), $this->validPayload([
            'starts_at' => $this->monday('09:00')->format('Y-m-d H:i'),
            'holder_email' => 'destinatario@example.com',
        ]));

        $appointment = Appointment::firstOrFail();

        Mail::assertSent(AppointmentRequested::class, function ($mail) use ($appointment) {
            return $mail->hasTo('destinatario@example.com')
                && $mail->appointment->is($appointment);
        });
    }

    public function test_booking_still_succeeds_even_if_sending_the_email_fails(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP fora do ar'));

        $this->post(route('booking.store'), $this->validPayload([
            'starts_at' => $this->monday('09:00')->format('Y-m-d H:i'),
        ]))->assertRedirect(route('home'))->assertSessionHas('status');

        $this->assertSame(1, Appointment::count());
    }

    public function test_document_must_be_a_valid_cpf_or_cnpj(): void
    {
        $this->post(route('booking.store'), $this->validPayload([
            'starts_at' => $this->monday('09:00')->format('Y-m-d H:i'),
            'holder_document' => '111.111.111-11', // sequência repetida, inválido
        ]))->assertSessionHasErrors('holder_document');

        $this->assertSame(0, Appointment::count());
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post(route('booking.store'), $this->validPayload([
            'starts_at' => $this->monday('09:00')->format('Y-m-d H:i'),
            'terms' => null,
        ]))->assertSessionHasErrors('terms');

        $this->assertSame(0, Appointment::count());
    }

    public function test_document_upload_is_optional_even_for_videoconferencia(): void
    {
        $this->post(route('booking.store'), $this->validPayload([
            'starts_at' => $this->monday('09:00')->format('Y-m-d H:i'),
            'validation_method' => Appointment::VALIDATION_VIDEOCONFERENCIA,
        ]))->assertRedirect(route('home'));

        $appointment = Appointment::firstOrFail();
        $this->assertSame(Appointment::VALIDATION_VIDEOCONFERENCIA, $appointment->validation_method);
        $this->assertNull($appointment->document_path);
    }

    public function test_cannot_book_a_product_that_is_not_active(): void
    {
        $inactive = Product::create(['name' => 'Inativo', 'duration_minutes' => 30, 'active' => false]);

        $this->post(route('booking.store'), $this->validPayload([
            'product_id' => $inactive->id,
            'starts_at' => $this->monday('09:00')->format('Y-m-d H:i'),
        ]))->assertSessionHasErrors('product_id');

        $this->assertSame(0, Appointment::count());
    }

    public function test_document_lookup_returns_data_from_the_most_recent_booking(): void
    {
        Appointment::create([
            'product_id' => $this->product->id, 'staff_id' => $this->staff->id,
            'starts_at' => now()->subDays(5), 'ends_at' => now()->subDays(5)->addMinutes(30),
            'holder_document' => '11144477735', 'holder_name' => 'Nome Antigo',
            'holder_email' => 'antigo@example.com', 'accountant_name' => 'Contador Antigo',
        ]);
        Appointment::create([
            'product_id' => $this->product->id, 'staff_id' => $this->staff->id,
            'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addMinutes(30),
            'holder_document' => '11144477735', 'holder_name' => 'Nome Recente',
            'holder_email' => 'recente@example.com', 'holder_phone' => '12988887777',
            'accountant_name' => 'Contador Recente',
        ]);

        $this->postJson(route('booking.lookup'), ['holder_document' => '111.444.777-35'])
            ->assertOk()
            ->assertJson([
                'found' => true,
                'holder_name' => 'Nome Recente',
                'holder_email' => 'recente@example.com',
                'holder_phone' => '12988887777',
                'accountant_name' => 'Contador Recente',
            ]);
    }

    public function test_document_lookup_reports_not_found_for_unknown_or_invalid_document(): void
    {
        $this->postJson(route('booking.lookup'), ['holder_document' => '111.444.777-35'])
            ->assertOk()->assertJson(['found' => false]);

        $this->postJson(route('booking.lookup'), ['holder_document' => '111.111.111-11'])
            ->assertOk()->assertJson(['found' => false]);
    }

    public function test_cannot_double_book_a_taken_slot(): void
    {
        $start = $this->monday('09:00');
        Appointment::create([
            'product_id' => $this->product->id, 'staff_id' => $this->staff->id,
            'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(30),
        ]);

        $this->post(route('booking.store'), $this->validPayload([
            'starts_at' => $start->format('Y-m-d H:i'),
        ]))->assertRedirect(route('home', ['product' => $this->product->id, 'date' => $start->toDateString()]))
            ->assertSessionHas('error');

        $this->assertSame(1, Appointment::count());
    }
}
