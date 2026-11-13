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

    <!-- Main Workspace (Grid 12 Cols: 8 Cols Left Active Consultation & EMR + 4 Cols Right Queue List) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Active Patient Consultation Card & Rekam Medis Elektronik (RME) -->
        <div class="lg:col-span-8 space-y-5">
            @if($activeQueue)
                <!-- Card Pasien Sedang Aktif -->
                <div class="bg-white rounded-3xl border-2 {{ $activeQueue->status === 'IN_SERVICE' ? 'border-emerald-500 shadow-emerald-500/10' : 'border-blue-500 shadow-blue-500/10' }} shadow-xl overflow-hidden">
                    <!-- Status Header Banner -->
                    <div class="px-6 py-3.5 {{ $activeQueue->status === 'IN_SERVICE' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white' : 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' }} flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-white animate-ping"></span>
                            <span class="text-xs font-bold uppercase tracking-wider">
                                {{ $activeQueue->status === 'IN_SERVICE' ? 'PASIEN SEDANG DALAM PEMERIKSAAN DOKTER' : 'PASIEN SEDANG DIPANGGIL KE RUANGAN' }}
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
                        <!-- Nomor & Nama Pasien & Tombol Riwayat Medis -->
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between border-b border-slate-100 pb-5 gap-3">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 rounded-2xl bg-slate-900 text-white flex flex-col items-center justify-center shadow-md">
                                    <span class="text-[10px] uppercase font-bold text-slate-400">Antrian</span>
                                    <span class="text-2xl font-black">#{{ str_pad($activeQueue->number, 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-slate-900">{{ $activeQueue->patient->name }}</h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $activeQueue->patient->insurance_type === 'BPJS' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                                            {{ $activeQueue->patient->insurance_type }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 mt-1">
                                        <span><i class="fa-solid fa-id-card mr-1 text-slate-400"></i> NIK: {{ $activeQueue->patient->nik ?? '-' }}</span>
                                        <span><i class="fa-brands fa-whatsapp text-emerald-500 mr-1"></i> {{ $activeQueue->patient->phone ?? '-' }}</span>
                                        @if($activeQueue->patient->gender)
                                            <span>• {{ in_array($activeQueue->patient->gender, ['MALE', 'M']) ? 'Laki-laki' : 'Perempuan' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="openHistoryModal()" class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center shadow-xs">
                                    <i class="fa-solid fa-clock-rotate-left mr-1.5 text-indigo-600"></i> Riwayat Medis
                                    <span class="ml-1.5 px-1.5 py-0.2 rounded-full bg-indigo-200 text-indigo-900 text-[10px]">
                                        {{ $patientHistory->count() }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Keluhan Utama Pasien (Subjektif Awal dari Pendaftaran) -->
                        <div class="bg-amber-50/90 border border-amber-200 rounded-2xl p-4 my-4 text-slate-800">
                            <span class="text-xs font-bold text-amber-900 uppercase tracking-wider flex items-center">
                                <i class="fa-solid fa-head-side-cough text-amber-600 mr-1.5 text-sm"></i> Keluhan Awal Pendaftaran Pasien:
                            </span>
                            <p class="text-sm font-semibold text-amber-950 mt-1">
                                {{ $activeQueue->complaint ?: 'Tidak ada keluhan tertulis saat registrasi (Pemeriksaan rutin / kontrol).' }}
                            </p>
                        </div>

                        <!-- Form Pengisian Rekam Medis Elektronik (Hanya saat status IN_SERVICE) -->
                        @if($activeQueue->status === 'IN_SERVICE')
                            <form action="{{ route('doctor.queue.finish', $activeQueue) }}" method="POST" id="finishConsultationForm" class="space-y-5">
                                @csrf

                                <!-- 1. TANDA-TANDA VITAL & FISIK (OBJEKTIF AWAL) -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center">
                                            <i class="fa-solid fa-heart-pulse text-rose-500 mr-2 text-sm"></i> 1. Tanda-Tanda Vital & Fisik
                                        </h4>
                                        <!-- Live IMT / BMI Indicator -->
                                        <div id="bmiDisplayBadge" class="hidden text-xs px-2.5 py-1 rounded-full font-bold bg-slate-200 text-slate-700 flex items-center">
                                            <span>IMT: </span>
                                            <span id="bmiValue" class="ml-1 font-mono font-black">-</span>
                                            <span id="bmiCategory" class="ml-1.5 px-2 py-0.5 rounded text-[10px] font-semibold"></span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                        <!-- TD Sistolik -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sistolik (mmHg)</label>
                                            <input type="number" name="systolic" placeholder="120" min="40" max="300"
                                                value="{{ old('systolic', $activeEncounter?->medicalRecord?->systolic) }}"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                        <!-- TD Diastolik -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Diastolik (mmHg)</label>
                                            <input type="number" name="diastolic" placeholder="80" min="20" max="200"
                                                value="{{ old('diastolic', $activeEncounter?->medicalRecord?->diastolic) }}"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                        <!-- Nadi -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nadi (x/mnt)</label>
                                            <input type="number" name="heart_rate" placeholder="80" min="30" max="250"
                                                value="{{ old('heart_rate', $activeEncounter?->medicalRecord?->heart_rate) }}"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                        <!-- Suhu -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Suhu (°C)</label>
                                            <input type="number" step="0.1" name="temperature" placeholder="36.5" min="30" max="45"
                                                value="{{ old('temperature', $activeEncounter?->medicalRecord?->temperature) }}"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                        <!-- Berat Badan -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Berat (kg)</label>
                                            <input type="number" step="0.1" name="weight" id="weightInput" placeholder="60" min="1" max="300"
                                                value="{{ old('weight', $activeEncounter?->medicalRecord?->weight) }}"
                                                oninput="calculateLiveBmi()"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                        <!-- Tinggi Badan -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tinggi (cm)</label>
                                            <input type="number" step="0.1" name="height" id="heightInput" placeholder="165" min="20" max="250"
                                                value="{{ old('height', $activeEncounter?->medicalRecord?->height) }}"
                                                oninput="calculateLiveBmi()"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. ANAMNESIS (SUBJEKTIF) -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 space-y-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center">
                                        <i class="fa-solid fa-comments text-blue-500 mr-2 text-sm"></i> 2. Anamnesis (Subjektif)
                                    </h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Keluhan Utama</label>
                                            <textarea name="complaint" rows="2" placeholder="Keluhan utama pasien saat ini..."
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('complaint', $activeEncounter?->medicalRecord?->complaint ?? $activeQueue->complaint) }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Riwayat Penyakit Sekarang (RPS)</label>
                                            <textarea name="history_of_present_illness" rows="2" placeholder="Onset, durasi, karakteristik gejala, faktor pemicu/pereda..."
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('history_of_present_illness', $activeEncounter?->medicalRecord?->history_of_present_illness) }}</textarea>
                                        </div>
                                    </div>

                                    <!-- Riwayat Alergi (High Alert Alert Box) -->
                                    <div class="p-3 rounded-xl bg-rose-50/80 border border-rose-200">
                                        <label class="block text-[11px] font-bold text-rose-800 uppercase tracking-wider mb-1 flex items-center">
                                            <i class="fa-solid fa-triangle-exclamation mr-1.5 text-rose-600"></i> Riwayat Alergi Obat & Makanan
                                        </label>
                                        <input type="text" name="allergy_history" placeholder="Contoh: Alergi Amoksisilin, Penisilin, Makanan Laut (Kosongkan atau isi 'Tidak ada' jika tidak ada)"
                                            value="{{ old('allergy_history', $activeEncounter?->medicalRecord?->allergy_history) }}"
                                            class="w-full px-3 py-1.5 bg-white rounded-lg border border-rose-300 text-xs text-rose-900 font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                                    </div>
                                </div>

                                <!-- 3. PEMERIKSAAN FISIK (OBJEKTIF) -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center mb-2">
                                        <i class="fa-solid fa-user-check text-teal-600 mr-2 text-sm"></i> 3. Pemeriksaan Fisik (Objektif)
                                    </h4>
                                    <textarea name="physical_examination" rows="2" placeholder="Kepala, leher, thoraks (cor/pulmo), abdomen, ekstremitas, status lokalis..."
                                        class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('physical_examination', $activeEncounter?->medicalRecord?->physical_examination) }}</textarea>
                                </div>

                                <!-- 4. DIAGNOSIS & ICD-10 (ASESMEN) -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center">
                                            <i class="fa-solid fa-book-medical text-indigo-600 mr-2 text-sm"></i> 4. Diagnosis Klinis & Kode ICD-10 (Asesmen)
                                        </h4>
                                        <span class="text-[11px] text-slate-500">Master ICD-10 Standar Kemenkes</span>
                                    </div>

                                    <!-- Autocomplete Search Input -->
                                    <div class="relative">
                                        <div class="relative">
                                            <input type="text" id="icd10SearchInput" autocomplete="off"
                                                placeholder="Cari diagnosis: ketik nama penyakit (contoh: Hipertensi, Lambung, ISPA, Batuk) atau kode ICD-10..."
                                                class="w-full pl-9 pr-9 py-2.5 bg-white rounded-xl border border-indigo-200 text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-xs">
                                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-indigo-400 text-xs pointer-events-none"></i>
                                            <span id="icdSearchSpinner" class="hidden absolute right-3 top-3 text-indigo-500 text-xs">
                                                <i class="fa-solid fa-spinner fa-spin"></i>
                                            </span>
                                        </div>

                                        <!-- Autocomplete Dropdown List -->
                                        <div id="icd10DropdownList" class="hidden absolute z-30 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl shadow-xl border border-slate-200 divide-y divide-slate-100">
                                        </div>
                                    </div>

                                    <!-- Selected Primary ICD-10 Display Card -->
                                    <input type="hidden" name="primary_icd10_code" id="primaryIcd10CodeInput"
                                        value="{{ old('primary_icd10_code', $activeEncounter?->medicalRecord?->primary_icd10_code) }}">

                                    <div id="selectedIcd10Container" class="{{ (old('primary_icd10_code', $activeEncounter?->medicalRecord?->primary_icd10_code)) ? '' : 'hidden' }} flex items-center justify-between p-3 rounded-xl bg-emerald-50 border border-emerald-300">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="px-2.5 py-1 rounded-lg font-mono font-bold text-xs bg-emerald-700 text-white shadow-xs" id="selectedIcdCodeBadge">
                                                {{ old('primary_icd10_code', $activeEncounter?->medicalRecord?->primary_icd10_code) }}
                                            </span>
                                            <div>
                                                <span class="text-xs font-bold text-emerald-950 block" id="selectedIcdName">
                                                    {{ $activeEncounter?->medicalRecord?->primaryIcd10?->name_id ?? '' }}
                                                </span>
                                                <span class="text-[10px] text-emerald-700 italic block" id="selectedIcdNameEn">
                                                    {{ $activeEncounter?->medicalRecord?->primaryIcd10?->name_en ?? '' }}
                                                </span>
                                            </div>
                                        </div>
                                        <button type="button" onclick="clearSelectedIcd10()" class="text-rose-600 hover:text-rose-800 text-xs font-semibold px-2.5 py-1 rounded-lg hover:bg-rose-100 transition">
                                            <i class="fa-solid fa-xmark mr-1"></i> Ganti
                                        </button>
                                    </div>

                                    <!-- Quick Popular ICD-10 Chips -->
                                    @if(isset($commonIcd10) && $commonIcd10->isNotEmpty())
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">
                                                <i class="fa-solid fa-bolt text-amber-500 mr-1"></i> Diagnosis Cepat Terpopuler:
                                            </span>
                                            <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
                                                @foreach($commonIcd10 as $icd)
                                                    <button type="button" onclick="selectIcd10('{{ $icd->code }}', '{{ addslashes($icd->name_id) }}', '{{ addslashes($icd->name_en) }}')"
                                                        class="text-[11px] px-2.5 py-1 rounded-lg bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 text-slate-700 transition flex items-center space-x-1 shadow-2xs">
                                                        <span class="font-mono font-bold text-indigo-600">{{ $icd->code }}</span>
                                                        <span class="truncate max-w-[140px]">{{ $icd->name_id }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Diagnosis Notes / Free Text -->
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Catatan Diagnosa / Diferensial</label>
                                        <textarea name="diagnosis_notes" rows="2" placeholder="Catatan klinis diagnosa, derajat keparahan, atau diferensial diagnosa..."
                                            class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('diagnosis_notes', $activeEncounter?->medicalRecord?->diagnosis_notes ?? $activeQueue->doctor_notes) }}</textarea>
                                    </div>
                                </div>

                                <!-- 5. TATA LAKSANA & EDUKASI (PLAN) -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center mb-2">
                                        <i class="fa-solid fa-clipboard-list text-purple-600 mr-2 text-sm"></i> 5. Tata Laksana & Edukasi (Plan)
                                    </h4>
                                    <textarea name="treatment_plan" rows="2" placeholder="Anjuran istirahat, diet khusus, edukasi gaya hidup, jadwal kontrol kembali..."
                                        class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('treatment_plan', $activeEncounter?->medicalRecord?->treatment_plan) }}</textarea>
                                </div>

                                <!-- 6. RESEP OBAT ELEKTRONIK (E-PRESCRIPTION) -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center">
                                            <i class="fa-solid fa-pills text-emerald-600 mr-2 text-sm"></i> 6. Resep Obat Elektronik
                                        </h4>
                                        <button type="button" onclick="addMedicineRow()" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition flex items-center">
                                            <i class="fa-solid fa-plus mr-1"></i> Tambah Obat
                                        </button>
                                    </div>

                                    <!-- Table / Container Baris Obat -->
                                    <div id="medicineRowsContainer" class="space-y-2.5">
                                        <!-- Baris obat akan di-generate via JavaScript -->
                                    </div>
                                </div>

                                <!-- 7. TINDAKAN MEDIS / PROSEDUR KLINIK (OPSIONAL) -->
                                <details class="group bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                                    <summary class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center justify-between cursor-pointer list-none">
                                        <span class="flex items-center">
                                            <i class="fa-solid fa-syringe text-indigo-500 mr-2 text-sm"></i> 7. Tindakan Medis / Prosedur Klinik (Opsional)
                                        </span>
                                        <i class="fa-solid fa-chevron-down text-slate-400 group-open:rotate-180 transition-transform"></i>
                                    </summary>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3 pt-3 border-t border-slate-200">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Tindakan</label>
                                            <input type="text" name="procedure_name" placeholder="Contoh: Nebulisasi, Jahit Luka, Injeksi IM"
                                                value="{{ old('procedure_name', $activeEncounter?->clinicalProcedures?->first()?->procedure_name) }}"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tarif Tindakan (Rp)</label>
                                            <input type="number" name="procedure_cost" placeholder="0" min="0" step="1000"
                                                value="{{ old('procedure_cost', $activeEncounter?->clinicalProcedures?->first()?->cost) }}"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Catatan Tindakan</label>
                                            <input type="text" name="procedure_notes" placeholder="Keterangan bahan habis pakai / lokasi tindakan"
                                                value="{{ old('procedure_notes', $activeEncounter?->clinicalProcedures?->first()?->notes) }}"
                                                class="w-full px-3 py-2 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        </div>
                                    </div>
                                </details>

                                <!-- TOMBOL AKSI AKHIR -->
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-200">
                                    <button type="submit" class="w-full sm:w-auto px-7 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 active:scale-[0.99] text-white font-bold text-sm shadow-lg shadow-emerald-600/25 transition flex items-center justify-center">
                                        <i class="fa-solid fa-circle-check mr-2 text-base"></i> Simpan Rekam Medis & Selesaikan Konsultasi
                                    </button>

                                    <button type="button" onclick="confirmSkipPatient()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-semibold transition flex items-center justify-center">
                                        <i class="fa-solid fa-forward-step mr-1.5"></i> Lewati Pasien Ini
                                    </button>
                                </div>
                            </form>

                            <!-- Form Lewati Pasien (Terpisah dari form utama untuk mencegah nested forms) -->
                            <form id="skipActivePatientForm" action="{{ route('doctor.queue.skip', $activeQueue) }}" method="POST" class="hidden">
                                @csrf
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
                                @if($nextQueue->complaint)
                                    <span class="text-xs text-amber-800 bg-amber-100/70 px-2 py-0.5 rounded border border-amber-200 block mt-1">
                                        <i class="fa-solid fa-head-side-cough text-amber-600 mr-1 text-[11px]"></i> Keluhan: {{ $nextQueue->complaint }}
                                    </span>
                                @endif
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

            <!-- Petunjuk Alur Praktik Dokter -->
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-xs text-slate-600 space-y-1.5">
                <div class="font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-circle-info text-blue-500 mr-1.5"></i> Alur Kerja Praktik & Pengisian RME:
                </div>
                <p>1. Tekan <strong>"Panggil Antrian Berikutnya"</strong> untuk memanggil nomor antrian ke layar display ruang tunggu dan mengirim WhatsApp.</p>
                <p>2. Saat pasien masuk ke ruangan, tekan <strong>"Pasien Sudah Masuk (Mulai Periksa)"</strong> agar timer durasi mulai berjalan dan formulir Rekam Medis (RME) terbuka.</p>
                <p>3. Isi Tanda Vital, Anamnesis SOAP, pilih kode diagnosis ICD-10, dan racik Resep Obat Elektronik.</p>
                <p>4. Tekan <strong>"Simpan Rekam Medis & Selesaikan Konsultasi"</strong> untuk menutup antrian dan menyimpan riwayat pasien.</p>
            </div>
        </div>

        <!-- Right Column: Queue Lists (Tab Menunggu, Selesai, Dilewati) -->
        <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden sticky top-20">
            <!-- Tabs Header -->
            <div class="p-3 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
                <div class="flex items-center space-x-1" id="queueTabs">
                    <button onclick="switchTab('waiting')" id="tabBtn-waiting" class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-white text-indigo-700 shadow-sm border border-slate-200 transition">
                        Menunggu ({{ $waitingCount }})
                    </button>
                    <button onclick="switchTab('done')" id="tabBtn-done" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                        Selesai ({{ $doneCount }})
                    </button>
                    <button onclick="switchTab('skipped')" id="tabBtn-skipped" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                        Dilewati ({{ $skippedCount }})
                    </button>
                </div>

                <a href="{{ request()->fullUrl() }}" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition" title="Muat Ulang / Refresh">
                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                </a>
            </div>

            <!-- Tab Content: Menunggu -->
            <div id="tabContent-waiting" class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
                @forelse($waitingQueues as $q)
                    <div class="p-3.5 hover:bg-slate-50/80 transition flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 font-extrabold flex items-center justify-center text-xs border border-blue-100 shrink-0">
                                #{{ str_pad($q->number, 2, '0', STR_PAD_LEFT) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 leading-tight truncate">{{ $q->patient->name }}</h4>
                                @if($q->complaint)
                                    <div class="text-[11px] text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200/60 mt-0.5 truncate max-w-[160px]" title="{{ $q->complaint }}">
                                        <i class="fa-solid fa-head-side-cough text-amber-600 mr-1 text-[10px]"></i> {{ $q->complaint }}
                                    </div>
                                @endif
                                <div class="flex items-center space-x-1.5 text-[10px] text-slate-400 mt-0.5">
                                    <span class="font-medium text-slate-600">{{ $q->patient->insurance_type }}</span>
                                    <span>•</span>
                                    <span>Est: {{ $q->started_at ?? 'Mengantri' }}</span>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('doctor.queue.call', $q) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition border border-indigo-200">
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
            <div id="tabContent-done" class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto hidden">
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
            <div id="tabContent-skipped" class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto hidden">
                @forelse($skippedQueues as $q)
                    <div class="p-3.5 hover:bg-slate-50/80 transition flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 font-extrabold flex items-center justify-center text-xs border border-amber-200 shrink-0">
                                #{{ str_pad($q->number, 2, '0', STR_PAD_LEFT) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 leading-tight truncate">{{ $q->patient->name }}</h4>
                                <span class="text-[10px] text-amber-700 block">Dilewati (Dipanggil {{ $q->call_count }}x)</span>
                            </div>
                        </div>

                        <form action="{{ route('doctor.queue.recall', $q) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 text-xs font-semibold transition border border-amber-200">
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

