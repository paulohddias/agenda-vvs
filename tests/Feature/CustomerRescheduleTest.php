<?php

namespace Tests\Feature;

use App\Mail\AppointmentChanged;
use App\Mail\AppointmentRequested;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private Product $product;
    private Carbon $monday;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monday = Carbon::parse('next monday');
        Carbon::setTestNow($this->monday->copy()->setTime(8, 0));

        $this->staff = Staff::create(['name' => 'Atendimento']);
        $this->staff->availabilityRules()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '11:00']);
        $this->product = Product::create(['name' => 'e-CNPJ A1', 'duration_minutes' => 30]);

        // Segunda da semana seguinte: bem longe do prazo mínimo.
        $start = $this->monday->copy()->addWeek()->setTime(9, 0);
        $this->appointment = Appointment::create([
            'product_id' => $this->product->id, 'staff_id' => $this->staff->id,
            'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED, 'validation_method' => Appointment::VALIDATION_PRESENCIAL,
            'holder_name' => 'Cliente Teste', 'holder_email' => 'cliente@example.com', 'holder_phone' => '12912345678',
        ]);
    }

    public function test_signed_link_shows_the_calendar_with_free_times(): void
    {
        $date = $this->appointment->starts_at->toDateString();

        $this->get($this->appointment->customerRescheduleUrl().'&date='.$date)
            ->assertOk()
            ->assertSee('Reagendar atendimento')
            ->assertSee('Cliente Teste')
            ->assertSee('10:00');
    }

    public function test_link_without_a_valid_signature_is_rejected(): void
    {
        $other = $this->appointment->replicate();
        $other->save();

        // Mesma assinatura, outro agendamento na URL.
        $tampered = str_replace(
            '/agendamento/'.$this->appointment->id.'/',
            '/agendamento/'.$other->id.'/',
            $this->appointment->customerRescheduleUrl(),
        );

        $this->get($tampered)->assertForbidden();
        $this->get(route('booking.reschedule', $this->appointment))->assertForbidden();
    }

    public function test_customer_moves_the_appointment_and_receives_the_email(): void
    {
        Mail::fake();
        $newStart = $this->appointment->starts_at->copy()->setTime(10, 0);

        $this->post($this->appointment->customerRescheduleUrl(), ['starts_at' => $newStart->format('Y-m-d H:i')])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->appointment->refresh();
        $this->assertTrue($this->appointment->starts_at->equalTo($newStart));
        $this->assertTrue($this->appointment->ends_at->equalTo($newStart->copy()->addMinutes(30)));

        Mail::assertSent(AppointmentChanged::class, fn ($mail) => $mail->hasTo('cliente@example.com')
            && $mail->change === AppointmentChanged::RESCHEDULED
            && str_contains($mail->render(), 'Reagende aqui'));
    }

    public function test_customer_cannot_move_onto_a_taken_slot(): void
    {
        $taken = $this->appointment->starts_at->copy()->setTime(10, 0);
        Appointment::create([
            'product_id' => $this->product->id, 'staff_id' => $this->staff->id,
            'starts_at' => $taken, 'ends_at' => $taken->copy()->addMinutes(30),
        ]);

        $this->post($this->appointment->customerRescheduleUrl(), ['starts_at' => $taken->format('Y-m-d H:i')])
            ->assertSessionHas('error');

        $this->assertSame('09:00', $this->appointment->refresh()->starts_at->format('H:i'));
    }

    public function test_link_stops_working_close_to_the_appointment_or_when_cancelled(): void
    {
        Carbon::setTestNow($this->appointment->starts_at->copy()->subHour()); // prazo padrão: 2 horas

        $this->get($this->appointment->customerRescheduleUrl())->assertOk()->assertSee('Não dá mais para reagendar');
        $this->post($this->appointment->customerRescheduleUrl(), [
            'starts_at' => $this->appointment->starts_at->copy()->setTime(10, 0)->format('Y-m-d H:i'),
        ]);
        $this->assertSame('09:00', $this->appointment->refresh()->starts_at->format('H:i'));

        Carbon::setTestNow($this->monday->copy()->setTime(8, 0));
        $this->appointment->update(['status' => Appointment::STATUS_CANCELLED]);
        $this->get($this->appointment->customerRescheduleUrl())->assertOk()->assertSee('não pode ser reagendado');
    }

    public function test_booking_email_and_whatsapp_carry_the_reschedule_link(): void
    {
        $url = $this->appointment->customerRescheduleUrl();

        $this->assertStringContainsString(e($url), (new AppointmentRequested($this->appointment))->render());
        $this->assertStringContainsString($url, rawurldecode($this->appointment->whatsappUrl(Appointment::WHATSAPP_REMIND)));
    }
}
