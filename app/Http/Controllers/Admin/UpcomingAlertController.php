<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;

/**
 * Atendimentos que começam dentro do tempo de aviso (Configurações → alert_minutes_before).
 * O painel consulta isso periodicamente e mostra o alerta; quem já foi avisado fica
 * guardado no navegador, então aqui não precisa marcar nada no banco.
 */
class UpcomingAlertController extends Controller
{
    public function index(): JsonResponse
    {
        $minutes = (int) config('agenda.alert_minutes_before');

        if ($minutes <= 0) {
            return response()->json(['alerts' => []]);
        }

        $now = now();

        $alerts = Appointment::blocking()
            ->with('product')
            // 1 minuto de folga para trás: um aviso que caiu entre duas consultas ainda aparece.
            ->whereBetween('starts_at', [$now->copy()->subMinute(), $now->copy()->addMinutes($minutes)])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $a) => [
                // Inclui o horário: se o agendamento for reagendado, avisa de novo no horário novo.
                'key' => $a->id.'@'.$a->starts_at->format('Y-m-d H:i'),
                'time' => $a->starts_at->format('H:i'),
                'minutesLeft' => max(0, (int) ceil($now->diffInSeconds($a->starts_at, false) / 60)),
                'holderName' => $a->holder_name,
                'product' => $a->product->name,
                'validationMethod' => $a->validationMethodLabel(),
                'url' => route('admin.appointments.index', ['view' => 'day', 'day' => $a->starts_at->toDateString()]),
            ]);

        return response()->json(['alerts' => $alerts]);
    }
}
