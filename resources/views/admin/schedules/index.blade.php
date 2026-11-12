@extends('layouts.app')

@section('title', 'Jadwal & Kuota Praktik - Admin')

@section('content')
<div class="space-y-8">

    <!-- Top Doctor Filter & Actions -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-calendar-days text-blue-600 mr-2.5"></i> Jadwal Praktik & Kuota Dokter
            </h2>
            <p class="text-xs text-slate-500 mt-1">Atur template jadwal mingguan dan sesuaikan kuota khusus per tanggal</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('admin.schedules.index') }}" class="flex items-center space-x-2">
                <label class="text-xs font-semibold text-slate-500">Dokter:</label>
                <select name="doctor_id" class="px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="this.form.submit()">
                    @foreach($doctors as $d)
                        <option value="{{ $d->id }}" {{ $selectedDoctorId === $d->id ? 'selected' : '' }}>
                            {{ $d->name }} ({{ $d->specialty }})
                        </option>
                    @endforeach
                </select>
            </form>

            <button onclick="document.getElementById('addScheduleModal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs shadow-md shadow-blue-500/20 transition flex items-center">
                <i class="fa-solid fa-plus mr-1.5"></i> Tambah Jadwal Mingguan
            </button>
            <button onclick="document.getElementById('addOverrideModal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs shadow-md shadow-purple-500/20 transition flex items-center">
                <i class="fa-solid fa-sliders mr-1.5"></i> Set Override / Kuota Khusus
            </button>
        </div>
    </div>

    <!-- Section 1: Template Jadwal Mingguan -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-blue-600 mr-2"></i> Template Jadwal Mingguan Default
            </h3>
            <span class="text-xs text-slate-500">Berlaku otomatis setiap minggu kecuali ada override</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-700 font-semibold">
                    <tr>
                        <th class="px-6 py-3.5">Hari</th>
                        <th class="px-6 py-3.5">Dokter</th>
                        <th class="px-6 py-3.5">Jam Mulai</th>
                        <th class="px-6 py-3.5">Jam Selesai</th>
                        <th class="px-6 py-3.5">Kuota Pasien</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($schedules as $sch)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="px-6 py-4 font-bold text-slate-900">
                            {{ $sch->day_name }}
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-800">
                            {{ $sch->doctor->name }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-slate-700">
                            {{ substr($sch->start_time, 0, 5) }} WIB
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-slate-700">
                            {{ substr($sch->end_time, 0, 5) }} WIB
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs">
                                {{ $sch->quota }} Pasien
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($sch->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <button onclick="openEditScheduleModal({{ json_encode($sch) }})" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition" title="Edit Jadwal">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.schedules.destroy', $sch->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg text-rose-500 hover:bg-rose-50 transition" title="Hapus Jadwal">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                            Belum ada jadwal mingguan untuk dokter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Jadwal & Kuota Override Khusus Per Tanggal -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-purple-50/50">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center text-purple-900">
                    <i class="fa-solid fa-sliders text-purple-600 mr-2"></i> Override Kuota & Libur Mendadak (Khusus Tanggal Tertentu)
                </h3>
                <p class="text-xs text-purple-700 mt-0.5">Menimpa kuota atau membatalkan praktik pada tanggal tertentu tanpa menghapus template mingguan</p>
            </div>
            <button onclick="document.getElementById('addOverrideModal').classList.remove('hidden')" class="text-xs font-bold text-purple-700 hover:text-purple-900">
                + Tambah Override
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-700 font-semibold bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5">Tanggal Praktik</th>
                        <th class="px-6 py-3.5">Dokter</th>
                        <th class="px-6 py-3.5">Perubahan Kuota / Jam</th>
                        <th class="px-6 py-3.5">Status Praktik</th>
                        <th class="px-6 py-3.5">Alasan / Catatan</th>
                        <th class="px-6 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($overrides as $ov)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="px-6 py-4 font-bold text-slate-900">
                            {{ $ov->override_date->locale('id')->translatedFormat('l, d M Y') }}
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-800">
                            {{ $ov->schedule->doctor->name }}
                        </td>
                        <td class="px-6 py-4 text-xs">
                            @if($ov->is_cancelled)
                                <span class="text-rose-600 font-bold">Praktik Ditiadakan</span>
                            @else
                                <span class="font-bold text-purple-700">{{ $ov->quota ?? $ov->schedule->quota }} Pasien</span>
                                @if($ov->start_time)
                                    <span class="block text-slate-500">Jam: {{ substr($ov->start_time, 0, 5) }} - {{ substr($ov->end_time, 0, 5) }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($ov->is_cancelled)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Libur Khusus</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">Kuota Diubah</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            {{ $ov->reason ?: '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <form action="{{ route('admin.schedules.override.destroy', $ov->id) }}" method="POST" onsubmit="return confirm('Hapus override ini? Jadwal akan kembali normal.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 transition" title="Kembalikan ke Normal">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                            Tidak ada jadwal khusus/override aktif untuk tanggal mendatang.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Jadwal Mingguan -->
<div id="addScheduleModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900">Tambah Jadwal Mingguan</h3>
            <button onclick="document.getElementById('addScheduleModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.schedules.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Dokter *</label>
                <select name="doctor_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    @foreach($doctors as $d)
                        <option value="{{ $d->id }}" {{ $selectedDoctorId === $d->id ? 'selected' : '' }}>
                            {{ $d->name }} ({{ $d->specialty }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Hari Praktik *</label>
                <select name="weekday" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="1">Senin</option>
                    <option value="2">Selasa</option>
                    <option value="3">Rabu</option>
                    <option value="4">Kamis</option>
                    <option value="5">Jumat</option>
                    <option value="6">Sabtu</option>
                    <option value="0">Ahad / Minggu</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jam Mulai *</label>
                    <input type="time" name="start_time" value="08:00" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jam Selesai *</label>
                    <input type="time" name="end_time" value="14:00" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kuota Maksimal Pasien *</label>
                <input type="number" name="quota" value="25" min="1" max="500" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addScheduleModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Jadwal Mingguan -->
<div id="editScheduleModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900">Edit Jadwal Mingguan</h3>
            <button onclick="document.getElementById('editScheduleModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editScheduleForm" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jam Mulai *</label>
                    <input type="time" id="edit_sch_start" name="start_time" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jam Selesai *</label>
                    <input type="time" id="edit_sch_end" name="end_time" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kuota Maksimal Pasien *</label>
                <input type="number" id="edit_sch_quota" name="quota" min="1" max="500" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="flex items-center space-x-2 pt-2">
                <input type="checkbox" id="edit_sch_is_active" name="is_active" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                <label for="edit_sch_is_active" class="text-xs font-semibold text-slate-700">Jadwal Aktif</label>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('editScheduleModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm">Perbarui</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Set Override / Kuota Khusus Per Tanggal -->
<div id="addOverrideModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900 flex items-center text-purple-900">
                <i class="fa-solid fa-sliders text-purple-600 mr-2"></i> Set Override Jadwal & Kuota Khusus
            </h3>
            <button onclick="document.getElementById('addOverrideModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.schedules.override.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pilih Jadwal Dokter *</label>
                <select name="doctor_schedule_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    @foreach($schedules as $s)
                        <option value="{{ $s->id }}">
                            {{ $s->doctor->name }} &mdash; {{ $s->day_name }} ({{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}, Kuota Normal: {{ $s->quota }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal Praktik Khusus *</label>
                <input type="date" name="override_date" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="override_is_cancelled" name="is_cancelled" value="1" onchange="toggleCancelMode(this.checked)" class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300">
                    <label for="override_is_cancelled" class="text-xs font-bold text-rose-700">Tandai Dokter Libur Khusus Pada Tanggal Ini (Tutup Pendaftaran)</label>
                </div>

                <div id="quotaEditFields" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Ubah Kuota Pasien Baru</label>
                        <input type="number" name="quota" placeholder="Contoh: 40 (tambah) atau 10 (kurangi)" min="1" max="500" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alasan / Keterangan</label>
                <input type="text" name="reason" placeholder="Contoh: Dokter ada seminar / Penambahan kuota sore" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addOverrideModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-medium text-sm">Simpan Override</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditScheduleModal(sch) {
        document.getElementById('editScheduleForm').action = `/admin/jadwal/${sch.id}`;
        document.getElementById('edit_sch_start').value = sch.start_time.substring(0, 5);
        document.getElementById('edit_sch_end').value = sch.end_time.substring(0, 5);
        document.getElementById('edit_sch_quota').value = sch.quota;
        document.getElementById('edit_sch_is_active').checked = Boolean(sch.is_active);
        document.getElementById('editScheduleModal').classList.remove('hidden');
    }

    function toggleCancelMode(isCancelled) {
        const fields = document.getElementById('quotaEditFields');
        if (isCancelled) {
            fields.classList.add('opacity-40', 'pointer-events-none');
        } else {
            fields.classList.remove('opacity-40', 'pointer-events-none');
        }
    }
</script>
@endsection

