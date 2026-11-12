@extends('layouts.app')

@section('title', 'Ruang Dokter - ' . $selectedDoctor->name)

@section('content')
<div class="space-y-6">

    <!-- Header & Doctor Info Bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-blue-600 flex items-center justify-center text-white text-2xl shadow-md shadow-indigo-500/20">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h2 class="text-xl font-bold text-slate-900">{{ $selectedDoctor->name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $selectedDoctor->specialty }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mt-1">
                    <span><i class="fa-solid fa-id-card-clip mr-1 text-slate-400"></i> SIP: {{ $selectedDoctor->sip_number ?? '-' }}</span>
                    <span><i class="fa-solid fa-door-open mr-1 text-slate-400"></i> {{ $selectedDoctor->clinics->firstWhere('id', $clinic->id)?->pivot->room_number ?? 'Ruang Poli' }}</span>
                    <span><i class="fa-solid fa-clock-rotate-left mr-1 text-slate-400"></i> Standar: {{ $selectedDoctor->avg_minutes_per_patient ?? 15 }} mnt/pasien</span>
                </div>
            </div>
        </div>

        <!-- Filter & Doctor Selector -->
        <div class="flex flex-wrap items-center gap-3">
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || !auth()->user()->doctor_id)
            <form action="{{ route('doctor.queue.index') }}" method="GET" class="flex items-center space-x-2">
                <input type="hidden" name="date" value="{{ $date }}">
                <div class="relative">
                    <select name="doctor_id" onchange="this.form.submit()" class="pl-8 pr-8 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none shadow-sm cursor-pointer">
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" {{ $selectedDoctor->id === $doc->id ? 'selected' : '' }}>
                                {{ $doc->name }} ({{ $doc->specialty }})
                            </option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-stethoscope absolute left-2.5 top-3 text-slate-400 text-xs pointer-events-none"></i>
                    <i class="fa-solid fa-chevron-down absolute right-2.5 top-3 text-slate-400 text-xs pointer-events-none"></i>
                </div>
            </form>
            @endif

            <form action="{{ route('doctor.queue.index') }}" method="GET" class="flex items-center">
                @if(request()->has('doctor_id'))
                    <input type="hidden" name="doctor_id" value="{{ request('doctor_id') }}">
                @endif
                <div class="relative">
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="pl-8 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm cursor-pointer">
                    <i class="fa-solid fa-calendar-day absolute left-2.5 top-3 text-slate-400 text-xs pointer-events-none"></i>
                </div>
            </form>

            <a href="{{ route('display', $clinic->slug) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center shadow-sm">
                <i class="fa-solid fa-desktop mr-1.5 text-slate-500"></i> Buka TV Display
            </a>
        </div>
    </div>

    <!-- Metric Badges Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">
                <span>Total Pasien</span>
                <i class="fa-solid fa-users text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-slate-900">{{ $totalCount }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Kuota: {{ $scheduleInfo['quota'] ?? '-' }} pasien</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-blue-200/80 shadow-sm bg-gradient-to-br from-blue-50/40 to-white">
            <div class="flex items-center justify-between text-blue-700 text-xs font-semibold uppercase tracking-wider mb-1">
                <span>Menunggu Giliran</span>
                <i class="fa-solid fa-clock text-blue-500"></i>
            </div>
            <div class="text-2xl font-bold text-blue-700">{{ $waitingCount }}</div>
            <div class="text-[11px] text-blue-600/80 mt-1">Siap dipanggil masuk</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-sm bg-gradient-to-br from-emerald-50/40 to-white">
            <div class="flex items-center justify-between text-emerald-700 text-xs font-semibold uppercase tracking-wider mb-1">
                <span>Selesai Diperiksa</span>
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
            </div>
            <div class="text-2xl font-bold text-emerald-700">{{ $doneCount }}</div>
            <div class="text-[11px] text-emerald-600/80 mt-1">Konsultasi tuntas</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-amber-200/80 shadow-sm bg-gradient-to-br from-amber-50/40 to-white">
            <div class="flex items-center justify-between text-amber-700 text-xs font-semibold uppercase tracking-wider mb-1">
                <span>Dilewati / Batal</span>
                <i class="fa-solid fa-forward-step text-amber-500"></i>
            </div>
            <div class="text-2xl font-bold text-amber-700">{{ $skippedCount }}</div>
            <div class="text-[11px] text-amber-600/80 mt-1">Bisa dipanggil kembali</div>
        </div>
    </div>

    <!-- Main Workspace (Grid 12 Cols: 7 Cols Left Active Consultation + 5 Cols Right Queue List) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Active Patient Consultation Card -->
        <div class="lg:col-span-7 space-y-5">
            @if($activeQueue)
                <!-- Card Pasien Sedang Aktif -->
                <div class="bg-white rounded-3xl border-2 {{ $activeQueue->status === 'IN_SERVICE' ? 'border-emerald-500 shadow-emerald-500/10' : 'border-blue-500 shadow-blue-500/10' }} shadow-xl overflow-hidden">
                    <!-- Status Header Banner -->
                    <div class="px-6 py-3.5 {{ $activeQueue->status === 'IN_SERVICE' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white' : 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' }} flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-white animate-ping"></span>
                            <span class="text-xs font-bold uppercase tracking-wider">
                                {{ $activeQueue->status === 'IN_SERVICE' ? 'PASIEN SEDANG DALAM PEMERIKSAAN' : 'PASIEN SEDANG DIPANGGIL KE RUANGAN' }}
                            </span>
                        </div>
                        <div class="text-xs font-medium">
                            @if($activeQueue->status === 'IN_SERVICE')
                                <span id="timerBadge" class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full font-mono font-bold">
                                    <i class="fa-solid fa-stopwatch mr-1"></i> Durasi: <span id="liveDurationTimer">00:00</span>
                                </span>
                            @else
                                <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full">
                                    Panggilan ke-{{ $activeQueue->call_count }} dari 3
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Nomor & Nama Pasien -->
                        <div class="flex items-start justify-between border-b border-slate-100 pb-5">
                            <div>
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Nomor Antrian</span>
                                <div class="text-5xl font-black text-slate-900 tracking-tight mt-0.5">
                                    #{{ str_pad($activeQueue->number, 2, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $activeQueue->patient->insurance_type === 'BPJS' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                                    {{ $activeQueue->patient->insurance_type }}
                                </span>
                                @if($activeQueue->patient->insurance_number)
                                    <div class="text-xs font-mono text-slate-500 mt-1">
                                        No. Kartu: {{ $activeQueue->patient->insurance_number }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Data Detail Pasien -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-4 text-sm">
                            <div class="space-y-1">
                                <span class="text-xs text-slate-400 block">Nama Pasien:</span>
                                <span class="font-bold text-slate-800 text-base block">{{ $activeQueue->patient->name }}</span>
                                <span class="text-xs text-slate-500 font-mono">NIK: {{ $activeQueue->patient->nik ?? '-' }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-xs text-slate-400 block">Kontak & Terdaftar:</span>
                                <span class="font-medium text-slate-700 block">
                                    <i class="fa-brands fa-whatsapp text-emerald-500 mr-1"></i> {{ $activeQueue->patient->phone ?? '-' }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    Waktu Masuk: {{ $activeQueue->started_at ? $activeQueue->started_at->format('H:i') . ' WIB' : ($activeQueue->called_at ? $activeQueue->called_at->format('H:i') . ' WIB' : '-') }}
                                </span>
                            </div>
                        </div>

                        <!-- Panel Form Selesai Konsultasi / Input Catatan Dokter -->
                        @if($activeQueue->status === 'IN_SERVICE')
                            <form action="{{ route('doctor.queue.finish', $activeQueue) }}" method="POST" class="mt-2 pt-4 border-t border-slate-100">
                                @csrf
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    <i class="fa-solid fa-notes-medical text-indigo-500 mr-1"></i> Catatan Konsultasi / Diagnosa / Resep Singkat (Opsional):
                                </label>
                                <textarea name="doctor_notes" rows="3" placeholder="Tulis catatan diagnosa, instruksi resep obat, atau anjuran kontrol pasien..." class="w-full p-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">{{ old('doctor_notes', $activeQueue->doctor_notes) }}</textarea>

                                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
                                    <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-bold text-sm shadow-lg shadow-emerald-600/25 transition flex items-center justify-center">
                                        <i class="fa-solid fa-circle-check mr-2"></i> Selesaikan Konsultasi Pasien
                                    </button>

                                    <form action="{{ route('doctor.queue.skip', $activeQueue) }}" method="POST" class="w-full sm:w-auto">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menandai pasien ini dilewati?')" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-semibold transition flex items-center justify-center">
                                            <i class="fa-solid fa-forward-step mr-1.5"></i> Lewati Pasien
                                        </button>
                                    </form>
                                </div>
                            </form>
                        @else
                            <!-- Action buttons when status is CALLED -->
                            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center space-x-2">
                                    <!-- Mulai Pemeriksaan -->
                                    <form action="{{ route('doctor.queue.start', $activeQueue) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-emerald-600/20 transition flex items-center">
                                            <i class="fa-solid fa-stethoscope mr-2"></i> Pasien Sudah Masuk (Mulai Periksa)
                                        </button>
                                    </form>

                                    <!-- Panggil Ulang -->
                                    <form action="{{ route('doctor.queue.call', $activeQueue) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-4 py-2.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-xs font-semibold transition flex items-center">
                                            <i class="fa-solid fa-bullhorn mr-1.5"></i> Panggil Ulang
                                        </button>
                                    </form>
                                </div>

                                <!-- Lewati (Skip) -->
                                <form action="{{ route('doctor.queue.skip', $activeQueue) }}" method="POST">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Tandai pasien ini dilewati (tidak hadir)?')" class="px-4 py-2.5 rounded-xl text-rose-600 hover:bg-rose-50 border border-rose-200 text-xs font-semibold transition flex items-center">
                                        <i class="fa-solid fa-user-xmark mr-1.5"></i> Lewati (Tidak Hadir)
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Keadaan Kosong (Belum ada pasien aktif di dalam ruangan) -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-8 text-center shadow-sm">
                    <div class="w-20 h-20 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-3xl mx-auto mb-4">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Ruang Pemeriksaan Siap</h3>
                    <p class="text-sm text-slate-500 max-w-md mx-auto mt-1 mb-6">
                        Saat ini tidak ada pasien di dalam ruang periksa. Panggil pasien berikutnya dari daftar antrian.
                    </p>

                    @if($nextQueue)
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 max-w-md mx-auto mb-6 text-left flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pasien Berikutnya:</span>
                                <span class="text-base font-bold text-slate-900 block">
                                    #{{ str_pad($nextQueue->number, 2, '0', STR_PAD_LEFT) }} - {{ $nextQueue->patient->name }}
                                </span>
                                <span class="text-xs text-slate-500">{{ $nextQueue->patient->insurance_type }} • {{ $nextQueue->patient->phone ?? '-' }}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Antrian #{{ $nextQueue->number }}</span>
                        </div>

                        <form action="{{ route('doctor.queue.call-next') }}" method="POST">
                            @csrf
                            <input type="hidden" name="doctor_id" value="{{ $selectedDoctor->id }}">
                            <button type="submit" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-base shadow-lg shadow-indigo-500/25 active:scale-[0.99] transition inline-flex items-center justify-center">
                                <i class="fa-solid fa-bullhorn mr-2.5 text-lg"></i> Panggil Antrian Berikutnya (#{{ $nextQueue->number }})
                            </button>
                        </form>
                    @else
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 max-w-sm mx-auto text-slate-500 text-xs">
                            <i class="fa-solid fa-check-circle text-emerald-500 mr-1 text-sm"></i> Tidak ada pasien yang sedang menunggu antrian.
                        </div>
                    @endif
                </div>
            @endif

            <!-- Quick Guidelines Box for Doctors -->
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-xs text-slate-600 space-y-1.5">
                <div class="font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-circle-info text-blue-500 mr-1.5"></i> Alur Kerja Praktik Dokter:
                </div>
                <p>1. Tekan <strong>"Panggil Antrian Berikutnya"</strong> untuk memanggil nomor antrian ke layar display ruang tunggu dan mengirim WhatsApp.</p>
                <p>2. Saat pasien masuk ke ruangan, tekan <strong>"Pasien Sudah Masuk (Mulai Periksa)"</strong> agar timer durasi mulai berjalan.</p>
                <p>3. Setelah konsultasi selesai, isi catatan diagnosa (opsional) lalu tekan <strong>"Selesaikan Konsultasi Pasien"</strong>.</p>
            </div>
        </div>

        <!-- Right Column: Queue Lists (Tab Menunggu, Selesai, Dilewati) -->
        <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Tabs Header -->
            <div class="p-3 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
                <div class="flex items-center space-x-1" id="queueTabs">
                    <button onclick="switchTab('waiting')" id="tabBtn-waiting" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-indigo-700 shadow-sm border border-slate-200 transition">
                        Menunggu ({{ $waitingCount }})
                    </button>
                    <button onclick="switchTab('done')" id="tabBtn-done" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                        Selesai ({{ $doneCount }})
                    </button>
                    <button onclick="switchTab('skipped')" id="tabBtn-skipped" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                        Dilewati ({{ $skippedCount }})
                    </button>
                </div>

                <a href="{{ request()->fullUrl() }}" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition" title="Muat Ulang / Refresh">
                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                </a>
            </div>

            <!-- Tab Content: Menunggu -->
            <div id="tabContent-waiting" class="divide-y divide-slate-100 max-h-[550px] overflow-y-auto">
                @forelse($waitingQueues as $q)
                    <div class="p-3.5 hover:bg-slate-50/80 transition flex items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 font-extrabold flex items-center justify-center text-sm border border-blue-100">
                                #{{ str_pad($q->number, 2, '0', STR_PAD_LEFT) }}
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 leading-tight">{{ $q->patient->name }}</h4>
                                <div class="flex items-center space-x-2 text-[11px] text-slate-400 mt-0.5">
                                    <span class="font-medium text-slate-600">{{ $q->patient->insurance_type }}</span>
                                    <span>•</span>
                                    <span>Est: {{ $q->started_at ?? 'Mengantri' }}</span>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('doctor.queue.call', $q) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition border border-indigo-200">
                                <i class="fa-solid fa-phone mr-1"></i> Panggil
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-inbox text-2xl mb-2 block text-slate-300"></i>
                        Tidak ada antrian yang sedang menunggu.
                    </div>
                @endforelse
            </div>

            <!-- Tab Content: Selesai -->
            <div id="tabContent-done" class="divide-y divide-slate-100 max-h-[550px] overflow-y-auto hidden">
                @forelse($doneQueues as $q)
                    <div class="p-3.5 hover:bg-slate-50/80 transition">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                    #{{ str_pad($q->number, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <h4 class="text-xs font-bold text-slate-900">{{ $q->patient->name }}</h4>
                            </div>
                            <span class="text-[11px] text-slate-400 font-mono">
                                {{ $q->finished_at ? $q->finished_at->format('H:i') . ' WIB' : '-' }}
                            </span>
                        </div>

                        @if($q->doctor_notes)
                            <div class="mt-1.5 text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-200/60 font-mono">
                                <i class="fa-solid fa-notes-medical text-slate-400 mr-1"></i> {{ $q->doctor_notes }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-clipboard-check text-2xl mb-2 block text-slate-300"></i>
                        Belum ada pasien yang selesai diperiksa hari ini.
                    </div>
                @endforelse
            </div>

            <!-- Tab Content: Dilewati -->
            <div id="tabContent-skipped" class="divide-y divide-slate-100 max-h-[550px] overflow-y-auto hidden">
                @forelse($skippedQueues as $q)
                    <div class="p-3.5 hover:bg-slate-50/80 transition flex items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 font-extrabold flex items-center justify-center text-sm border border-amber-200">
                                #{{ str_pad($q->number, 2, '0', STR_PAD_LEFT) }}
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 leading-tight">{{ $q->patient->name }}</h4>
                                <span class="text-[11px] text-amber-700">Dilewati (Dipanggil {{ $q->call_count }}x)</span>
                            </div>
                        </div>

                        <form action="{{ route('doctor.queue.recall', $q) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 text-xs font-semibold transition border border-amber-200">
                                <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Panggil Balik
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-check-circle text-2xl mb-2 block text-slate-300"></i>
                        Tidak ada antrian yang dilewati.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

<!-- Script untuk Live Duration Timer & Tab Switcher -->
<script>
    // Tab Switching
    function switchTab(tab) {
        const tabs = ['waiting', 'done', 'skipped'];
        tabs.forEach(t => {
            const btn = document.getElementById(`tabBtn-${t}`);
            const content = document.getElementById(`tabContent-${t}`);
            if (t === tab) {
                btn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-indigo-700 shadow-sm border border-slate-200 transition';
                content.classList.remove('hidden');
            } else {
                btn.className = 'px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition';
                content.classList.add('hidden');
            }
        });
    }

    // Live Stopwatch for IN_SERVICE status
    @if($activeQueue && $activeQueue->status === 'IN_SERVICE' && $activeQueue->started_at)
        const startedAt = new Date("{{ $activeQueue->started_at->toIso8601String() }}").getTime();
        function updateTimer() {
            const now = new Date().getTime();
            const elapsedMs = Math.max(0, now - startedAt);
            const totalSec = Math.floor(elapsedMs / 1000);
            const minutes = Math.floor(totalSec / 60);
            const seconds = totalSec % 60;
            const strMin = String(minutes).padStart(2, '0');
            const strSec = String(seconds).padStart(2, '0');
            const el = document.getElementById('liveDurationTimer');
            if (el) el.textContent = `${strMin}:${strSec}`;
        }
        setInterval(updateTimer, 1000);
        updateTimer();
    @endif
</script>
@endsection
