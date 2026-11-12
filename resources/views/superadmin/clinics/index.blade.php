@extends('layouts.app')

@section('title', 'Manajemen Seluruh Klinik - SuperAdmin')

@section('content')
<div class="space-y-6">

    <!-- Header bar -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-3xl p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold uppercase tracking-wider mb-2">
                <i class="fa-solid fa-crown text-amber-400 mr-1"></i> Super Administrator Panel
            </div>
            <h2 class="text-2xl font-black tracking-tight text-white flex items-center">
                Multi-Klinik Hub & Manajemen Jaringan
            </h2>
            <p class="text-xs text-slate-300 mt-1">Daftarkan klinik baru, kelola status operasional, dan pantau aktivitas seluruh cabang.</p>
        </div>

        <button onclick="document.getElementById('addClinicModal').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg shadow-blue-500/30 transition flex items-center w-max">
            <i class="fa-solid fa-plus-circle mr-2 text-base"></i> Daftarkan Klinik Baru
        </button>
    </div>

    <!-- Active Clinic Indicator for SuperAdmin -->
    @if(session('active_clinic_id'))
    @php
        $curr = $clinics->firstWhere('id', session('active_clinic_id'));
    @endphp
    @if($curr)
    <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold">
                <i class="fa-solid fa-eye"></i>
            </div>
            <div>
                <span class="text-xs text-blue-600 font-semibold uppercase tracking-wider">Klinik Sedang Dipantau:</span>
                <h4 class="font-bold text-slate-900">{{ $curr->name }} <span class="text-xs text-slate-500 font-normal">({{ $curr->slug }})</span></h4>
            </div>
        </div>

        <a href="{{ route('queue.index') }}" class="px-4 py-2 rounded-xl bg-white border border-blue-300 text-xs font-bold text-blue-700 hover:bg-blue-100 transition shadow-sm">
            Buka Dashboard Antrian Klinik Ini &rarr;
        </a>
    </div>
    @endif
    @endif

    <!-- Clinics Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center">
                <i class="fa-solid fa-hospital text-blue-600 mr-2"></i> Daftar Klinik Terdaftar ({{ $clinics->count() }})
            </h3>
            <span class="text-xs text-slate-500">Shared Database Multi-Tenant Scope</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-700 font-semibold bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4">Nama Klinik & URL Slug</th>
                        <th class="px-6 py-4">Kontak & Zona Waktu</th>
                        <th class="px-6 py-4 text-center">Dokter</th>
                        <th class="px-6 py-4 text-center">Staf/Admin</th>
                        <th class="px-6 py-4 text-center">Antrian Hari Ini</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Aksi Superadmin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($clinics as $c)
                    <tr class="hover:bg-slate-50/75 transition {{ session('active_clinic_id') === $c->id ? 'bg-blue-50/40' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-base shadow-sm">
                                    <i class="fa-solid fa-hospital-user"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 flex items-center">
                                        {{ $c->name }}
                                        @if(session('active_clinic_id') === $c->id)
                                            <span class="ml-2 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold">Sedang Aktif</span>
                                        @endif
                                    </h4>
                                    <span class="text-xs font-mono text-slate-500">slug: {{ $c->slug }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-xs space-y-0.5">
                            <div class="text-slate-800 font-medium"><i class="fa-solid fa-phone text-slate-400 mr-1"></i> {{ $c->phone }}</div>
                            <div class="text-slate-400"><i class="fa-regular fa-clock mr-1"></i> {{ $c->timezone }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">
                                {{ $c->doctors_count }} Dokter
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-xs">
                                {{ $c->users_count }} Akun
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-slate-900">
                            {{ $c->queues_count }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($c->is_active)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <form action="{{ route('superadmin.clinics.switch', $c->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ session('active_clinic_id') === $c->id ? 'bg-blue-600 text-white' : 'bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700' }}" title="Beralih ke klinik ini">
                                        <i class="fa-solid fa-repeat mr-1"></i> Switch
                                    </button>
                                </form>

                                <a href="{{ route('display', $c->slug) }}" target="_blank" class="p-2 rounded-xl text-emerald-600 hover:bg-emerald-50 transition" title="Buka Layar Display TV Klinik Ini">
                                    <i class="fa-solid fa-desktop"></i>
                                </a>

                                <button onclick="openEditClinicModal({{ json_encode($c) }})" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition" title="Edit Klinik">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                            Belum ada klinik terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Klinik Baru -->
<div id="addClinicModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-lg text-slate-900 flex items-center">
                <i class="fa-solid fa-hospital-user text-blue-600 mr-2"></i> Pendaftaran Klinik Baru
            </h3>
            <button onclick="document.getElementById('addClinicModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('superadmin.clinics.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Klinik *</label>
                <input type="text" name="name" required placeholder="Contoh: Klinik Medika Utama" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Slug URL (Opsional)</label>
                    <input type="text" name="slug" placeholder="medika-utama" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor Telepon / WA *</label>
                    <input type="text" name="phone" required placeholder="081234567890" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alamat Klinik</label>
                <textarea name="address" rows="2" placeholder="Jl. Sudirman No. 45" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Zona Waktu *</label>
                <select name="timezone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="Asia/Jakarta">WIB (Asia/Jakarta)</option>
                    <option value="Asia/Makassar">WITA (Asia/Makassar)</option>
                    <option value="Asia/Jayapura">WIT (Asia/Jayapura)</option>
                </select>
            </div>

            <!-- Buat Akun Admin Sekaligus -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-shield text-blue-600 mr-1.5"></i> Akun Administrator Klinik (Opsional)
                </h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] text-slate-500 mb-0.5">Nama Admin</label>
                        <input type="text" name="admin_name" placeholder="Admin Klinik" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-500 mb-0.5">Email Login Admin</label>
                        <input type="email" name="admin_email" placeholder="admin@medika.id" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-500 mb-0.5">Password</label>
                    <input type="password" name="admin_password" placeholder="Minimal 6 karakter" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addClinicModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm shadow-md shadow-blue-500/20">Daftarkan Klinik</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Klinik -->
<div id="editClinicModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-lg text-slate-900">Edit Data Klinik</h3>
            <button onclick="document.getElementById('editClinicModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editClinicForm" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Klinik *</label>
                <input type="text" id="edit_clinic_name" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor Telepon / WA *</label>
                <input type="text" id="edit_clinic_phone" name="phone" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alamat Klinik</label>
                <textarea id="edit_clinic_address" name="address" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Zona Waktu *</label>
                <select id="edit_clinic_tz" name="timezone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="Asia/Jakarta">WIB (Asia/Jakarta)</option>
                    <option value="Asia/Makassar">WITA (Asia/Makassar)</option>
                    <option value="Asia/Jayapura">WIT (Asia/Jayapura)</option>
                </select>
            </div>

            <div class="flex items-center space-x-2 pt-2">
                <input type="checkbox" id="edit_clinic_active" name="is_active" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                <label for="edit_clinic_active" class="text-xs font-semibold text-slate-700">Klinik Berstatus Aktif</label>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('editClinicModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm">Perbarui Klinik</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditClinicModal(clinic) {
        document.getElementById('editClinicForm').action = `/superadmin/klinik/${clinic.id}`;
        document.getElementById('edit_clinic_name').value = clinic.name;
        document.getElementById('edit_clinic_phone').value = clinic.phone || '';
        document.getElementById('edit_clinic_address').value = clinic.address || '';
        document.getElementById('edit_clinic_tz').value = clinic.timezone || 'Asia/Jakarta';
        document.getElementById('edit_clinic_active').checked = Boolean(clinic.is_active);
        document.getElementById('editClinicModal').classList.remove('hidden');
    }
</script>
@endsection
