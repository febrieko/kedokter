<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ScheduleOverride;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    protected function getClinic(Request $request): Clinic
    {
        return $request->user()->clinic ?? Clinic::firstOrFail();
    }

    public function index(Request $request): View
    {
        $clinic = $this->getClinic($request);
        $doctors = $clinic->doctors()->where('clinic_doctors.is_active', true)->get();

        $selectedDoctorId = $request->input('doctor_id', $doctors->first()?->id);

        $schedules = DoctorSchedule::with('doctor')
            ->where('clinic_id', $clinic->id)
            ->when($selectedDoctorId, fn($q) => $q->where('doctor_id', $selectedDoctorId))
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        // Get upcoming overrides
        $overrides = ScheduleOverride::with(['schedule.doctor', 'creator'])
            ->where('clinic_id', $clinic->id)
            ->where('override_date', '>=', Carbon::today())
            ->orderBy('override_date')
            ->get();

        return view('admin.schedules.index', compact('clinic', 'doctors', 'selectedDoctorId', 'schedules', 'overrides'));
    }

    public function store(Request $request): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        $validated = $request->validate([
            'doctor_id' => ['required', 'uuid'],
            'weekday' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'quota' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        DoctorSchedule::create([
            'clinic_id' => $clinic->id,
            'doctor_id' => $validated['doctor_id'],
            'weekday' => $validated['weekday'],
            'start_time' => $validated['start_time'] . ':00',
            'end_time' => $validated['end_time'] . ':00',
            'quota' => $validated['quota'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Jadwal praktik mingguan berhasil ditambahkan.');
    }

    public function update(Request $request, DoctorSchedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'start_time' => ['required'],
            'end_time' => ['required'],
            'quota' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $schedule->update([
            'start_time' => strlen($validated['start_time']) === 5 ? $validated['start_time'] . ':00' : $validated['start_time'],
            'end_time' => strlen($validated['end_time']) === 5 ? $validated['end_time'] . ':00' : $validated['end_time'],
            'quota' => $validated['quota'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Jadwal praktik berhasil diperbarui.');
    }

    public function destroy(DoctorSchedule $schedule): RedirectResponse
    {
        $schedule->delete();
        return back()->with('success', 'Jadwal praktik berhasil dihapus.');
    }

    /**
     * Store a dynamic schedule & quota override for a specific date
     */
    public function storeOverride(Request $request): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        $validated = $request->validate([
            'doctor_schedule_id' => ['required', 'uuid'],
            'override_date' => ['required', 'date', 'after_or_equal:today'],
            'is_cancelled' => ['nullable', 'boolean'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'quota' => ['nullable', 'integer', 'min:0', 'max:500'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $isCancelled = $request->boolean('is_cancelled');

        ScheduleOverride::updateOrCreate(
            [
                'doctor_schedule_id' => $validated['doctor_schedule_id'],
                'override_date' => $validated['override_date'],
            ],
            [
                'clinic_id' => $clinic->id,
                'is_cancelled' => $isCancelled,
                'start_time' => $isCancelled ? null : (!empty($validated['start_time']) ? (strlen($validated['start_time']) === 5 ? $validated['start_time'] . ':00' : $validated['start_time']) : null),
                'end_time' => $isCancelled ? null : (!empty($validated['end_time']) ? (strlen($validated['end_time']) === 5 ? $validated['end_time'] . ':00' : $validated['end_time']) : null),
                'quota' => $isCancelled ? 0 : ($validated['quota'] ?? null),
                'reason' => $validated['reason'] ?? ($isCancelled ? 'Praktik Diliburkan' : 'Penyesuaian Kuota'),
                'created_by' => $request->user()->id,
            ]
        );

        return back()->with('success', 'Override jadwal & kuota untuk tanggal tersebut berhasil disimpan.');
    }

    public function destroyOverride(ScheduleOverride $override): RedirectResponse
    {
        $override->delete();
        return back()->with('success', 'Override jadwal berhasil dihapus (jadwal kembali ke template mingguan).');
    }
}
