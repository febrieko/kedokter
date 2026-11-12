@extends('layouts.app')

@section('title', 'Pengaturan WhatsApp & Template - Admin')

@section('content')
<div class="space-y-8">

    <!-- Header bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 flex items-center">
            <i class="fa-brands fa-whatsapp text-emerald-600 mr-2.5 text-2xl"></i> Pengaturan WhatsApp Gateway & Template Pesan
        </h2>
        <p class="text-xs text-slate-500 mt-1">Kelola koneksi provider WhatsApp (Fonnte / WAHA) dan sesuaikan isi pesan otomatis untuk pasien</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Column 1: WhatsApp Gateway Settings & Test Send (5 cols) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Card Gateway Config -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-900 text-sm flex items-center mb-4">
                    <i class="fa-solid fa-plug text-blue-600 mr-2"></i> Konfigurasi Provider WhatsApp
                </h3>

                <form action="{{ route('admin.whatsapp.config') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Provider Gateway *</label>
                        <select name="whatsapp_provider" id="providerSelect" onchange="toggleProviderFields(this.value)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="FONNTE" {{ $clinic->whatsapp_provider === 'FONNTE' ? 'selected' : '' }}>Fonnte (SaaS Multi-Device)</option>
                            <option value="WAHA" {{ $clinic->whatsapp_provider === 'WAHA' ? 'selected' : '' }}>WAHA (WhatsApp HTTP API Self-Hosted)</option>
                        </select>
                    </div>

                    <!-- Fonnte Fields -->
                    <div id="fonnteSection" class="{{ $clinic->whatsapp_provider === 'WAHA' ? 'hidden' : '' }} space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Fonnte API Token *</label>
                            <input type="password" name="fonnte_token" value="{{ $clinic->whatsapp_config['token'] ?? '' }}" placeholder="Masukkan token akun Fonnte" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[11px] text-slate-400">Didapatkan dari dashboard Fonnte -> Device Menu.</span>
                        </div>
                    </div>

                    <!-- WAHA Fields -->
                    <div id="wahaSection" class="{{ $clinic->whatsapp_provider !== 'WAHA' ? 'hidden' : '' }} space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">WAHA Base URL *</label>
                            <input type="text" name="waha_base_url" value="{{ $clinic->whatsapp_config['base_url'] ?? 'http://localhost:3000' }}" placeholder="http://localhost:3000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">WAHA API Key (Opsional)</label>
                            <input type="password" name="waha_api_key" value="{{ $clinic->whatsapp_config['api_key'] ?? '' }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Session Name</label>
                            <input type="text" name="waha_session" value="{{ $clinic->whatsapp_config['session'] ?? 'default' }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Webhook URL Info -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                        <strong class="text-slate-700 block">Webhook URL Klinik Anda:</strong>
                        <div class="font-mono text-blue-700 break-all select-all p-2 rounded bg-white border border-slate-200">
                            {{ url('/api/webhook/whatsapp/' . $clinic->slug) }}
                        </div>
                        <span class="text-slate-400 text-[10px] block">Masukkan URL di atas ke webhook settings di provider Fonnte/WAHA Anda.</span>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm shadow-md shadow-blue-500/20 transition">
                        Simpan Konfigurasi
                    </button>
                </form>
            </div>

            <!-- Card Test Kirim Pesan -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-900 text-sm flex items-center mb-2">
                    <i class="fa-solid fa-paper-plane text-emerald-600 mr-2"></i> Uji Coba Kirim Pesan
                </h3>
                <p class="text-xs text-slate-500 mb-4">Pastikan koneksi gateway berjalan dengan mengirim pesan percobaan ke nomor Anda.</p>

                <form action="{{ route('admin.whatsapp.test') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor WhatsApp Tujuan *</label>
                        <input type="text" name="test_phone" placeholder="Contoh: 081234567890" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm shadow-md shadow-emerald-500/20 transition flex items-center justify-center">
                        <i class="fa-solid fa-paper-plane mr-2"></i> Kirim Pesan Uji Coba
                    </button>
                </form>
            </div>

        </div>

        <!-- Column 2: Message Templates Editor (7 cols) -->
        <div class="lg:col-span-7">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-6">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm flex items-center">
                            <i class="fa-solid fa-message text-indigo-600 mr-2"></i> Kustomisasi Template Pesan Otomatis
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Format pesan yang dikirimkan ke pasien saat pendaftaran dan pemanggilan</p>
                    </div>
                </div>

                <!-- Template list -->
                <div class="space-y-6">
                    @forelse($templates as $tmpl)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200/60">
                                {{ $tmpl->key }}
                            </span>
                            <span class="text-[11px] text-slate-400">
                                @switch($tmpl->key)
                                    @case('REGISTER_SUCCESS') Pendaftaran Berhasil @break
                                    @case('REMINDER_H1') Pengingat H-1 Besok @break
                                    @case('NEAR_TURN') Giliran Mendekat (Sisa 2 Pasien) @break
                                    @case('CALLED_1') Pasien Dipanggil @break
                                    @default Pesan Otomatis @break
                                @endswitch
                            </span>
                        </div>

                        <form action="{{ route('admin.whatsapp.template', $tmpl->id) }}" method="POST" class="space-y-2">
                            @csrf
                            @method('PUT')
                            <textarea name="body" rows="4" class="w-full p-3 rounded-xl border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none leading-relaxed">{{ $tmpl->body }}</textarea>

                            <div class="flex items-center justify-between pt-1">
                                <span class="text-[10px] text-slate-400">Variabel: {nama}, {nomor}, {dokter}, {klinik}, {tanggal}, {jam_estimasi}</span>
                                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs transition">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                    @empty
                    <div class="text-center text-slate-400 py-8">
                        Belum ada template pesan yang tersimpan.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function toggleProviderFields(val) {
        if (val === 'FONNTE') {
            document.getElementById('fonnteSection').classList.remove('hidden');
            document.getElementById('wahaSection').classList.add('hidden');
        } else {
            document.getElementById('fonnteSection').classList.add('hidden');
            document.getElementById('wahaSection').classList.remove('hidden');
        }
    }
</script>
@endsection
