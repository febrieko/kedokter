<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Encounter;
use App\Models\MedicalRecordAddendum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    /**
     * Tampilan Detail Rekam Medis Elektronik (RME) Kunjungan Pasien
     */
    public function show(Request $request, Encounter $encounter): View|RedirectResponse
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic 
            ?? Clinic::firstOrFail();

        // Keamanan Scope Multi-Klinik
        if ($encounter->clinic_id !== $clinic->id && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses ke rekam medis klinik lain.');
        }

        $encounter->load([
            'patient',
            'doctor',
            'clinic',
            'queue',
            'medicalRecord.primaryIcd10',
            'medicalRecord.addendums.author',
            'prescriptions.items',
            'clinicalProcedures',
            'lockedByUser',
        ]);

        $medicalRecord = $encounter->medicalRecord;

        return view('doctor.medical-record.show', compact('encounter', 'medicalRecord', 'clinic'));
    }

    /**
     * Tampilan Cetak / Resume Medis Rawat Jalan (Print-ready)
     */
    public function print(Request $request, Encounter $encounter): View|RedirectResponse
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic 
            ?? Clinic::firstOrFail();

        if ($encounter->clinic_id !== $clinic->id && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses ke rekam medis klinik lain.');
        }

        $encounter->load([
            'patient',
            'doctor',
            'clinic',
            'medicalRecord.primaryIcd10',
            'medicalRecord.addendums.author',
            'prescriptions.items',
            'clinicalProcedures',
            'lockedByUser',
        ]);

        $medicalRecord = $encounter->medicalRecord;

        return view('doctor.medical-record.print', compact('encounter', 'medicalRecord', 'clinic'));
    }

    /**
     * Kunci & Tandatangani Rekam Medis (Sign & Lock)
     * Mengubah status menjadi LOCKED sehingga read-only sesuai standar hukum kesehatan
     */
    public function lock(Request $request, Encounter $encounter): RedirectResponse
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic 
            ?? Clinic::firstOrFail();

        if ($encounter->clinic_id !== $clinic->id && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if ($encounter->isLocked()) {
            return back()->with('info', 'Rekam medis ini sudah terkunci sebelumnya.');
        }

        // Kunci encounter dengan stempel waktu dan user ID dokter/pemeriksa
        $encounter->lock(auth()->user());

        return back()->with('success', "Rekam medis kunjungan #{$encounter->id} berhasil dikunci dan ditandatangani secara digital (Status: LOCKED). Catatan kini berstatus Read-Only.");
    }

    /**
     * Tambahkan Catatan Tambahan (Addendum) pada Rekam Medis yang telah dikunci
     */
    public function addAddendum(Request $request, Encounter $encounter): RedirectResponse
    {
        $clinic = $request->attributes->get('clinic') 
            ?? (app()->has('currentClinic') ? app('currentClinic') : null) 
            ?? $request->user()?->clinic 
            ?? Clinic::firstOrFail();

        if ($encounter->clinic_id !== $clinic->id && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if (!$encounter->isLocked()) {
            return back()->with('error', 'Fitur Addendum hanya diperuntukkan bagi Rekam Medis yang telah dikunci (LOCKED). Untuk rekam medis terbuka, silakan perbarui langsung.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'required|string|max:5000',
        ]);

        $medicalRecord = $encounter->medicalRecord;
        if (!$medicalRecord) {
            return back()->with('error', 'Catatan medis tidak ditemukan.');
        }

        // Catat addendum baru
        MedicalRecordAddendum::create([
            'medical_record_id' => $medicalRecord->id,
            'user_id' => auth()->id(),
            'reason' => $validated['reason'],
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Catatan tambahan (Addendum) berhasil ditambahkan ke riwayat medis pasien.');
    }
}

