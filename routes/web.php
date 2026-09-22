<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Agendamento público, numa página só: produto, dia, horário e o formulário de dados.
// Sem cadastro nem login de cliente — login existe só para o painel em /admin.
Route::get('/', [BookingController::class, 'index'])->name('home');
Route::post('/agendar', [BookingController::class, 'store'])->name('booking.store');

// Preenche nome, e-mail e contador de quem já agendou antes com o mesmo CPF/CNPJ.
// Limitado por IP para não virar uma forma de descobrir dados de terceiros.
Route::post('/agendar/consulta-documento', [BookingController::class, 'lookupByDocument'])
    ->middleware('throttle:20,1')
    ->name('booking.lookup');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', Admin\ProductController::class)->except('show');

    Route::get('availability', [Admin\AvailabilityRuleController::class, 'index'])->name('availability.index');
    Route::post('availability', [Admin\AvailabilityRuleController::class, 'store'])->name('availability.store');
    Route::delete('availability/{availabilityRule}', [Admin\AvailabilityRuleController::class, 'destroy'])->name('availability.destroy');

    Route::get('blocked', [Admin\BlockedPeriodController::class, 'index'])->name('blocked.index');
    Route::post('blocked', [Admin\BlockedPeriodController::class, 'store'])->name('blocked.store');
    Route::delete('blocked/{blockedPeriod}', [Admin\BlockedPeriodController::class, 'destroy'])->name('blocked.destroy');

    Route::get('appointments', [Admin\AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/{appointment}', [Admin\AppointmentController::class, 'show'])->name('appointments.show');
    Route::get('appointments/{appointment}/documento', [Admin\AppointmentController::class, 'downloadDocument'])->name('appointments.document');
    Route::patch('appointments/{appointment}', [Admin\AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('appointments/{appointment}/reagendar', [Admin\AppointmentController::class, 'reschedule'])->name('appointments.reschedule');

    Route::resource('users', Admin\AdminUserController::class)->except('show');
});

require __DIR__.'/auth.php';