<!-- MODAL RIWAYAT REKAM MEDIS PASIEN -->
<div id="patientHistoryModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[85vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Riwayat Kunjungan & Rekam Medis Pasien</h3>
                    <p class="text-xs text-slate-500">{{ $activeQueue?->patient?->name ?? 'Pasien' }} (5 Kunjungan Terakhir)</p>
                </div>
            </div>
            <button type="button" onclick="closeHistoryModal()" class="w-8 h-8 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body Content -->
        <div class="p-6 overflow-y-auto space-y-4 divide-y divide-slate-100">
            @if(isset($patientHistory) && $patientHistory->isNotEmpty())
                @foreach($patientHistory as $history)
                    <div class="pt-4 first:pt-0 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                    {{ $history->encounter?->encounter_date ? \Carbon\Carbon::parse($history->encounter->encounter_date)->format('d M Y') : $history->created_at->format('d M Y') }}
                                </span>
                                <span class="text-xs text-slate-600 font-medium">Dokter: {{ $history->doctor?->name ?? 'Dokter Pemeriksa' }}</span>
                            </div>
                            @if($history->primary_icd10_code)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    ICD-10: {{ $history->primary_icd10_code }}
                                </span>
                            @endif
                        </div>

                        <!-- Tanda Vital Summary -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
                            <div><span class="text-slate-400">TD:</span> <span class="font-bold text-slate-800">{{ $history->blood_pressure }}</span></div>
                            <div><span class="text-slate-400">Nadi:</span> <span class="font-bold text-slate-800">{{ $history->heart_rate ? $history->heart_rate . ' x/mnt' : '-' }}</span></div>
                            <div><span class="text-slate-400">Suhu:</span> <span class="font-bold text-slate-800">{{ $history->temperature ? $history->temperature . ' °C' : '-' }}</span></div>
                            <div><span class="text-slate-400">BB/TB:</span> <span class="font-bold text-slate-800">{{ $history->weight ? $history->weight . 'kg' : '-' }} / {{ $history->height ? $history->height . 'cm' : '-' }} (IMT: {{ $history->bmi ?? '-' }})</span></div>
                        </div>

                        <!-- Keluhan & Diagnosa -->
                        <div class="text-xs space-y-1">
                            <div>
                                <span class="font-bold text-slate-700">Keluhan:</span>
                                <span class="text-slate-600">{{ $history->complaint ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="font-bold text-slate-700">Diagnosa:</span>
                                <span class="text-slate-800 font-semibold">{{ $history->primaryIcd10?->name_id ?? $history->diagnosis_notes ?? 'Pemeriksaan rutin' }}</span>
                            </div>
                            @if($history->treatment_plan)
                                <div>
                                    <span class="font-bold text-slate-700">Tata Laksana:</span>
                                    <span class="text-slate-600">{{ $history->treatment_plan }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="py-12 text-center text-slate-400">
                    <i class="fa-solid fa-notes-medical text-3xl mb-2 text-slate-300"></i>
                    <p class="text-sm font-semibold">Belum Ada Riwayat Kunjungan Sebelumnya</p>
                    <p class="text-xs text-slate-400 mt-1">Ini adalah kunjungan pertama pasien di sistem rekam medis klinik ini.</p>
                </div>
            @endif
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 text-right">
            <button type="button" onclick="closeHistoryModal()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- SCRIPT UNTUK EMR, AUTOCOMPLETE ICD-10, RESEP DINAMIS & TIMER -->
<script>
    // Tab Switching Antrian
    function switchTab(tab) {
        const tabs = ['waiting', 'done', 'skipped'];
        tabs.forEach(t => {
            const btn = document.getElementById(`tabBtn-${t}`);
            const content = document.getElementById(`tabContent-${t}`);
            if (t === tab) {
                btn.className = 'px-2.5 py-1.5 rounded-xl text-xs font-bold bg-white text-indigo-700 shadow-sm border border-slate-200 transition';
                content.classList.remove('hidden');
            } else {
                btn.className = 'px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition';
                content.classList.add('hidden');
            }
        });
    }

    // Modal Riwayat
    function openHistoryModal() {
        document.getElementById('patientHistoryModal').classList.remove('hidden');
    }
    function closeHistoryModal() {
        document.getElementById('patientHistoryModal').classList.add('hidden');
    }

    // Skip Patient Confirmation
    function confirmSkipPatient() {
        if (confirm('Apakah Anda yakin ingin menandai pasien ini dilewati?')) {
            document.getElementById('skipActivePatientForm').submit();
        }
    }

    // Live Stopwatch for IN_SERVICE
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

    // Live BMI / IMT Calculation
    function calculateLiveBmi() {
        const weight = parseFloat(document.getElementById('weightInput')?.value);
        const height = parseFloat(document.getElementById('heightInput')?.value);
        const badge = document.getElementById('bmiDisplayBadge');
        const valEl = document.getElementById('bmiValue');
        const catEl = document.getElementById('bmiCategory');

        if (!badge || !valEl || !catEl) return;

        if (weight > 0 && height > 0) {
            const heightM = height / 100;
            const bmi = (weight / (heightM * heightM)).toFixed(1);
            valEl.textContent = bmi;

            // Klasifikasi WHO Kemenkes Asia Pasifik
            let categoryText = 'Normal';
            let catClass = 'bg-emerald-100 text-emerald-800';
            if (bmi < 18.5) {
                categoryText = 'Kurus / Kurang';
                catClass = 'bg-amber-100 text-amber-800';
            } else if (bmi >= 18.5 && bmi <= 22.9) {
                categoryText = 'Normal';
                catClass = 'bg-emerald-100 text-emerald-800';
            } else if (bmi >= 23 && bmi <= 24.9) {
                categoryText = 'Kelebihan Berat';
                catClass = 'bg-orange-100 text-orange-800';
            } else {
                categoryText = 'Obesitas';
                catClass = 'bg-rose-100 text-rose-800';
            }

            catEl.textContent = categoryText;
            catEl.className = `ml-1.5 px-2 py-0.5 rounded text-[10px] font-bold ${catClass}`;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    // ICD-10 Autocomplete & Selection
    let searchTimeout = null;
    const searchInput = document.getElementById('icd10SearchInput');
    const dropdownList = document.getElementById('icd10DropdownList');
    const spinner = document.getElementById('icdSearchSpinner');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                dropdownList.classList.add('hidden');
                dropdownList.innerHTML = '';
                return;
            }

            spinner?.classList.remove('hidden');
            searchTimeout = setTimeout(() => {
                fetch(`/api/icd10/search?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        spinner?.classList.add('hidden');
                        if (!data || data.length === 0) {
                            dropdownList.innerHTML = `
                                <div class="p-3 text-xs text-slate-400 text-center">
                                    Tidak ditemukan kode ICD-10 yang cocok.
                                </div>
                            `;
                            dropdownList.classList.remove('hidden');
                            return;
                        }

                        let html = '';
                        data.forEach(item => {
                            const codeEsc = escapeHtml(item.code);
                            const nameIdEsc = escapeHtml(item.name_id);
                            const nameEnEsc = escapeHtml(item.name_en || '');
                            html += `
                                <div onclick="selectIcd10('${item.code}', '${escapeQuotes(item.name_id)}', '${escapeQuotes(item.name_en || '')}')" 
                                    class="p-2.5 hover:bg-indigo-50/70 cursor-pointer transition flex items-center justify-between text-xs">
                                    <div>
                                        <div class="font-bold text-slate-800">
                                            <span class="font-mono px-1.5 py-0.5 bg-indigo-100 text-indigo-700 rounded mr-1.5">${codeEsc}</span>
                                            ${nameIdEsc}
                                        </div>
                                        ${nameEnEsc ? `<div class="text-[11px] text-slate-400 italic mt-0.5">${nameEnEsc}</div>` : ''}
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(item.category || '')}</span>
                                </div>
                            `;
                        });
                        dropdownList.innerHTML = html;
                        dropdownList.classList.remove('hidden');
                    })
                    .catch(err => {
                        spinner?.classList.add('hidden');
                        console.error('ICD-10 search error:', err);
                    });
            }, 250);
        });

        // Close dropdown when clicked outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdownList.contains(e.target)) {
                dropdownList.classList.add('hidden');
            }
        });
    }

    function selectIcd10(code, nameId, nameEn) {
        document.getElementById('primaryIcd10CodeInput').value = code;
        document.getElementById('selectedIcdCodeBadge').textContent = code;
        document.getElementById('selectedIcdName').textContent = nameId;
        document.getElementById('selectedIcdNameEn').textContent = nameEn || '';
        document.getElementById('selectedIcd10Container').classList.remove('hidden');
        if (searchInput) searchInput.value = '';
        if (dropdownList) dropdownList.classList.add('hidden');
    }

    function clearSelectedIcd10() {
        document.getElementById('primaryIcd10CodeInput').value = '';
        document.getElementById('selectedIcd10Container').classList.add('hidden');
        if (searchInput) searchInput.focus();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function escapeQuotes(str) {
        if (!str) return '';
        return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    // Dynamic Medicine Rows (e-Prescription)
    let medicineRowIndex = 0;
    const medicineForms = ['Tablet', 'Kapsul', 'Sirup', 'Salep', 'Injeksi', 'Tetes', 'Suspensi', 'Puyer'];

    function addMedicineRow(item = {}) {
        const container = document.getElementById('medicineRowsContainer');
        if (!container) return;

        const idx = medicineRowIndex++;
        const rowDiv = document.createElement('div');
        rowDiv.id = `medRow-${idx}`;
        rowDiv.className = 'p-3 bg-white rounded-xl border border-slate-200/90 shadow-2xs space-y-2';

        const formOptions = medicineForms.map(f => 
            `<option value="${f}" ${(item.form || 'Tablet') === f ? 'selected' : ''}>${f}</option>`
        ).join('');

        rowDiv.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs">
                <!-- Nama Obat -->
                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Obat</label>
                    <input type="text" name="medicines[${idx}][name]" value="${escapeHtml(item.name || '')}" placeholder="Contoh: Paracetamol"
                        class="w-full px-2.5 py-1.5 bg-slate-50 rounded-lg border border-slate-200 text-xs font-semibold focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Dosis / Sediaan -->
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Dosis</label>
                    <input type="text" name="medicines[${idx}][dosage]" value="${escapeHtml(item.dosage || '')}" placeholder="500 mg"
                        class="w-full px-2.5 py-1.5 bg-slate-50 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Bentuk Sediaan -->
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Bentuk</label>
                    <select name="medicines[${idx}][form]" class="w-full px-2 py-1.5 bg-slate-50 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        ${formOptions}
                    </select>
                </div>

                <!-- Frekuensi / Aturan Pakai -->
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Aturan Pakai</label>
                    <input type="text" name="medicines[${idx}][frequency]" value="${escapeHtml(item.frequency || '3x1 sehari')}" placeholder="3x1 sehari"
                        class="w-full px-2.5 py-1.5 bg-slate-50 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Jumlah (Qty) -->
                <div class="sm:col-span-1">
                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Qty</label>
                    <input type="number" name="medicines[${idx}][quantity]" value="${item.quantity || 10}" min="1" max="500"
                        class="w-full px-2 py-1.5 bg-slate-50 rounded-lg border border-slate-200 text-xs text-center font-bold focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Tombol Hapus -->
                <div class="sm:col-span-1 flex items-end justify-center">
                    <button type="button" onclick="removeMedicineRow(${idx})" class="w-full py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition text-xs font-semibold" title="Hapus obat">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>

            <!-- Petunjuk Khusus -->
            <div>
                <input type="text" name="medicines[${idx}][instructions]" value="${escapeHtml(item.instructions || '')}" placeholder="Petunjuk khusus: sesudah makan / bila demam / habiskan..."
                    class="w-full px-2.5 py-1 bg-slate-50 rounded-lg border border-slate-200 text-[11px] text-slate-600 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
            </div>
        `;

        container.appendChild(rowDiv);
    }

    function removeMedicineRow(idx) {
        const row = document.getElementById(`medRow-${idx}`);
        if (row) row.remove();
    }

    // Initialize BMI & default medicine rows on DOM loaded
    document.addEventListener('DOMContentLoaded', function() {
        calculateLiveBmi();

        // Render existing prescription items if available, or 1 empty row
        @if(isset($activeEncounter) && $activeEncounter?->prescriptions?->isNotEmpty() && $activeEncounter->prescriptions->first()->items->isNotEmpty())
            @foreach($activeEncounter->prescriptions->first()->items as $med)
                addMedicineRow({
                    name: "{{ addslashes($med->medicine_name) }}",
                    dosage: "{{ addslashes($med->dosage ?? '') }}",
                    form: "{{ addslashes($med->form ?? 'Tablet') }}",
                    frequency: "{{ addslashes($med->frequency ?? '') }}",
                    quantity: {{ (int) $med->quantity }},
                    instructions: "{{ addslashes($med->instructions ?? '') }}"
                });
            @endforeach
        @else
            // Row kosong default
            addMedicineRow();
        @endif
    });
</script>
@endsection
