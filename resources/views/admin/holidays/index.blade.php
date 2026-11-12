@extends('layouts.app')

@section('title', 'Hari Libur Klinik - Admin')

@section('content')
<div class="space-y-6">

    <!-- Header bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-mug-hot text-amber-500 mr-2.5"></i> Hari Libur Klinik & Dokter
            </h2>
            <p class="text-xs text-slate-500 mt-1">Atur hari libur nasional, cuti klinik bersama, atau izin dokter tertentu</p>
        </div>

        <button onclick="document.getElementById('addHolidayModal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-medium text-sm shadow-md shadow-amber-500/20 transition flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Hari Libur
        </button>
    </div>

    <!-- Holidays Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Tanggal Libur</th>
                        <th class="px-6 py-4">Cakupan Libur</th>
                        <th class="px-6 py-4">Keterangan / Alasan</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($holidays as $h)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="px-6 py-4 font-bold text-slate-900">
                            {{ $h->holiday_date->locale('id')->translatedFormat('l, d F Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($h->doctor)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    Dokter Khusus: {{ $h->doctor->name }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                    <i class="fa-solid fa-hospital mr-1"></i> Seluruh Klinik Libur
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-800 font-medium">
                            {{ $h->reason }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <form action="{{ route('admin.holidays.destroy', $h->id) }}" method="POST" onsubmit="return confirm('Hapus hari libur ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                            Belum ada jadwal hari libur yang didaftarkan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Hari Libur -->
<div id="addHolidayModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900">Tambah Hari Libur</h3>
            <button onclick="document.getElementById('addHolidayModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.holidays.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal Libur *</label>
                <input type="date" name="holiday_date" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Cakupan Libur *</label>
                <select name="doctor_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Seluruh Klinik Libur (Semua Dokter Tutup)</option>
                    @foreach($doctors as $d)
                        <option value="{{ $d->id }}">Hanya Dokter: {{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alasan Libur *</label>
                <input type="text" name="reason" placeholder="Contoh: Libur Nasional Idul Fitri / Cuti Tahunan" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addHolidayModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-medium text-sm">Simpan Libur</button>
            </div>
        </form>
    </div>
</div>
@endsection
