<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'pendingCount' => Appointment::where('status', Appointment::STATUS_PENDING)
                ->where('starts_at', '>=', now())->count(),
            'todayCount' => Appointment::blocking()->whereDate('starts_at', today())->count(),
            'upcoming' => Appointment::blocking()
                ->with('product')
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
