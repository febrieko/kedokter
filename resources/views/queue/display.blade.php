<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar Antrian - {{ $clinic->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(0.97); opacity: 0.85; }
            50% { transform: scale(1.03); opacity: 1; }
            100% { transform: scale(0.97); opacity: 0.85; }
        }
        .call-glow {
            animation: pulse-ring 2.2s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col font-sans select-none overflow-hidden">

    <!-- Header bar -->
    <header class="bg-slate-800/90 border-b border-slate-700/80 px-6 sm:px-8 py-3.5 flex items-center justify-between shadow-xl">
        <div class="flex items-center space-x-4">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-hospital-user"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white leading-tight">{{ $clinic->name }}</h1>
                <p class="text-xs text-slate-400 flex items-center mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-ping"></span>
                    <span>Live Antrian Ruang Tunggu &bull; <strong id="headerDateText" class="text-slate-300">{{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y') }}</strong></span>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2 sm:space-x-3">
            <!-- Filter Poli / Dokter (Opsional untuk Display TV per Ruangan) -->
            <form method="GET" action="{{ route('display', $clinic->slug) }}" class="hidden md:flex items-center space-x-1.5">
                <input type="hidden" name="date" value="{{ $date }}">
                <select name="doctor_id" onchange="this.form.submit()" class="bg-slate-700/80 text-slate-200 text-xs px-2.5 py-2 rounded-xl border border-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">Semua Poli / Dokter</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}" {{ $doctorId === $doc->id ? 'selected' : '' }}>
                            {{ $doc->name }} ({{ $doc->pivot->room_number ?? $doc->specialty }})
                        </option>
                    @endforeach
                </select>
            </form>

            <button onclick="testSpeechCall()" class="px-3 py-2 rounded-xl bg-slate-700/80 hover:bg-slate-600 text-xs font-semibold text-blue-300 transition flex items-center border border-slate-600" title="Uji speaker ruang tunggu">
                <i class="fa-solid fa-play mr-1.5 text-blue-400"></i> Tes Suara
            </button>

            <!-- Voice Selector -->
            <select id="voiceSelect" onchange="changeVoice(this.value)" class="bg-slate-700 text-slate-200 text-xs px-2.5 py-2 rounded-xl border border-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 max-w-[130px] sm:max-w-none">
                <option value="">Memuat Suara...</option>
            </select>

            <button id="enableSoundBtn" onclick="toggleAudio()" class="px-3 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-xs font-semibold text-slate-300 transition flex items-center border border-slate-600">
                <i id="soundIcon" class="fa-solid fa-volume-high mr-1.5 text-emerald-400"></i> <span id="soundStatusText">Suara Aktif</span>
            </button>

            <div class="text-right pl-2 border-l border-slate-700">
                <div id="liveClock" class="text-2xl sm:text-3xl font-mono font-bold text-white tracking-wider">--:--:--</div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">WIB</div>
            </div>
        </div>
    </header>

    <!-- Main Live Display Split Screen -->
    <main class="flex-1 p-6 sm:p-8 grid grid-cols-12 gap-6 sm:gap-8 items-stretch">

        <!-- Left Big Display: Currently Called or In-Service Patient -->
        <div class="col-span-12 lg:col-span-8 flex flex-col">
            <div class="flex-1 bg-gradient-to-b from-slate-800 to-slate-800/80 rounded-3xl border border-slate-700 p-8 sm:p-10 flex flex-col items-center justify-center text-center shadow-2xl relative overflow-hidden">
                <!-- Background decoration glow -->
                <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Status Banner -->
                <div id="callStatusBadge" class="inline-flex items-center px-6 py-2 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-sm font-bold tracking-widest uppercase mb-4 transition-all">
                    <span id="callStatusDot" class="w-2.5 h-2.5 rounded-full bg-blue-400 mr-2 animate-ping"></span>
                    <span id="callStatusText">MENUNGGU PANGGILAN</span>
                </div>

                <div class="my-auto py-2">
                    <div id="callNumber" class="text-7xl sm:text-8xl md:text-[150px] font-black tracking-tight text-white leading-none call-glow drop-shadow-[0_15px_25px_rgba(59,130,246,0.4)]">
                        -
                    </div>
                    <div id="callPatient" class="text-slate-300 text-base sm:text-lg font-medium mt-3 tracking-wide">
                        Silakan bersiap di ruang tunggu
                    </div>
                </div>

                <div class="w-full max-w-xl bg-slate-700/60 backdrop-blur-md rounded-2xl p-5 sm:p-6 border border-slate-600/60 shadow-lg mt-auto">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Dokter / Spesialis</p>
                            <h3 id="callDoctor" class="text-xl sm:text-2xl font-bold text-white mt-0.5">Semua Poli Siap</h3>
                            <p id="callSpecialty" class="text-xs text-blue-300 mt-0.5"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Tujuan Ruangan</p>
                            <span id="callRoom" class="text-xl sm:text-2xl font-black text-emerald-400 mt-0.5 block">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Next In Queue & Practicing Doctors -->
        <div class="col-span-12 lg:col-span-4 flex flex-col space-y-6">

            <!-- Next In Line Card -->
            <div class="bg-slate-800/80 rounded-3xl border border-slate-700 p-6 shadow-xl flex-1 flex flex-col">
                <h3 class="text-base font-bold text-slate-200 uppercase tracking-wider flex items-center mb-4">
                    <i class="fa-solid fa-forward-step mr-2 text-indigo-400"></i> Antrian Berikutnya
                </h3>

                <div id="nextQueueContainer" class="flex-1 space-y-3 flex flex-col justify-center">
                    <div class="text-center text-slate-500 py-8">
                        <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i>
                        <p class="text-xs">Memuat daftar antrian...</p>
                    </div>
                </div>
            </div>

            <!-- Doctor Status Summary -->
            <div class="bg-slate-800/80 rounded-3xl border border-slate-700 p-6 shadow-xl">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center mb-3">
                    <i class="fa-solid fa-user-doctor mr-2 text-blue-400"></i> Status Dokter Hari Ini
                </h3>
                <div id="doctorStatusContainer" class="space-y-2">
                    <div class="text-xs text-slate-500">Memuat status dokter...</div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer marquee -->
    <footer class="bg-slate-800 border-t border-slate-700 px-8 py-2.5 text-xs text-slate-400 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            <span>Pendaftaran mandiri dapat dilakukan via WhatsApp Klinik: <strong>{{ $clinic->phone }}</strong></span>
        </div>
        <div>
            Ketik <strong>DAFTAR</strong> untuk mendaftar antrian baru atau <strong>CEK</strong> untuk memantau status.
        </div>
    </footer>

    <!-- Audio Speech & Ding-Dong Chime Engine -->
    <script>
        const clinicSlug = '{{ $clinic->slug }}';
        const displayDate = '{{ $date }}';
        const displayDoctorId = '{{ $doctorId ?? '' }}';

        let lastAnnouncedNumber = null;
        let soundEnabled = true;
        let audioCtx = null;
        let availableVoices = [];
        let selectedVoice = null;
        let pollingInterval = null;

        // Clock display
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toTimeString().split(' ')[0];
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 1. AudioContext & Hospital 3-Tone Chime (C5 -> E5 -> G5)
        function getAudioContext() {
            if (!audioCtx) {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (AudioContextClass) {
                    audioCtx = new AudioContextClass();
                }
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }

        function playHospitalChime() {
            return new Promise((resolve) => {
                const ctx = getAudioContext();
                if (!ctx) {
                    resolve();
                    return;
                }

                const notes = [
                    { freq: 523.25, time: 0.00, duration: 0.35 }, // C5
                    { freq: 659.25, time: 0.28, duration: 0.35 }, // E5
                    { freq: 783.99, time: 0.56, duration: 0.65 }, // G5
                ];

                const now = ctx.currentTime;

                notes.forEach(note => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(note.freq, now + note.time);

                    gain.gain.setValueAtTime(0.0001, now + note.time);
                    gain.gain.exponentialRampToValueAtTime(0.3, now + note.time + 0.04);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + note.time + note.duration);

                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(now + note.time);
                    osc.stop(now + note.time + note.duration);
                });

                setTimeout(resolve, 1100);
            });
        }

        // 2. Load Neural Voices for Indonesian
        function loadVoices() {
            if (!('speechSynthesis' in window)) return;
            const voices = window.speechSynthesis.getVoices();
            if (voices.length === 0) return;

            availableVoices = voices;
            const voiceSelect = document.getElementById('voiceSelect');
            voiceSelect.innerHTML = '';

            const idVoices = voices.filter(v => v.lang.startsWith('id') || v.lang.startsWith('in') || v.name.includes('Indonesia'));

            idVoices.sort((a, b) => {
                const aNatural = a.name.includes('Natural') || a.name.includes('Gadis') || a.name.includes('Google');
                const bNatural = b.name.includes('Natural') || b.name.includes('Gadis') || b.name.includes('Google');
                return bNatural - aNatural;
            });

            if (idVoices.length > 0) {
                idVoices.forEach((v, idx) => {
                    const opt = document.createElement('option');
                    opt.value = v.name;
                    opt.textContent = v.name.replace('Microsoft ', '').replace('Online (Natural) - ', '').replace('Indonesian (Indonesia)', '(ID)');
                    if (idx === 0) opt.selected = true;
                    voiceSelect.appendChild(opt);
                });
                selectedVoice = idVoices[0];
            } else {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Suara Standar';
                voiceSelect.appendChild(opt);
            }
        }

        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = loadVoices;
            loadVoices();
        }

        function changeVoice(voiceName) {
            selectedVoice = availableVoices.find(v => v.name === voiceName) || null;
            speakText('Suara panggilan dipilih');
        }

        function toggleAudio() {
            soundEnabled = !soundEnabled;
            const btn = document.getElementById('soundStatusText');
            const icon = document.getElementById('soundIcon');

            btn.textContent = soundEnabled ? 'Suara Aktif' : 'Suara Mati';
            icon.className = soundEnabled ? 'fa-solid fa-volume-high mr-1.5 text-emerald-400' : 'fa-solid fa-volume-xmark mr-1.5 text-rose-400';

            if (soundEnabled) {
                getAudioContext();
                speakAnnouncement(1, 'Ruang 101');
            }
        }

        // 3. Speech Announcement
        async function speakAnnouncement(queueNumber, roomName) {
            if (!soundEnabled) return;

            await playHospitalChime();

            if (!('speechSynthesis' in window)) return;
            window.speechSynthesis.cancel();

            const textToSpeak = `Nomor antrian... ${queueNumber}... Silakan menuju ke... ${roomName || 'ruang periksa'}.`;

            const utterance = new SpeechSynthesisUtterance(textToSpeak);
            utterance.lang = 'id-ID';

            if (selectedVoice) {
                utterance.voice = selectedVoice;
            }

            utterance.rate = 0.88;
            utterance.pitch = 1.05;
            utterance.volume = 1.0;

            window.speechSynthesis.speak(utterance);
        }

        function testSpeechCall() {
            getAudioContext();
            speakAnnouncement(5, 'Ruang 101');
        }

        function speakText(text) {
            if (!soundEnabled || !('speechSynthesis' in window)) return;
            window.speechSynthesis.cancel();
            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'id-ID';
            if (selectedVoice) u.voice = selectedVoice;
            u.rate = 0.95;
            window.speechSynthesis.speak(u);
        }

        // 4. Render Data dari Realtime SSE / Polling
        function renderDisplay(data) {
            if (data.formatted_date) {
                document.getElementById('headerDateText').textContent = data.formatted_date;
            }

            // 1. Current call / in-service patient
            const callNumberEl = document.getElementById('callNumber');
            const callDoctorEl = document.getElementById('callDoctor');
            const callSpecialtyEl = document.getElementById('callSpecialty');
            const callRoomEl = document.getElementById('callRoom');
            const callPatientEl = document.getElementById('callPatient');
            const callStatusBadge = document.getElementById('callStatusBadge');
            const callStatusText = document.getElementById('callStatusText');
            const callStatusDot = document.getElementById('callStatusDot');

            if (data.current_call) {
                const call = data.current_call;
                callNumberEl.textContent = '#' + String(call.number).padStart(2, '0');
                callDoctorEl.textContent = call.doctor;
                callSpecialtyEl.textContent = call.specialty || '';
                callRoomEl.textContent = call.room || 'Ruang Poli';
                callPatientEl.textContent = call.patient_name ? `Pasien: ${call.patient_name}` : 'Silakan masuk ke ruangan';

                if (call.status === 'CALLED') {
                    callStatusBadge.className = 'inline-flex items-center px-6 py-2 rounded-full bg-blue-500/20 border border-blue-400/40 text-blue-300 text-sm font-bold tracking-widest uppercase mb-4 transition-all';
                    callStatusDot.className = 'w-2.5 h-2.5 rounded-full bg-blue-400 mr-2 animate-ping';
                    callStatusText.textContent = `NOMOR SEDANG DIPANGGIL (PANGGILAN KE-${call.call_count || 1})`;

                    // Bunyikan suara panggilan hanya jika panggilan baru
                    const callKey = `${call.id}-${call.call_count}`;
                    if (lastAnnouncedNumber !== callKey) {
                        lastAnnouncedNumber = callKey;
                        speakAnnouncement(call.number, call.room || call.doctor);
                    }
                } else if (call.status === 'IN_SERVICE') {
                    callStatusBadge.className = 'inline-flex items-center px-6 py-2 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 text-sm font-bold tracking-widest uppercase mb-4 transition-all';
                    callStatusDot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-400 mr-2';
                    callStatusText.textContent = 'SEDANG DALAM PEMERIKSAAN DOKTER';
                }
            } else {
                callNumberEl.textContent = '-';
                callDoctorEl.textContent = 'Menunggu Panggilan Berikutnya';
                callSpecialtyEl.textContent = '';
                callRoomEl.textContent = '-';
                callPatientEl.textContent = 'Silakan duduk dan tunggu nomor Anda dipanggil';

                callStatusBadge.className = 'inline-flex items-center px-6 py-2 rounded-full bg-slate-700/60 border border-slate-600/60 text-slate-400 text-sm font-bold tracking-widest uppercase mb-4 transition-all';
                callStatusDot.className = 'w-2.5 h-2.5 rounded-full bg-slate-500 mr-2';
                callStatusText.textContent = 'BELUM ADA PANGGILAN AKTIF';
            }

            // 2. Next queues
            const nextContainer = document.getElementById('nextQueueContainer');
            if (data.next_queues && data.next_queues.length > 0) {
                nextContainer.innerHTML = data.next_queues.map(q => `
                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-700/50 border border-slate-600/50">
                        <div class="flex items-center space-x-3">
                            <span class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 font-black text-lg flex items-center justify-center">
                                #${String(q.number).padStart(2, '0')}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-white">${q.doctor}</p>
                                <p class="text-[11px] text-slate-400">${q.room} &bull; ${q.patient_name || 'Pasien'}</p>
                            </div>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-slate-600/60 text-slate-300 font-semibold">
                            Menunggu
                        </span>
                    </div>
                `).join('');
            } else {
                nextContainer.innerHTML = `
                    <div class="text-center text-slate-500 py-6">
                        <i class="fa-solid fa-inbox text-2xl mb-2 text-slate-600 block"></i>
                        <p class="text-sm">Tidak ada antrian menunggu saat ini</p>
                    </div>
                `;
            }

            // 3. Doctor Status Cards
            const docContainer = document.getElementById('doctorStatusContainer');
            if (data.doctor_queues && data.doctor_queues.length > 0) {
                docContainer.innerHTML = data.doctor_queues.map(d => `
                    <div class="flex items-center justify-between text-xs py-2 border-b border-slate-700/60 last:border-0">
                        <div>
                            <span class="font-bold text-slate-200 block">${d.doctor_name}</span>
                            <span class="text-[10px] text-slate-400">${d.room}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-slate-400">Aktif: <strong class="text-emerald-400">${d.current_number}</strong></span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">Sisa: ${d.waiting_count}</span>
                        </div>
                    </div>
                `).join('');
            } else {
                docContainer.innerHTML = `<div class="text-xs text-slate-500">Semua dokter belum memulai pelayanan</div>`;
            }
        }

        // 5. Realtime Sync (SSE + Smart Polling Fallback)
        function getApiUrl(endpoint) {
            const params = new URLSearchParams();
            if (displayDate) params.append('date', displayDate);
            if (displayDoctorId) params.append('doctor_id', displayDoctorId);
            const queryStr = params.toString();
            return `/api/display/${clinicSlug}/${endpoint}` + (queryStr ? `?${queryStr}` : '');
        }

        function connectSSE() {
            if (window.EventSource) {
                const sseUrl = getApiUrl('stream');
                const eventSource = new EventSource(sseUrl);

                eventSource.onmessage = function(event) {
                    try {
                        const data = JSON.parse(event.data);
                        renderDisplay(data);
                    } catch (e) {
                        console.error('Error parsing SSE data:', e);
                    }
                };

                eventSource.onerror = function() {
                    console.warn('SSE connection disconnected. Falling back to HTTP polling.');
                    eventSource.close();
                    startPolling();
                };
            } else {
                startPolling();
            }
        }

        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            pollingInterval = setInterval(fetchSnapshot, 2500);
            fetchSnapshot();
        }

        async function fetchSnapshot() {
            try {
                const res = await fetch(getApiUrl('data'));
                if (res.ok) {
                    const data = await res.json();
                    renderDisplay(data);
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }

        // Jalankan sinkronisasi
        fetchSnapshot(); // Ambil snapshot pertama segera
        connectSSE();    // Hubungkan SSE realtime
    </script>
</body>
</html>
