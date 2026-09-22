<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlockedPeriodController extends Controller
{
    public function index(): View
    {
        return view('admin.blocked.index', [
            'periods' => BlockedPeriod::where('ends_at', '>=', now())->orderBy('starts_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day_mode' => ['required', Rule::in(['full', 'partial'])],
            'date_start' => ['required_if:day_mode,full', 'nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'date' => ['required_if:day_mode,partial', 'nullable', 'date'],
            'time_start' => ['required_if:day_mode,partial', 'nullable', 'date_format:H:i'],
            'time_end' => ['required_if:day_mode,partial', 'nullable', 'date_format:H:i', 'after:time_start'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['day_mode'] === 'full') {
            $starts = Carbon::parse($data['date_start'])->startOfDay();
            $ends = Carbon::parse($data['date_end'] ?? $data['date_start'])->endOfDay();
        } else {
            $starts = Carbon::parse($data['date'].' '.$data['time_start']);
            $ends = Carbon::parse($data['date'].' '.$data['time_end']);
        }

        // staff_id nulo = bloqueio geral, vale para qualquer atendente.
        BlockedPeriod::create([
            'starts_at' => $starts,
            'ends_at' => $ends,
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'Bloqueio adicionado.');
    }

    public function destroy(BlockedPeriod $blockedPeriod): RedirectResponse
    {
        $blockedPeriod->delete();

        return back()->with('status', 'Bloqueio removido.');
    }
}
