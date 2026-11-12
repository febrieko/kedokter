<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    protected function getClinic(Request $request): Clinic
    {
        return $request->user()->clinic ?? Clinic::firstOrFail();
    }

    public function index(Request $request): View
    {
        $clinic = $this->getClinic($request);
        $doctors = $clinic->doctors()->where('clinic_doctors.is_active', true)->get();

        $holidays = Holiday::with('doctor')
            ->where('clinic_id', $clinic->id)
            ->where('holiday_date', '>=', Carbon::today())
            ->orderBy('holiday_date')
            ->get();

        return view('admin.holidays.index', compact('clinic', 'doctors', 'holidays'));
    }

    public function store(Request $request): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        $validated = $request->validate([
            'doctor_id' => ['nullable', 'uuid'],
            'holiday_date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        Holiday::create([
            'clinic_id' => $clinic->id,
            'doctor_id' => $validated['doctor_id'] ?? null,
            'holiday_date' => $validated['holiday_date'],
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Hari libur berhasil dicatat.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();
        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}
