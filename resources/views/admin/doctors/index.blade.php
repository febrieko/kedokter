@extends('layouts.app')

@section('title', 'Manajemen Dokter - Admin')

@section('content')
<div class="space-y-6">

    <!-- Header bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-user-doctor text-blue-600 mr-2.5"></i> Manajemen Dokter
            </h2>
            <p class="text-xs text-slate-500 mt-1">Kelola data dokter, spesialisasi, dan ruangan periksa di {{ $clinic->name }}</p>
        </div>

        <button onclick="document.getElementById('addDoctorModal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm shadow-md shadow-blue-500/20 transition flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Dokter Baru
        </button>
    </div>

    <!-- Doctors Cards & Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Dokter & Spesialisasi</th>
                        <th class="px-6 py-4">Nomor SIP</th>
                        <th class="px-6 py-4">Ruangan</th>
                        <th class="px-6 py-4">Estimasi Konsultasi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($doctors as $doc)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                    <i class="fa-solid fa-stethoscope"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900">{{ $doc->name }}</h4>
                                    <span class="text-xs text-blue-600 font-medium">{{ $doc->specialty }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-xs font-mono text-slate-700">
                            {{ $doc->sip_number ?: '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-800">
                                {{ $doc->pivot->room_number ?: 'Ruang Poli' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs">
                            <span class="font-bold text-slate-800">{{ $doc->avg_minutes_per_patient }}</span> menit / pasien
                        </td>
                        <td class="px-6 py-4">
                            @if($doc->pivot->is_active)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <button onclick="openEditDoctorModal({{ json_encode($doc) }})" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition" title="Edit Dokter">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.doctors.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Hapus dokter {{ $doc->name }} dari klinik ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg text-rose-500 hover:bg-rose-50 transition" title="Hapus Dokter">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                            <i class="fa-solid fa-user-doctor text-3xl mb-2 block"></i>
                            Belum ada dokter yang didaftarkan di klinik ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Dokter Baru -->
<div id="addDoctorModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-lg text-slate-900 flex items-center">
                <i class="fa-solid fa-user-plus text-blue-600 mr-2"></i> Tambah Dokter Baru
            </h3>
            <button onclick="document.getElementById('addDoctorModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.doctors.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap Dokter & Gelar *</label>
                <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: dr. Ahmad Rasyid, Sp.A">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Spesialisasi / Poli *</label>
                <input type="text" name="specialty" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: Poli Spesialis Anak / Umum">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor SIP</label>
                    <input type="text" name="sip_number" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="SIP-xxx/2026">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Ruang Praktik</label>
                    <input type="text" name="room_number" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: Ruang 102">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rata-rata Durasi Konsultasi (Menit) *</label>
                <input type="number" name="avg_minutes_per_patient" value="15" min="1" max="120" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <span class="text-[11px] text-slate-400">Digunakan untuk menghitung estimasi jam giliran pasien saat mendaftar.</span>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addDoctorModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm shadow-md shadow-blue-500/20">Simpan Dokter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Dokter -->
<div id="editDoctorModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-lg text-slate-900 flex items-center">
                <i class="fa-solid fa-user-pen text-blue-600 mr-2"></i> Edit Data Dokter
            </h3>
            <button onclick="document.getElementById('editDoctorModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editDoctorForm" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap Dokter & Gelar *</label>
                <input type="text" id="edit_name" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Spesialisasi / Poli *</label>
                <input type="text" id="edit_specialty" name="specialty" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor SIP</label>
                    <input type="text" id="edit_sip_number" name="sip_number" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Ruang Praktik</label>
                    <input type="text" id="edit_room_number" name="room_number" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rata-rata Durasi Konsultasi (Menit) *</label>
                <input type="number" id="edit_avg_minutes" name="avg_minutes_per_patient" min="1" max="120" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="flex items-center space-x-2 pt-2">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                <label for="edit_is_active" class="text-xs font-semibold text-slate-700">Aktif berpraktik di klinik ini</label>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('editDoctorModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm shadow-md shadow-blue-500/20">Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditDoctorModal(doctor) {
        document.getElementById('editDoctorForm').action = `/admin/dokter/${doctor.id}`;
        document.getElementById('edit_name').value = doctor.name;
        document.getElementById('edit_specialty').value = doctor.specialty;
        document.getElementById('edit_sip_number').value = doctor.sip_number || '';
        document.getElementById('edit_room_number').value = doctor.pivot ? (doctor.pivot.room_number || '') : '';
        document.getElementById('edit_avg_minutes').value = doctor.avg_minutes_per_patient;
        document.getElementById('edit_is_active').checked = doctor.pivot ? Boolean(doctor.pivot.is_active) : true;
        document.getElementById('editDoctorModal').classList.remove('hidden');
    }
</script>
@endsection

