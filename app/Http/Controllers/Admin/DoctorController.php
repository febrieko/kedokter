<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    protected function getClinic(Request $request): Clinic
    {
        return $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()->clinic 
            ?? Clinic::firstOrFail();
    }

    public function index(Request $request): View
    {
        $clinic = $this->getClinic($request);
        $doctors = $clinic->doctors()
            ->withPivot('room_number', 'is_active')
            ->orderBy('name')
            ->get();

        return view('admin.doctors.index', compact('clinic', 'doctors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialty' => ['required', 'string', 'max:255'],
            'sip_number' => ['nullable', 'string', 'max:100'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'avg_minutes_per_patient' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $doctor = Doctor::create([
            'name' => $validated['name'],
            'specialty' => $validated['specialty'],
            'sip_number' => $validated['sip_number'],
            'avg_minutes_per_patient' => $validated['avg_minutes_per_patient'],
            'is_active' => true,
        ]);

        $clinic->doctors()->attach($doctor->id, [
            'room_number' => $validated['room_number'] ?? 'Ruang Poli',
            'is_active' => true,
        ]);

        return back()->with('success', "Dokter {$doctor->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialty' => ['required', 'string', 'max:255'],
            'sip_number' => ['nullable', 'string', 'max:100'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'avg_minutes_per_patient' => ['required', 'integer', 'min:1', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $doctor->update([
            'name' => $validated['name'],
            'specialty' => $validated['specialty'],
            'sip_number' => $validated['sip_number'],
            'avg_minutes_per_patient' => $validated['avg_minutes_per_patient'],
        ]);

        $clinic->doctors()->updateExistingPivot($doctor->id, [
            'room_number' => $validated['room_number'] ?? 'Ruang Poli',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Data dokter {$doctor->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, Doctor $doctor): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        // Deactivate or detach from clinic
        $clinic->doctors()->detach($doctor->id);

        return back()->with('success', "Dokter {$doctor->name} berhasil dihapus dari klinik.");
    }
}

