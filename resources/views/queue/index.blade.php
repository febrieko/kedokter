@extends('layouts.app')

@section('title', 'Dashboard Antrian Pasien')

@section('content')
<div class="space-y-6">

    <!-- Header bar with filter and action button -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <form method="GET" action="{{ route('queue.index') }}" class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Pilih Tanggal</label>
                <input type="date" name="date" value="{{ $date }}" class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="this.form.submit()">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Pilih Dokter</label>
                <select name="doctor_id" class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="this.form.submit()">
                    @forelse($doctors as $doc)
                        <option value="{{ $doc->id }}" {{ $selectedDoctor?->id === $doc->id ? 'selected' : '' }}>
                            {{ $doc->name }} ({{ $doc->specialty }})
                        </option>
                    @empty
                        <option value="">Belum ada dokter terdaftar</option>
                    @endforelse
                </select>
            </div>
        </form>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('display', ['clinic_slug' => $clinic->slug, 'date' => $date, 'doctor_id' => $selectedDoctor?->id]) }}" target="_blank" class="px-3.5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-medium text-xs border border-emerald-200 shadow-sm transition flex items-center" title="Buka layar TV display untuk tanggal & dokter ini">
                <i class="fa-solid fa-desktop mr-1.5 text-emerald-600"></i> TV Display
            </a>

            <form action="{{ route('queue.reminders.h1') }}" method="POST" onsubmit="return confirm('Kirimkan pesan pengingat WhatsApp ke seluruh pasien yang terjadwal besok?')">
                @csrf
                <button type="submit" class="px-3.5 py-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 font-medium text-xs border border-amber-200 shadow-sm transition flex items-center" title="Kirimkan pengingat WA H-1 ke pasien besok">
                    <i class="fa-solid fa-bell mr-1.5 text-amber-600"></i> Kirim Pengingat Besok (H-1)
                </button>
            </form>

            <button onclick="document.getElementById('manualModal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs sm:text-sm shadow-md shadow-blue-500/20 transition flex items-center">
                <i class="fa-solid fa-user-plus mr-2"></i> Tambah Pasien Manual
            </button>
        </div>
    </div>

    <!-- Doctor schedule & statistics summary card -->
    @if($selectedDoctor)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Dokter Praktik</p>
                <h4 class="font-bold text-slate-900 leading-snug">{{ $selectedDoctor->name }}</h4>
                <p class="text-xs text-blue-600">{{ $selectedDoctor->specialty }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Jam Pelayanan</p>
                <h4 class="font-bold text-slate-900">
                    {{ $scheduleInfo ? $scheduleInfo['start_time'] . ' - ' . $scheduleInfo['end_time'] : 'Libur' }}
                </h4>
                @if($scheduleInfo && $scheduleInfo['is_overridden'])
                    <span class="inline-block text-[10px] font-semibold text-purple-700 bg-purple-100 px-1.5 py-0.5 rounded">Jadwal Khusus</span>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Kuota Terpakai</p>
                <h4 class="font-bold text-slate-900">
                    {{ $queues->where('status', '!=', 'CANCELLED')->count() }} / {{ $scheduleInfo['quota'] ?? 0 }}
                </h4>
                <p class="text-xs text-slate-500">Sisa: <strong class="text-amber-600">{{ $scheduleInfo['remaining_quota'] ?? 0 }}</strong> kuota</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Status Antrian Hari Ini</p>
                <div class="flex items-center space-x-2 mt-1">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-100 text-blue-700">Tunggu: {{ $queues->where('status', 'WAITING')->count() }}</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">Selesai: {{ $queues->where('status', 'DONE')->count() }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Queue Table List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-list-ol mr-2 text-blue-600"></i> Daftar Nomor Antrian
                <span class="ml-2 px-2.5 py-0.5 text-xs rounded-full bg-slate-100 text-slate-600 font-semibold">{{ $queues->count() }} Pasien</span>
            </h3>
            <span class="text-xs text-slate-500">Auto-update ketika aksi dilakukan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5 text-center">No</th>
                        <th class="px-6 py-3.5">Nama Pasien</th>
                        <th class="px-6 py-3.5">Kontak & NIK</th>
                        <th class="px-6 py-3.5">Jaminan / Asuransi</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Sumber</th>
                        <th class="px-6 py-3.5 text-center">Aksi Resepsionis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($queues as $q)
                    <tr class="hover:bg-slate-50/75 transition {{ $q->status === 'CALLED' ? 'bg-amber-50/60' : '' }}">
                        <td class="px-6 py-4 text-center font-bold text-lg text-slate-900">
                            #{{ $q->number }}
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-900">
                            <div class="flex items-center space-x-2">
                                <span>{{ $q->patient->name }}</span>
                                @if($q->call_count > 0 && $q->status === 'CALLED')
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500 text-white animate-pulse">
                                        Panggilan ke-{{ $q->call_count }}/3
                                    </span>
                                @endif
                            </div>
                            @if($q->complaint)
                                <div class="text-[11px] text-amber-800 bg-amber-50/80 px-2 py-0.5 rounded border border-amber-200/70 inline-flex items-center mt-1 max-w-xs truncate" title="{{ $q->complaint }}">
                                    <i class="fa-solid fa-head-side-cough text-amber-600 mr-1 text-[10px]"></i> {{ $q->complaint }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs space-y-0.5">
                            <div class="font-mono text-slate-700"><i class="fa-brands fa-whatsapp text-emerald-600 mr-1"></i>{{ $q->patient->phone }}</div>
                            @if($q->patient->nik)
                                <div class="text-slate-400">NIK: {{ $q->patient->nik }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($q->patient->insurance_type === 'BPJS')
                                <span class="px-2 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> BPJS
                                </span>
                            @elseif($q->patient->insurance_type === 'OTHER')
                                <span class="px-2 py-1 rounded-md text-xs font-semibold bg-purple-100 text-purple-800">
                                    Asuransi Swasta
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
                                    Umum / Mandiri
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @switch($q->status)
                                @case('WAITING')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Menunggu</span>
                                    @break
                                @case('CALLED')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 flex items-center w-max">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-ping"></span> Dipanggil
                                    </span>
                                    @break
                                @case('IN_SERVICE')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Diperiksa</span>
                                    @break
                                @case('SKIPPED')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Dilewati</span>
                                    @break
                                @case('DONE')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Selesai</span>
                                    @break
                                @case('CANCELLED')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Batal</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-xs font-medium">
                            @if($q->source === 'WHATSAPP')
                                <span class="text-emerald-700"><i class="fa-brands fa-whatsapp text-sm mr-1"></i> WhatsApp</span>
                            @else
                                <span class="text-slate-600"><i class="fa-solid fa-person-walking mr-1"></i> Manual</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center space-x-1.5">
                                @if(in_array($q->status, ['WAITING', 'CALLED', 'SKIPPED']))
                                <form action="{{ route('queue.call', $q->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-medium text-xs shadow-sm transition flex items-center" title="Panggil Pasien (Maksimal 3x)">
                                        <i class="fa-solid fa-bullhorn mr-1"></i> Panggil
                                    </button>
                                </form>
                                @endif

                                @if($q->status === 'CALLED')
                                <form action="{{ route('queue.start', $q->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs shadow-sm transition flex items-center" title="Mulai Pemeriksaan">
                                        <i class="fa-solid fa-stethoscope mr-1"></i> Mulai
                                    </button>
                                </form>
                                <form action="{{ route('queue.skip', $q->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium text-xs transition" title="Lewati Pasien">
                                        <i class="fa-solid fa-forward"></i>
                                    </button>
                                </form>
                                @endif

                                @if($q->status === 'IN_SERVICE')
                                <form action="{{ route('queue.finish', $q->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-sm transition flex items-center">
                                        <i class="fa-solid fa-check mr-1"></i> Selesai
                                    </button>
                                </form>
                                @endif

                                @if(!in_array($q->status, ['DONE', 'CANCELLED']))
                                <form action="{{ route('queue.cancel', $q->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan nomor antrian ini?')">
                                    @csrf
                                    <button type="submit" class="px-2 py-1.5 text-slate-400 hover:text-rose-600 transition" title="Batalkan Antrian">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                            Belum ada antrian pasien pada tanggal ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Pasien Manual (Walk-in) -->
<div id="manualModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative animate-in fade-in duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="font-bold text-lg text-slate-900 flex items-center">
                <i class="fa-solid fa-user-plus text-blue-600 mr-2"></i> Pendaftaran Antrian Manual
            </h3>
            <button onclick="document.getElementById('manualModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('queue.store-manual') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="clinic_id" value="{{ $clinic->id }}">
            <input type="hidden" name="doctor_id" value="{{ $selectedDoctor?->id }}">
            <input type="hidden" name="queue_date" value="{{ $date }}">

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap Pasien *</label>
                <input type="text" name="name" required class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: Ahmad Fauzi">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nomor WhatsApp / HP *</label>
                <input type="text" name="phone" required class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: 081234567890">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">NIK KTP (16 Digit)</label>
                    <input type="text" name="nik" maxlength="16" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Opsional">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Jaminan *</label>
                    <select name="insurance_type" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="NONE">Umum / Mandiri</option>
                        <option value="BPJS">BPJS Kesehatan</option>
                        <option value="OTHER">Asuransi Lain</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nomor BPJS / Polis (Jika ada)</label>
                <input type="text" name="insurance_number" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Nomor kartu kepesertaan">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Keluhan Utama / Alasan Kunjungan</label>
                <textarea name="complaint" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: Demam 2 hari, batuk pilek, kontrol tensi rutin..."></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('manualModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm shadow-md shadow-blue-500/20">Daftarkan Sekarang</button>
            </div>
        </form>
    </div>
</div>
@endsection

