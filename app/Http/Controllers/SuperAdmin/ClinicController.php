<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClinicController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today()->toDateString();

        $clinics = Clinic::withCount([
            'doctors',
            'users',
            'queues' => function ($q) use ($today) {
                $q->whereDate('queue_date', $today);
            },
        ])->orderBy('name')->get();

        $activeClinicId = session('active_clinic_id');

        return view('superadmin.clinics.index', compact('clinics', 'activeClinicId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:clinics,slug'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'timezone' => ['required', 'string', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'unique:users,email'],
            'admin_password' => ['nullable', 'string', 'min:6'],
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Clinic::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $clinic = Clinic::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'timezone' => $validated['timezone'],
            'whatsapp_provider' => 'FONNTE',
            'is_active' => true,
        ]);

        // Optionally create clinic admin user
        if (!empty($validated['admin_email'])) {
            User::create([
                'clinic_id' => $clinic->id,
                'name' => $validated['admin_name'] ?: 'Admin ' . $clinic->name,
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password'] ?: 'password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]);
        }

        return back()->with('success', "Klinik '{$clinic->name}' berhasil didaftarkan.");
    }

    public function update(Request $request, Clinic $clinic): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'timezone' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $clinic->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'timezone' => $validated['timezone'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Data klinik '{$clinic->name}' berhasil diperbarui.");
    }

    /**
     * Switch active clinic context for Superadmin
     */
    public function switchClinic(Clinic $clinic): RedirectResponse
    {
        session(['active_clinic_id' => $clinic->id]);

        return back()->with('success', "Mode pantau dialihkan ke: {$clinic->name}");
    }
}
