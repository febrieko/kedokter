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
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
        .call-glow {
            animation: pulse-ring 2s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col font-sans select-none overflow-hidden">

    <!-- Header bar -->
    <header class="bg-slate-800/90 border-b border-slate-700/80 px-8 py-4 flex items-center justify-between shadow-xl">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold text-2xl shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-hospital-user"></i>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white">{{ $clinic->name }}</h1>
                <p class="text-xs text-slate-400 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-ping"></span> Live Antrian Ruang Tunggu
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <button onclick="testSpeechCall()" class="px-3.5 py-2 rounded-xl bg-slate-700/80 hover:bg-slate-600 text-xs font-semibold text-blue-300 transition flex items-center border border-slate-600">
                <i class="fa-solid fa-play mr-1.5 text-blue-400"></i> Tes Suara
            </button>

            <!-- Voice Selector -->
            <select id="voiceSelect" onchange="changeVoice(this.value)" class="bg-slate-700 text-slate-200 text-xs px-3 py-2 rounded-xl border border-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Memuat Pilihan Suara Indonesia...</option>
            </select>

            <button id="enableSoundBtn" onclick="toggleAudio()" class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-xs font-semibold text-slate-300 transition flex items-center border border-slate-600">
                <i id="soundIcon" class="fa-solid fa-volume-high mr-2 text-emerald-400"></i> <span id="soundStatusText">Suara Aktif</span>
            </button>
            <div class="text-right pl-2">
                <div id="liveClock" class="text-3xl font-mono font-bold text-white tracking-wider">--:--:--</div>
                <div id="liveDate" class="text-xs text-slate-400">Loading...</div>
            </div>
        </div>
    </header>

    <!-- Main Live Display Split Screen -->
    <main class="flex-1 p-8 grid grid-cols-12 gap-8 items-stretch">

        <!-- Left Big Display: Currently Called Patient -->
        <div class="col-span-12 lg:col-span-8 flex flex-col">
            <div class="flex-1 bg-gradient-to-b from-slate-800 to-slate-800/80 rounded-3xl border border-slate-700 p-10 flex flex-col items-center justify-center text-center shadow-2xl relative overflow-hidden">
                <!-- Background decoration glow -->
                <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="inline-flex items-center px-6 py-2 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-sm font-bold tracking-widest uppercase mb-6">
                    <i class="fa-solid fa-bullhorn mr-2"></i> Nomor Sedang Dipanggil
                </div>

                <div class="my-auto py-4">
                    <div id="callNumber" class="text-8xl md:text-[160px] font-black tracking-tight text-white leading-none call-glow drop-shadow-[0_15px_25px_rgba(59,130,246,0.4)]">
                        -
                    </div>
                </div>

                <div class="w-full max-w-xl bg-slate-700/60 backdrop-blur-md rounded-2xl p-6 border border-slate-600/60 shadow-lg mt-auto">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Dokter & Spesialis</p>
                            <h3 id="callDoctor" class="text-2xl font-bold text-white mt-0.5">-</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Ruangan</p>
                            <span id="callRoom" class="text-xl font-bold text-emerald-400 mt-0.5 block">-</span>
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
                    <i class="fa-solid fa-user-doctor mr-2 text-blue-400"></i> Dokter Praktik Hari Ini
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
            Ketik <strong>DAFTAR</strong> untuk mengambil antrian baru atau <strong>CEK</strong> untuk memantau status.
        </div>
    </footer>

    <!-- Audio Speech & Ding-Dong Chime Engine -->
    <script>
        const clinicSlug = '{{ $clinic->slug }}';
        let lastAnnouncedNumber = null;
        let soundEnabled = true;
        let audioCtx = null;
        let availableVoices = [];
        let selectedVoice = null;

        // Clock display
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 1. Inisialisasi AudioContext & Nada Ding-Dong Rumah Sakit
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

        // Memainkan 3 nada denting bel panggilan khas rumah sakit (C5 -> E5 -> G5)
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

                    // Envelope lembut seperti lonceng
                    gain.gain.setValueAtTime(0.0001, now + note.time);
                    gain.gain.exponentialRampToValueAtTime(0.3, now + note.time + 0.04);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + note.time + note.duration);

                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(now + note.time);
                    osc.stop(now + note.time + note.duration);
                });

                // Tunggu sampai denting bel selesai sebelum suara mulai
                setTimeout(resolve, 1100);
            });
        }

        // 2. Deteksi & Pemilihan Voice Bahasa Indonesia Paling Natural
        function loadVoices() {
            if (!('speechSynthesis' in window)) return;

            availableVoices = window.speechSynthesis.getVoices();
            const voiceSelect = document.getElementById('voiceSelect');
            voiceSelect.innerHTML = '';

            // Filter suara Indonesia
            const idVoices = availableVoices.filter(v => 
                v.lang.toLowerCase().includes('id') || 
                v.name.toLowerCase().includes('indonesia') ||
                v.name.toLowerCase().includes('gadis') ||
                v.name.toLowerCase().includes('ardi')
            );

            // Prioritaskan suara neural online (Microsoft Gadis / Ardi / Google)
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
                // Fallback jika tidak ada suara lokal eksplisit
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Suara Standar Browser';
                voiceSelect.appendChild(opt);
            }
        }

        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = loadVoices;
            loadVoices();
        }

        function changeVoice(voiceName) {
            selectedVoice = availableVoices.find(v => v.name === voiceName) || null;
            speakText('Suara panggilan diganti');
        }

        function toggleAudio() {
            soundEnabled = !soundEnabled;
            const btn = document.getElementById('soundStatusText');
            const icon = document.getElementById('soundIcon');

            btn.textContent = soundEnabled ? 'Suara Aktif' : 'Suara Mati';
            icon.className = soundEnabled ? 'fa-solid fa-volume-high mr-2 text-emerald-400' : 'fa-solid fa-volume-xmark mr-2 text-rose-400';

            if (soundEnabled) {
                getAudioContext();
                speakAnnouncement(1, 'Ruang Poli');
            }
        }

        // 3. Mesin Panggilan Suara Natural (Bel Ding-dong + Text-to-Speech)
        async function speakAnnouncement(queueNumber, roomName) {
            if (!soundEnabled) return;

            // 1. Bunyikan Bel Rumah Sakit terlebih dahulu
            await playHospitalChime();

            // 2. Ucapkan kalimat panggilan dengan intonasi natural & jeda
            if (!('speechSynthesis' in window)) return;
            window.speechSynthesis.cancel();

            // Kalimat sopan standar rumah sakit di Indonesia
            const textToSpeak = `Nomor antrian... ${queueNumber}... Silakan menuju ke... ${roomName || 'ruang periksa'}.`;

            const utterance = new SpeechSynthesisUtterance(textToSpeak);
            utterance.lang = 'id-ID';

            if (selectedVoice) {
                utterance.voice = selectedVoice;
            }

            // Atur kecepatan bicara yang tenang dan artikulatif
            utterance.rate = 0.88; // Sedikit lebih santai agar jelas di ruang tunggu
            utterance.pitch = 1.05; // Nada ramah
            utterance.volume = 1.0;

            window.speechSynthesis.speak(utterance);
        }

        function testSpeechCall() {
            getAudioContext();
            speakAnnouncement(5, 'Poli Spesialis Penyakit Dalam');
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
            // 1. Current call
            const callNumberEl = document.getElementById('callNumber');
            const callDoctorEl = document.getElementById('callDoctor');
            const callRoomEl = document.getElementById('callRoom');

            if (data.current_call) {
                const call = data.current_call;
                callNumberEl.textContent = '#' + call.number;
                callDoctorEl.textContent = call.doctor;
                callRoomEl.textContent = call.room || 'Ruang Poli';

                // Check if new call should be announced
                const callKey = `${call.number}-${call.call_count}`;
                if (lastAnnouncedNumber !== callKey) {
                    lastAnnouncedNumber = callKey;
                    speakAnnouncement(call.number, call.room || call.doctor);
                }
            } else {
                callNumberEl.textContent = '-';
                callDoctorEl.textContent = 'Belum Ada Panggilan';
                callRoomEl.textContent = '-';
            }

            // 2. Next queues
            const nextContainer = document.getElementById('nextQueueContainer');
            if (data.next_queues && data.next_queues.length > 0) {
                nextContainer.innerHTML = data.next_queues.map(q => `
                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-700/50 border border-slate-600/50">
                        <div class="flex items-center space-x-3">
                            <span class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 font-black text-lg flex items-center justify-center">
                                #${q.number}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-white">${q.doctor}</p>
                                <p class="text-[11px] text-slate-400">${q.room || 'Poli'}</p>
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
                        <p class="text-sm">Tidak ada antrian berikutnya</p>
                    </div>
                `;
            }

            // 3. Doctor Status Cards
            const docContainer = document.getElementById('doctorStatusContainer');
            if (data.doctor_queues && data.doctor_queues.length > 0) {
                docContainer.innerHTML = data.doctor_queues.map(d => `
                    <div class="flex items-center justify-between text-xs py-1.5 border-b border-slate-700/60 last:border-0">
                        <span class="font-semibold text-slate-300">${d.doctor_name}</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-slate-400">Aktif: <strong class="text-emerald-400">#${d.current_number}</strong></span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">Sisa: ${d.waiting_count}</span>
                        </div>
                    </div>
                `).join('');
            } else {
                docContainer.innerHTML = `<div class="text-xs text-slate-500">Semua dokter belum memulai pelayanan</div>`;
            }
        }

        // Realtime Connection via Server-Sent Events (SSE) with Polling Fallback
        function connectSSE() {
            if (window.EventSource) {
                const eventSource = new EventSource(`/api/display/${clinicSlug}/stream`);

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
            setInterval(fetchSnapshot, 3000);
            fetchSnapshot();
        }

        async function fetchSnapshot() {
            try {
                const res = await fetch(`/api/display/${clinicSlug}/data`);
                if (res.ok) {
                    const data = await res.json();
                    renderDisplay(data);
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }

        // Start SSE stream
        connectSSE();
    </script>
</body>
</html>

