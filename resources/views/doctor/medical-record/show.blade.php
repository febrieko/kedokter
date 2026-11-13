@extends('layouts.app')

@section('title', 'Rekam Medis - ' . ($encounter->patient->name ?? 'Pasien'))

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12">

    <!-- Top Action Breadcrumb Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="flex items-center space-x-3">
            <a href="{{ route('doctor.queue.index') }}" class="p-2 rounded-xl text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 transition" title="Kembali ke Ruang Dokter">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-file-waveform text-indigo-600 mr-2"></i> Rekam Medis Elektronik (RME)
                </h2>
                <p class="text-xs text-slate-500">ID Kunjungan: <span class="font-mono">{{ $encounter->id }}</span></p>
            </div>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('doctor.medical-record.print', $encounter) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center shadow-2xs">
                <i class="fa-solid fa-print mr-1.5 text-slate-500"></i> Cetak Resume Medis
            </a>

            @if(!$encounter->isLocked())
                <form action="{{ route('doctor.medical-record.lock', $encounter) }}" method="POST">
                    @csrf
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin MENGUNCI dan MENANDATANGANI rekam medis ini? Setelah dikunci, data utama menjadi read-only dan koreksi hanya dapat dilakukan melalui Addendum.')"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 active:scale-[0.99] transition flex items-center">
                        <i class="fa-solid fa-file-signature mr-1.5"></i> Kunci & Tanda Tangani (Sign & Lock)
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Status Banner: LOCKED vs COMPLETED -->
    @if($encounter->isLocked())
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-500 rounded-3xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-emerald-600/20">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-600 text-white uppercase tracking-wider">
                            TERKUNCI & DITANDATANGANI SECARA DIGITAL (LOCKED)
                        </span>
                    </div>
                    <p class="text-xs text-emerald-950 font-medium mt-1">
                        Rekam medis ini telah dikunci pada <strong>{{ $encounter->locked_at ? $encounter->locked_at->format('d M Y, H:i') : '-' }} WIB</strong>
                        oleh <strong>{{ $encounter->lockedByUser?->name ?? $encounter->doctor?->name ?? 'Dokter Pemeriksa' }}</strong>.
                    </p>
                    <p class="text-[11px] text-emerald-700 mt-0.5">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Standar Permenkes No. 24/2022: Data berstatus Read-Only. Perbaikan atau catatan medis susulan dilakukan melalui fitur <strong>Addendum</strong> di bagian bawah.
                    </p>
                </div>
            </div>
            <span class="self-start md:self-center px-3 py-1.5 rounded-xl bg-white border border-emerald-300 text-emerald-800 text-xs font-mono font-bold shadow-2xs whitespace-nowrap">
                <i class="fa-solid fa-signature mr-1 text-emerald-600"></i> Signed & Valid
            </span>
        </div>
    @else
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-400 rounded-3xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-amber-500/20">
                    <i class="fa-solid fa-lock-open"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-500 text-white uppercase tracking-wider">
                            BELUM DIKUNCI (STATUS: COMPLETED)
                        </span>
                    </div>
                    <p class="text-xs text-amber-950 font-medium mt-1">
                        Pemeriksaan klinis telah selesai. Klik tombol <strong>"Kunci & Tanda Tangani"</strong> untuk memvalidasi catatan secara permanen sebelum arsip atau pembayaran kasir ditutup.
                    </p>
                </div>
            </div>

            <form action="{{ route('doctor.medical-record.lock', $encounter) }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" onclick="return confirm('Kunci rekam medis sekarang?')"
                    class="px-5 py-2.5 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-600/25 active:scale-[0.99] transition flex items-center">
                    <i class="fa-solid fa-file-signature mr-2"></i> Kunci Sekarang
                </button>
            </form>
        </div>
    @endif

    <!-- Patient & Encounter Profile Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Patient Info -->
            <div class="md:col-span-8 flex items-start space-x-4 border-b md:border-b-0 md:border-r border-slate-100 pb-5 md:pb-0 md:pr-6">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-black shrink-0">
                    {{ strtoupper(substr($encounter->patient->name, 0, 1)) }}
                </div>
                <div class="space-y-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <h3 class="text-lg font-black text-slate-900 truncate">{{ $encounter->patient->name }}</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $encounter->patient->insurance_type === 'BPJS' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                            {{ $encounter->patient->insurance_type }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-0.5 text-xs text-slate-500">
                        <span><strong class="text-slate-700">NIK:</strong> {{ $encounter->patient->nik ?? '-' }}</span>
                        <span><strong class="text-slate-700">No. HP:</strong> {{ $encounter->patient->phone ?? '-' }}</span>
                        <span><strong class="text-slate-700">Jenis Kelamin:</strong> {{ in_array($encounter->patient->gender, ['MALE', 'M']) ? 'Laki-laki' : ($encounter->patient->gender ? 'Perempuan' : '-') }}</span>
                        <span><strong class="text-slate-700">Asuransi:</strong> {{ $encounter->patient->insurance_number ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Encounter Info -->
            <div class="md:col-span-4 space-y-1.5 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-400">Tanggal Kunjungan:</span>
                    <span class="font-bold text-slate-800">{{ $encounter->encounter_date ? \Carbon\Carbon::parse($encounter->encounter_date)->format('d F Y') : '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Dokter Pemeriksa:</span>
                    <span class="font-bold text-slate-800">{{ $encounter->doctor->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Klinik:</span>
                    <span class="font-medium text-slate-800">{{ $clinic->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">No. Antrian:</span>
                    <span class="font-bold text-indigo-700 font-mono">#{{ $encounter->queue?->number ? str_pad($encounter->queue->number, 2, '0', STR_PAD_LEFT) : '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tanda Vital & Fisik Grid -->
    @if($medicalRecord)
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-heart-pulse text-rose-500 mr-2 text-sm"></i> Tanda-Tanda Vital & Pengukuran Fisik
            </h4>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tekanan Darah</span>
                    <span class="text-base font-extrabold text-slate-900 mt-0.5 block">{{ $medicalRecord->blood_pressure ?? '-' }}</span>
                    <span class="text-[10px] text-slate-400">mmHg</span>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Denyut Nadi</span>
                    <span class="text-base font-extrabold text-slate-900 mt-0.5 block">{{ $medicalRecord->heart_rate ?? '-' }}</span>
                    <span class="text-[10px] text-slate-400">x/menit</span>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Laju Napas</span>
                    <span class="text-base font-extrabold text-slate-900 mt-0.5 block">{{ $medicalRecord->respiratory_rate ?? '-' }}</span>
                    <span class="text-[10px] text-slate-400">x/menit</span>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Suhu Tubuh</span>
                    <span class="text-base font-extrabold text-slate-900 mt-0.5 block">{{ $medicalRecord->temperature ?? '-' }}</span>
                    <span class="text-[10px] text-slate-400">°Celcius</span>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Berat / Tinggi</span>
                    <span class="text-base font-extrabold text-slate-900 mt-0.5 block">{{ $medicalRecord->weight ?? '-' }}kg / {{ $medicalRecord->height ?? '-' }}cm</span>
                    <span class="text-[10px] text-slate-400">Antropometri</span>
                </div>

                <div class="p-3 rounded-2xl bg-indigo-50 border border-indigo-200">
                    <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">IMT / BMI</span>
                    <span class="text-base font-extrabold text-indigo-950 mt-0.5 block">{{ $medicalRecord->bmi ?? '-' }}</span>
                    <span class="text-[10px] font-semibold text-indigo-700">
                        @if($medicalRecord->bmi)
                            {{ $medicalRecord->bmi < 18.5 ? 'Kurang' : ($medicalRecord->bmi <= 22.9 ? 'Normal' : ($medicalRecord->bmi <= 24.9 ? 'Berlebih' : 'Obesitas')) }}
                        @else
                            -
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Catatan Medis SOAP (Subjektif, Objektif, Asesmen, Plan) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-6">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-notes-medical text-indigo-600 mr-2 text-sm"></i> Catatan Klinis SOAP (Permenkes Standar)
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- S: Subjektif -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-700 flex items-center">
                        <i class="fa-solid fa-comments mr-1.5"></i> Subjektif (Anamnesis)
                    </span>
                    <div class="text-xs text-slate-700 space-y-2">
                        <div>
                            <strong class="text-slate-900 block">Keluhan Utama:</strong>
                            <p class="mt-0.5 text-slate-600">{{ $medicalRecord->complaint ?: 'Tidak ada keluhan spesifik tertulis.' }}</p>
                        </div>
                        @if($medicalRecord->history_of_present_illness)
                            <div>
                                <strong class="text-slate-900 block">Riwayat Penyakit Sekarang (RPS):</strong>
                                <p class="mt-0.5 text-slate-600">{{ $medicalRecord->history_of_present_illness }}</p>
                            </div>
                        @endif
                        @if($medicalRecord->allergy_history)
                            <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900">
                                <strong class="block text-rose-800 text-[11px] uppercase tracking-wider">
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Riwayat Alergi:
                                </strong>
                                <p class="font-semibold text-xs mt-0.5">{{ $medicalRecord->allergy_history }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- O: Objektif -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-700 flex items-center">
                        <i class="fa-solid fa-user-check mr-1.5"></i> Objektif (Pemeriksaan Fisik)
                    </span>
                    <div class="text-xs text-slate-700">
                        <p class="whitespace-pre-line">{{ $medicalRecord->physical_examination ?: 'Pemeriksaan fisik dalam batas normal.' }}</p>
                    </div>
                </div>

                <!-- A: Asesmen (Diagnosis ICD-10) -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 flex items-center">
                        <i class="fa-solid fa-book-medical mr-1.5"></i> Asesmen (Diagnosis Klinis)
                    </span>
                    <div class="space-y-2 text-xs">
                        @if($medicalRecord->primary_icd10_code)
                            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-300 flex items-start space-x-2.5">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-xs bg-emerald-700 text-white shrink-0">
                                    {{ $medicalRecord->primary_icd10_code }}
                                </span>
                                <div>
                                    <span class="font-bold text-emerald-950 block">{{ $medicalRecord->primaryIcd10?->name_id }}</span>
                                    <span class="text-[11px] text-emerald-700 italic block">{{ $medicalRecord->primaryIcd10?->name_en }}</span>
                                </div>
                            </div>
                        @endif

                        @if($medicalRecord->diagnosis_notes)
                            <div>
                                <strong class="text-slate-900 block">Catatan Diagnosa / Diferensial:</strong>
                                <p class="mt-0.5 text-slate-600">{{ $medicalRecord->diagnosis_notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- P: Plan (Tata Laksana & Edukasi) -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-purple-700 flex items-center">
                        <i class="fa-solid fa-clipboard-list mr-1.5"></i> Plan (Tata Laksana & Edukasi)
                    </span>
                    <div class="text-xs text-slate-700">
                        <p class="whitespace-pre-line">{{ $medicalRecord->treatment_plan ?: 'Edukasi pola hidup sehat dan anjuran istirahat.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Resep Obat Elektronik & Tindakan Medis Grid -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <!-- Resep Obat -->
        <div class="md:col-span-8 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-pills text-emerald-600 mr-2 text-sm"></i> Resep Obat Elektronik (e-Prescription)
            </h4>

            @if($encounter->prescriptions->isNotEmpty() && $encounter->prescriptions->first()->items->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Nama Obat</th>
                                <th class="py-2.5 px-3">Dosis / Bentuk</th>
                                <th class="py-2.5 px-3">Aturan Pakai</th>
                                <th class="py-2.5 px-3 text-center">Jumlah</th>
                                <th class="py-2.5 px-3">Instruksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($encounter->prescriptions->first()->items as $item)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="py-2.5 px-3 font-bold text-slate-900">{{ $item->medicine_name }}</td>
                                    <td class="py-2.5 px-3 text-slate-600">{{ $item->dosage ?? '-' }} ({{ $item->form ?? 'Tablet' }})</td>
                                    <td class="py-2.5 px-3 font-semibold text-indigo-700">{{ $item->frequency }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ $item->quantity }}</td>
                                    <td class="py-2.5 px-3 text-slate-500 italic">{{ $item->instructions ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 text-center text-slate-400 text-xs">
                    <i class="fa-solid fa-prescription-bottle text-2xl mb-1 text-slate-300 block"></i>
                    Tidak ada resep obat tertulis pada kunjungan ini.
                </div>
            @endif
        </div>

        <!-- Tindakan Medis -->
        <div class="md:col-span-4 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center border-b border-slate-100 pb-3">
                <i class="fa-solid fa-syringe text-indigo-600 mr-2 text-sm"></i> Tindakan Medis / Prosedur
            </h4>

            @if($encounter->clinicalProcedures->isNotEmpty())
                <div class="space-y-2">
                    @foreach($encounter->clinicalProcedures as $proc)
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $proc->procedure_name }}</span>
                                <span class="font-mono font-bold text-emerald-700">Rp {{ number_format($proc->cost, 0, ',', '.') }}</span>
                            </div>
                            @if($proc->notes)
                                <p class="text-[11px] text-slate-500">{{ $proc->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-slate-400 text-xs">
                    <i class="fa-solid fa-stethoscope text-2xl mb-1 text-slate-300 block"></i>
                    Tidak ada tindakan klinis tambahan.
                </div>
            @endif
        </div>
    </div>

    <!-- SEKSI ADDENDUM MEDIS (CATATAN TAMBAHAN / KOREKSI RESMI) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 gap-2">
            <div>
                <h4 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-clock-rotate-left text-indigo-600 mr-2"></i> Riwayat Addendum Medis (Catatan Tambahan)
                </h4>
                <p class="text-xs text-slate-500">
                    Jejak audit medikolegal sesuai Permenkes No. 24/2022 Pasal 21 untuk revisi atau data susulan pasca penguncian rekam medis.
                </p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                {{ $medicalRecord?->addendums?->count() ?? 0 }} Addendum
            </span>
        </div>

        <!-- Daftar Addendum yang Sudah Dicatat -->
        @if($medicalRecord && $medicalRecord->addendums->isNotEmpty())
            <div class="space-y-3">
                @foreach($medicalRecord->addendums as $addendum)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 border-b border-slate-200/60 pb-2">
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800">
                                    {{ $addendum->author?->name ?? 'Dokter Pemeriksa' }}
                                </span>
                                <span class="text-slate-400 font-mono text-[11px]">
                                    {{ $addendum->created_at->format('d/m/Y H:i') }} WIB
                                </span>
                            </div>
                            <span class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded border border-amber-200">
                                Alasan: {{ $addendum->reason }}
                            </span>
                        </div>
                        <p class="text-slate-700 whitespace-pre-line font-mono text-xs leading-relaxed">
                            {{ $addendum->notes }}
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 rounded-2xl bg-slate-50/60 border border-dashed border-slate-200 text-center text-xs text-slate-400">
                <i class="fa-solid fa-file-circle-check text-2xl mb-1 text-slate-300 block"></i>
                Belum ada catatan tambahan (Addendum) pada rekam medis ini.
            </div>
        @endif

        <!-- Formulir Tambah Addendum (Hanya aktif jika status LOCKED) -->
        @if($encounter->isLocked())
            <div class="mt-6 pt-5 border-t border-slate-100">
                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-3 flex items-center">
                    <i class="fa-solid fa-plus-circle text-emerald-600 mr-1.5"></i> Buat Catatan Tambahan (Addendum Baru)
                </h5>

                <form action="{{ route('doctor.medical-record.addendum', $encounter) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Alasan Addendum / Penambahan Catatan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="reason" required
                            placeholder="Contoh: Hasil lab darah lengkap susulan / Koreksi dosis obat / Keterangan tambahan pasien"
                            class="w-full px-3.5 py-2.5 bg-white rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Isi Catatan Tambahan (Addendum) <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="notes" rows="3" required
                            placeholder="Tuliskan catatan medis tambahan atau koreksi dengan jelas..."
                            class="w-full p-3.5 bg-white rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 active:scale-[0.99] transition flex items-center">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Catatan Addendum
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500 flex items-center">
                <i class="fa-solid fa-circle-info text-blue-500 mr-2 text-sm"></i>
                Fitur Addendum akan aktif secara otomatis setelah rekam medis ini dikunci (LOCKED).
            </div>
        @endif
    </div>

</div>
@endsection
