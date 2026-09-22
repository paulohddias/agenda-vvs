<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AvailabilityRuleController extends Controller
{
    public function index(): View
    {
        $rules = Staff::primary()->availabilityRules()
            ->orderBy('weekday')->orderBy('start_time')->get()
            ->groupBy('weekday');

        return view('admin.availability.index', ['rulesByDay' => $rules]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'weekday' => ['required', 'integer', Rule::in(array_keys(AvailabilityRule::WEEKDAYS))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $staff = Staff::primary();

        $overlaps = $staff->availabilityRules()
            ->where('weekday', $data['weekday'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->exists();

        if ($overlaps) {
            return back()->withInput()->withErrors(['start_time' => 'Esse intervalo se sobrepõe a outro já cadastrado no mesmo dia.']);
        }

        $staff->availabilityRules()->create($data);

        return back()->with('status', 'Horário adicionado.');
    }

    public function destroy(AvailabilityRule $availabilityRule): RedirectResponse
    {
        $availabilityRule->delete();

        return back()->with('status', 'Horário removido.');
    }
}
