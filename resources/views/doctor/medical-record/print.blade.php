<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Medis - {{ $encounter->patient->name }} - {{ $clinic->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                font-size: 11pt;
            }
            .page-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased p-4 sm:p-8">

    <!-- Action Bar (Hidden on print) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('doctor.medical-record.show', $encounter) }}" class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition flex items-center">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke RME
        </a>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center">
                <i class="fa-solid fa-print mr-2"></i> Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- Official Paper Container -->
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-3xl sm:shadow-lg border border-slate-200 text-slate-900 page-break">
        
        <!-- Kop Surat Klinik -->
        <div class="border-b-2 border-slate-900 pb-4 mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-indigo-700 text-white flex items-center justify-center text-2xl font-bold">
                    <i class="fa-solid fa-hospital"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black uppercase tracking-tight text-slate-900">{{ $clinic->name }}</h1>
                    <p class="text-xs text-slate-600">{{ $clinic->address ?? 'Alamat Klinik' }}</p>
                    <p class="text-xs text-slate-500 font-mono">Telp: {{ $clinic->phone ?? '-' }} | Sistem Rekam Medis Elektronik Terintegrasi</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Formulir Rekam Medis</span>
                <span class="text-sm font-black text-slate-800">RESUME RAWAT JALAN</span>
                <span class="text-xs font-mono text-slate-500 block">ID: {{ substr($encounter->id, 0, 8) }}</span>
            </div>
        </div>

        <!-- Demografi Pasien & Info Kunjungan -->
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 mb-6 text-xs">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Nama Pasien:</span>
                    <strong class="text-sm text-slate-900 block mt-0.5">{{ $encounter->patient->name }}</strong>
                    <span class="text-slate-500 font-mono">{{ $encounter->patient->nik ?? 'NIK: -' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Penjamin / Asuransi:</span>
                    <strong class="text-slate-800 block mt-0.5">{{ $encounter->patient->insurance_type }}</strong>
                    <span class="text-slate-500 font-mono">{{ $encounter->patient->insurance_number ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Tgl. Kunjungan / Jam:</span>
                    <strong class="text-slate-800 block mt-0.5">{{ $encounter->encounter_date ? \Carbon\Carbon::parse($encounter->encounter_date)->format('d/m/Y') : '-' }}</strong>
                    <span class="text-slate-500">{{ $encounter->start_time ? $encounter->start_time->format('H:i') . ' WIB' : '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Dokter Pemeriksa:</span>
                    <strong class="text-slate-800 block mt-0.5">{{ $encounter->doctor->name }}</strong>
                    <span class="text-slate-500">{{ $encounter->doctor->specialty }}</span>
                </div>
            </div>
        </div>

        <!-- Tanda Vital -->
        @if($medicalRecord)
            <div class="mb-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-200 pb-1 mb-2">
                    Tanda-Tanda Vital & Pengukuran Fisik
                </h3>
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-xs">
                    <div class="p-2 border border-slate-200 rounded-lg">
                        <span class="text-[10px] text-slate-400 block">TD:</span>
                        <strong class="font-mono">{{ $medicalRecord->blood_pressure ?? '-' }} mmHg</strong>
                    </div>
                    <div class="p-2 border border-slate-200 rounded-lg">
                        <span class="text-[10px] text-slate-400 block">Nadi:</span>
                        <strong class="font-mono">{{ $medicalRecord->heart_rate ?? '-' }} x/mnt</strong>
                    </div>
                    <div class="p-2 border border-slate-200 rounded-lg">
                        <span class="text-[10px] text-slate-400 block">Napas:</span>
                        <strong class="font-mono">{{ $medicalRecord->respiratory_rate ?? '-' }} x/mnt</strong>
                    </div>
                    <div class="p-2 border border-slate-200 rounded-lg">
                        <span class="text-[10px] text-slate-400 block">Suhu:</span>
                        <strong class="font-mono">{{ $medicalRecord->temperature ?? '-' }} °C</strong>
                    </div>
                    <div class="p-2 border border-slate-200 rounded-lg">
                        <span class="text-[10px] text-slate-400 block">BB / TB:</span>
                        <strong class="font-mono">{{ $medicalRecord->weight ?? '-' }}kg / {{ $medicalRecord->height ?? '-' }}cm</strong>
                    </div>
                    <div class="p-2 border border-slate-200 rounded-lg">
                        <span class="text-[10px] text-slate-400 block">IMT:</span>
                        <strong class="font-mono">{{ $medicalRecord->bmi ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            <!-- SOAP -->
            <div class="space-y-4 mb-6 text-xs">
                <!-- Subjektif -->
                <div class="border border-slate-200 rounded-xl p-3">
                    <span class="font-bold uppercase tracking-wider text-blue-800 text-[10px] block mb-1">Anamnesis (Subjektif)</span>
                    <p><strong>Keluhan Utama:</strong> {{ $medicalRecord->complaint ?: '-' }}</p>
                    @if($medicalRecord->history_of_present_illness)
                        <p class="mt-1"><strong>RPS:</strong> {{ $medicalRecord->history_of_present_illness }}</p>
                    @endif
                    @if($medicalRecord->allergy_history)
                        <p class="mt-1 text-rose-700"><strong>Riwayat Alergi:</strong> {{ $medicalRecord->allergy_history }}</p>
                    @endif
                </div>

                <!-- Objektif -->
                <div class="border border-slate-200 rounded-xl p-3">
                    <span class="font-bold uppercase tracking-wider text-teal-800 text-[10px] block mb-1">Pemeriksaan Fisik (Objektif)</span>
                    <p class="whitespace-pre-line">{{ $medicalRecord->physical_examination ?: 'Pemeriksaan fisik dalam batas normal.' }}</p>
                </div>

                <!-- Asesmen / Diagnosis -->
                <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                    <span class="font-bold uppercase tracking-wider text-indigo-800 text-[10px] block mb-1">Diagnosis Klinis (Asesmen)</span>
                    @if($medicalRecord->primary_icd10_code)
                        <div class="flex items-center space-x-2 my-1">
                            <span class="px-2 py-0.5 rounded bg-slate-900 text-white font-mono font-bold">{{ $medicalRecord->primary_icd10_code }}</span>
                            <strong class="text-sm">{{ $medicalRecord->primaryIcd10?->name_id }}</strong>
                            <span class="text-slate-500 italic">({{ $medicalRecord->primaryIcd10?->name_en }})</span>
                        </div>
                    @endif
                    @if($medicalRecord->diagnosis_notes)
                        <p class="mt-1"><strong>Catatan Diagnosa:</strong> {{ $medicalRecord->diagnosis_notes }}</p>
                    @endif
                </div>

                <!-- Plan -->
                <div class="border border-slate-200 rounded-xl p-3">
                    <span class="font-bold uppercase tracking-wider text-purple-800 text-[10px] block mb-1">Tata Laksana & Edukasi (Plan)</span>
                    <p class="whitespace-pre-line">{{ $medicalRecord->treatment_plan ?: 'Istirahat cukup dan pola hidup sehat.' }}</p>
                </div>
            </div>
        @endif

        <!-- Resep Obat -->
        @if($encounter->prescriptions->isNotEmpty() && $encounter->prescriptions->first()->items->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-200 pb-1 mb-2">
                    Terapi Obat (Resep Elektronik)
                </h3>
                <table class="w-full text-xs text-left border border-slate-200 rounded-lg overflow-hidden">
                    <thead class="bg-slate-100 font-semibold text-[10px] uppercase">
                        <tr>
                            <th class="p-2 border-b">Nama Obat</th>
                            <th class="p-2 border-b">Dosis / Bentuk</th>
                            <th class="p-2 border-b">Aturan Pakai</th>
                            <th class="p-2 border-b text-center">Jumlah</th>
                            <th class="p-2 border-b">Petunjuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($encounter->prescriptions->first()->items as $item)
                            <tr>
                                <td class="p-2 font-bold">{{ $item->medicine_name }}</td>
                                <td class="p-2">{{ $item->dosage ?? '-' }} ({{ $item->form ?? 'Tablet' }})</td>
                                <td class="p-2 font-semibold">{{ $item->frequency }}</td>
                                <td class="p-2 text-center font-bold">{{ $item->quantity }}</td>
                                <td class="p-2 italic text-slate-500">{{ $item->instructions ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Addendum jika ada -->
        @if($medicalRecord && $medicalRecord->addendums->isNotEmpty())
            <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                <h4 class="font-bold uppercase tracking-wider text-[10px] text-amber-900 border-b border-slate-200 pb-1">
                    Catatan Tambahan (Addendum Medis Resmi)
                </h4>
                @foreach($medicalRecord->addendums as $add)
                    <div class="text-[11px] border-b border-slate-200/50 pb-2 last:border-b-0">
                        <div class="flex justify-between font-semibold text-slate-700">
                            <span>Oleh: {{ $add->author?->name ?? 'Dokter' }} (Alasan: {{ $add->reason }})</span>
                            <span class="font-mono text-slate-400">{{ $add->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="font-mono text-slate-600 mt-0.5">{{ $add->notes }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Tanda Tangan Dokter & Validasi Digital -->
        <div class="mt-8 pt-4 border-t border-slate-200 flex justify-between items-end text-xs">
            <div class="space-y-1 text-[10px] text-slate-400">
                <p>Dokumen ini diterbitkan secara elektronik oleh Sistem Kedokter.</p>
                <p>Status Dokumen: <strong>{{ $encounter->isLocked() ? 'TERKUNCI (LOCKED / VALID)' : 'DRAFT / BELUM DIKUNCI' }}</strong></p>
                <p>Waktu Cetak: {{ now()->format('d/m/Y H:i:s') }} WIB</p>
            </div>

            <div class="text-center w-56">
                <p class="text-xs text-slate-600 mb-1">Dokter Pemeriksa,</p>
                @if($encounter->isLocked())
                    <div class="my-2 py-2 px-3 border border-emerald-300 bg-emerald-50 rounded-xl text-emerald-800 text-[10px] font-mono leading-tight">
                        <i class="fa-solid fa-signature text-sm block mb-1 text-emerald-600"></i>
                        <span>DIGITALLY SIGNED</span><br>
                        <span>{{ $encounter->locked_at ? $encounter->locked_at->format('d/m/Y H:i') : '' }}</span>
                    </div>
                @else
                    <div class="h-14"></div>
                @endif
                <p class="font-bold text-slate-900 border-b border-slate-400 pb-0.5">{{ $encounter->doctor->name }}</p>
                <p class="text-[10px] text-slate-500 font-mono mt-0.5">SIP: {{ $encounter->doctor->sip_number ?? '-' }}</p>
            </div>
        </div>

    </div>

</body>
</html>

