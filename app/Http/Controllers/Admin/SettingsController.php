<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'minNoticeMinutes' => config('agenda.min_notice_minutes'),
            'slotStepMinutes' => config('agenda.slot_step_minutes'),
            'maxDaysAhead' => config('agenda.max_days_ahead'),
            'cancelMinHours' => config('agenda.cancel_min_hours'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'min_notice_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'slot_step_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'max_days_ahead' => ['required', 'integer', 'min:1', 'max:365'],
            'cancel_min_hours' => ['required', 'integer', 'min:0', 'max:168'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        return back()->with('status', 'Configurações salvas.');
    }
}
