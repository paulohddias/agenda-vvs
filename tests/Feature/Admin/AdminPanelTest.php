<?php

namespace Tests\Feature\Admin;

use App\Mail\AppointmentChanged;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customers_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create();

        foreach (['admin.dashboard', 'admin.products.index', 'admin.availability.index', 'admin.blocked.index', 'admin.appointments.index', 'admin.users.index'] as $route) {
            $this->actingAs($customer)->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_sees_every_admin_page(): void
    {
        Staff::create(['name' => 'Atendimento']);
        $admin = $this->admin();

        foreach (['admin.dashboard', 'admin.products.index', 'admin.products.create', 'admin.availability.index', 'admin.blocked.index', 'admin.appointments.index', 'admin.users.index', 'admin.users.create'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_admin_can_create_edit_and_delete_another_admin_user(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Novo Admin', 'email' => 'novo@agenda.local',
            'password' => 'senha-forte-123', 'password_confirmation' => 'senha-forte-123',
        ])->assertRedirect(route('admin.users.index'));

        $newUser = User::firstWhere('email', 'novo@agenda.local');
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->isAdmin());
        $this->assertTrue(Hash::check('senha-forte-123', $newUser->password));

        $this->actingAs($admin)->put(route('admin.users.update', $newUser), [
            'name' => 'Novo Admin Editado', 'email' => 'novo@agenda.local',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertSame('Novo Admin Editado', $newUser->refresh()->name);
        $this->assertTrue(Hash::check('senha-forte-123', $newUser->password), 'senha não deveria mudar quando deixada em branco');

        $this->actingAs($admin)->delete(route('admin.users.destroy', $newUser));
        $this->assertModelMissing($newUser);
    }

    public function test_admin_cannot_delete_themselves_or_the_last_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error');
        $this->assertModelExists($admin);

        $second = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $second));
        $this->assertModelMissing($second);

        // agora só sobrou $admin: mesmo outro admin tentando, não pode zerar a lista
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
    }

    public function test_new_admin_user_requires_unique_email_and_matching_password_confirmation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'X', 'email' => $admin->email, 'password' => 'senha-forte-123', 'password_confirmation' => 'senha-forte-123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'X', 'email' => 'outro@agenda.local', 'password' => 'senha-forte-123', 'password_confirmation' => 'nao-bate',
        ])->assertSessionHasErrors('password');
    }

    public function test_admin_is_redirected_to_panel_after_login(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_admin_can_create_update_and_delete_a_product(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Corte', 'duration_minutes' => 45, 'price' => '50.00', 'active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::firstWhere('name', 'Corte');
        $this->assertSame(45, $product->duration_minutes);
        $this->assertTrue($product->active);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Corte longo', 'duration_minutes' => 60,
        ])->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('Corte longo', $product->name);
        $this->assertFalse($product->active, 'checkbox desmarcado deve desativar o produto');

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));
        $this->assertModelMissing($product);
    }

    public function test_product_with_appointments_cannot_be_deleted(): void
    {
        $staff = Staff::create(['name' => 'Atendimento']);
        $product = Product::create(['name' => 'Corte', 'duration_minutes' => 30]);
        Appointment::create([
            'product_id' => $product->id, 'staff_id' => $staff->id,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30),
            'holder_name' => 'Cliente Teste',
        ]);

        $this->actingAs($this->admin())->delete(route('admin.products.destroy', $product))
            ->assertSessionHas('error');

        $this->assertModelExists($product);
    }

    public function test_product_validation_rejects_bad_duration(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'X', 'duration_minutes' => 0,
        ])->assertSessionHasErrors('duration_minutes');
    }

    public function test_availability_rule_is_created_and_overlaps_are_rejected(): void
    {
        $staff = Staff::create(['name' => 'Atendimento']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.availability.store'), [
            'weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00',
        ])->assertSessionHasNoErrors();

        // adjacente (12:00 termina onde a outra começa) é permitido
        $this->actingAs($admin)->post(route('admin.availability.store'), [
            'weekday' => 1, 'start_time' => '12:00', 'end_time' => '18:00',
        ])->assertSessionHasNoErrors();

        // sobrepõe
        $this->actingAs($admin)->post(route('admin.availability.store'), [
            'weekday' => 1, 'start_time' => '11:00', 'end_time' => '13:00',
        ])->assertSessionHasErrors('start_time');

        // outro dia não conflita
        $this->actingAs($admin)->post(route('admin.availability.store'), [
            'weekday' => 2, 'start_time' => '11:00', 'end_time' => '13:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, $staff->availabilityRules()->count());
    }

    public function test_availability_end_must_be_after_start(): void
    {
        Staff::create(['name' => 'Atendimento']);

        $this->actingAs($this->admin())->post(route('admin.availability.store'), [
            'weekday' => 1, 'start_time' => '18:00', 'end_time' => '09:00',
        ])->assertSessionHasErrors('end_time');
    }

    public function test_full_day_blocked_period_can_be_created_and_removed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.blocked.store'), [
            'day_mode' => 'full', 'date_start' => '2030-12-25', 'reason' => 'Natal',
        ])->assertSessionHasNoErrors();

        $period = BlockedPeriod::firstOrFail();
        $this->assertNull($period->staff_id);
        $this->assertTrue($period->isFullDay());
        $this->assertSame('2030-12-25 00:00:00', $period->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2030-12-25 23:59:59', $period->ends_at->format('Y-m-d H:i:s'));

        $this->actingAs($admin)->delete(route('admin.blocked.destroy', $period));
        $this->assertModelMissing($period);
    }

    public function test_full_day_blocked_period_can_span_multiple_days(): void
    {
        $this->actingAs($this->admin())->post(route('admin.blocked.store'), [
            'day_mode' => 'full', 'date_start' => '2030-12-24', 'date_end' => '2030-12-26',
        ])->assertSessionHasNoErrors();

        $period = BlockedPeriod::firstOrFail();
        $this->assertSame('2030-12-24 00:00:00', $period->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2030-12-26 23:59:59', $period->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_partial_blocked_period_uses_the_given_time_range(): void
    {
        $this->actingAs($this->admin())->post(route('admin.blocked.store'), [
            'day_mode' => 'partial', 'date' => '2030-12-25', 'time_start' => '12:00', 'time_end' => '13:00', 'reason' => 'Almoço',
        ])->assertSessionHasNoErrors();

        $period = BlockedPeriod::firstOrFail();
        $this->assertFalse($period->isFullDay());
        $this->assertSame('2030-12-25 12:00:00', $period->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2030-12-25 13:00:00', $period->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_partial_blocked_period_end_must_be_after_start(): void
    {
        $this->actingAs($this->admin())->post(route('admin.blocked.store'), [
            'day_mode' => 'partial', 'date' => '2030-12-25', 'time_start' => '13:00', 'time_end' => '12:00',
        ])->assertSessionHasErrors('time_end');
    }

    public function test_admin_can_change_appointment_status_and_filter_the_list(): void
    {
        $staff = Staff::create(['name' => 'Atendimento']);
        $product = Product::create(['name' => 'Corte', 'duration_minutes' => 30]);
        $day = now()->addDay();
        $appointment = Appointment::create([
            'product_id' => $product->id, 'staff_id' => $staff->id,
            'starts_at' => $day->copy(), 'ends_at' => $day->copy()->addMinutes(30),
            'holder_name' => 'Cliente Teste',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), ['status' => 'confirmed'])
            ->assertSessionHas('status');
        $this->assertSame('confirmed', $appointment->refresh()->status);

        $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), ['status' => 'invalido'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)->get(route('admin.appointments.index', ['day' => $day->toDateString(), 'status' => 'confirmed']))
            ->assertOk()->assertSee('Cliente Teste');
        $this->actingAs($admin)->get(route('admin.appointments.index', ['day' => $day->toDateString(), 'status' => 'cancelled']))
            ->assertOk()->assertDontSee('Cliente Teste');
    }

    public function test_customer_is_emailed_when_admin_confirms_cancels_or_reschedules(): void
    {
        Mail::fake();
        $staff = Staff::create(['name' => 'Atendimento']);
        $newDay = now()->addDays(2);
        $staff->availabilityRules()->create(['weekday' => $newDay->dayOfWeek, 'start_time' => '09:00', 'end_time' => '18:00']);
        $product = Product::create(['name' => 'e-CNPJ A1', 'duration_minutes' => 30]);
        $appointment = Appointment::create([
            'product_id' => $product->id, 'staff_id' => $staff->id,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_PENDING,
            'holder_name' => 'Cliente Teste', 'holder_email' => 'cliente@example.com',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), ['status' => 'confirmed']);
        Mail::assertSent(AppointmentChanged::class, fn ($mail) => $mail->hasTo('cliente@example.com')
            && $mail->change === AppointmentChanged::CONFIRMED);

        $this->actingAs($admin)->patch(route('admin.appointments.reschedule', $appointment), [
            'reschedule_date' => $newDay->toDateString(), 'reschedule_time' => '10:00',
        ]);
        Mail::assertSent(AppointmentChanged::class, function ($mail) use ($newDay) {
            $html = $mail->render();

            return $mail->change === AppointmentChanged::RESCHEDULED
                && str_contains($html, 'Horário anterior')
                && str_contains($html, $newDay->format('d/m/Y'));
        });

        $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), ['status' => 'cancelled']);
        Mail::assertSent(AppointmentChanged::class, fn ($mail) => $mail->change === AppointmentChanged::CANCELLED
            && str_contains($mail->render(), 'Cancelado'));

        Mail::assertSentCount(3);
    }

    public function test_customer_is_not_emailed_when_appointment_is_completed_or_status_is_unchanged(): void
    {
        Mail::fake();
        $staff = Staff::create(['name' => 'Atendimento']);
        $product = Product::create(['name' => 'Corte', 'duration_minutes' => 30]);
        $appointment = Appointment::create([
            'product_id' => $product->id, 'staff_id' => $staff->id,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED,
            'holder_name' => 'Cliente Teste', 'holder_email' => 'cliente@example.com',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), ['status' => 'confirmed']);
        $this->actingAs($admin)->patch(route('admin.appointments.update', $appointment), ['status' => 'completed']);

        Mail::assertNothingSent();
    }

    public function test_admin_can_view_the_agenda_as_day_week_or_month(): void
    {
        $admin = $this->admin();
        Staff::create(['name' => 'Atendimento']);

        foreach (['day', 'week', 'month'] as $view) {
            $this->actingAs($admin)->get(route('admin.appointments.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_reschedule_an_appointment_to_a_free_slot(): void
    {
        $staff = Staff::create(['name' => 'Atendimento']);
        $staff->availabilityRules()->create(['weekday' => now()->addDays(2)->dayOfWeek, 'start_time' => '09:00', 'end_time' => '18:00']);
        $product = Product::create(['name' => 'Corte', 'duration_minutes' => 30]);
        $appointment = Appointment::create([
            'product_id' => $product->id, 'staff_id' => $staff->id,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30),
            'holder_name' => 'Cliente Teste',
        ]);

        $newDay = now()->addDays(2);
        $this->actingAs($this->admin())->patch(route('admin.appointments.reschedule', $appointment), [
            'reschedule_date' => $newDay->toDateString(), 'reschedule_time' => '10:00',
        ])->assertSessionHas('status');

        $appointment->refresh();
        $this->assertSame($newDay->toDateString().' 10:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame($newDay->toDateString().' 10:30:00', $appointment->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_cannot_reschedule_an_appointment_onto_a_taken_slot(): void
    {
        $staff = Staff::create(['name' => 'Atendimento']);
        $product = Product::create(['name' => 'Corte', 'duration_minutes' => 30]);
        $day = now()->addDay()->setTime(10, 0);
        $staff->availabilityRules()->create(['weekday' => $day->dayOfWeek, 'start_time' => '09:00', 'end_time' => '18:00']);

        $taken = Appointment::create([
            'product_id' => $product->id, 'staff_id' => $staff->id,
            'starts_at' => $day->copy(), 'ends_at' => $day->copy()->addMinutes(30),
            'holder_name' => 'Já Marcado',
        ]);
        $toMove = Appointment::create([
            'product_id' => $product->id, 'staff_id' => $staff->id,
            'starts_at' => $day->copy()->addHour(), 'ends_at' => $day->copy()->addHour()->addMinutes(30),
            'holder_name' => 'Vou Mover',
        ]);

        $this->actingAs($this->admin())->patch(route('admin.appointments.reschedule', $toMove), [
            'reschedule_date' => $day->toDateString(), 'reschedule_time' => '10:00',
        ])->assertSessionHas('error');

        // 10:00 já está ocupado pelo $taken: o agendamento não deve ter se movido do horário original (11:00).
        $this->assertSame('11:00:00', $toMove->refresh()->starts_at->format('H:i:s'));
    }

    public function test_admin_can_view_and_update_agenda_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'min_notice_minutes' => 5, 'slot_step_minutes' => 15, 'max_days_ahead' => 10, 'cancel_min_hours' => 1,
        ])->assertSessionHas('status');

        $this->assertSame('5', \App\Models\Setting::allCached()['min_notice_minutes']);
        $this->assertSame('15', \App\Models\Setting::allCached()['slot_step_minutes']);
    }

    public function test_agenda_settings_validation_rejects_out_of_range_values(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'min_notice_minutes' => -1, 'slot_step_minutes' => 1, 'max_days_ahead' => 0, 'cancel_min_hours' => 999,
        ])->assertSessionHasErrors(['min_notice_minutes', 'slot_step_minutes', 'max_days_ahead', 'cancel_min_hours']);
    }

    public function test_saved_setting_overrides_the_config_default(): void
    {
        \App\Models\Setting::set('min_notice_minutes', '5');

        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertSame(5, config('agenda.min_notice_minutes'));
    }
}
