<!DOCTYPE html>
<html lang="id" id="html-root" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiagaNusa - Pantau & Lapor Darurat (Fixed)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            darkMode: 'class', 
            theme: {
                extend: {
                    colors: {
                        darkbg: '#121212',
                        darkcard: '#1E1E1E',
                        alertorange: '#FF9800', 
                        alertred: '#F44336'     
                    }
                }
            }
        }
    </script>
    
    <!-- Leaflet Configuration -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        /* CSS Animasi untuk Audio Visualizer WhatsApp Style */
        @keyframes soundwave {
            0% { height: 4px; }
            100% { height: 24px; }
        }
        .animate-soundwave {
            animation: soundwave ease-in-out infinite alternate;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-darkbg text-gray-900 dark:text-gray-100 font-sans h-screen flex flex-col overflow-hidden transition-colors duration-300">

    <header class="bg-white dark:bg-darkcard p-4 shadow-md flex justify-between items-center z-10 border-b border-gray-200 dark:border-gray-800 transition-colors duration-300">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-shield-halved text-alertorange text-2xl"></i>
            <h1 class="text-xl font-bold tracking-wider">Siaga<span class="text-alertorange">Nusa</span></h1>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="bg-orange-100 dark:bg-alertorange/20 text-orange-600 dark:text-alertorange px-4 py-1 rounded-full text-sm font-bold flex items-center gap-2 border border-orange-300 dark:border-alertorange/50">
                <div class="w-2 h-2 rounded-full bg-alertorange"></div>
                STATUS: WASPADA
            </div>
            
            <button onclick="toggleTheme()" class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-800 flex justify-center items-center text-gray-600 dark:text-yellow-400 hover:bg-gray-300 dark:hover:bg-gray-700 transition duration-300 focus:outline-none">
                <i id="theme-icon" class="fa-solid fa-moon text-lg"></i>
            </button>
        </div>
    </header>

    <main class="flex-1 flex flex-col md:flex-row w-full h-full overflow-hidden">
        
        <section id="map" class="w-full md:w-3/5 h-[40vh] md:h-full bg-gray-300 dark:bg-gray-800 relative flex flex-col justify-center items-center border-b md:border-b-0 md:border-r border-gray-300 dark:border-gray-800 transition-colors duration-300 z-0">
            <!-- Peta akan dirender di sini oleh Leaflet -->
            <div id="map-loading" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-200 dark:bg-gray-800 z-10 transition-opacity duration-300">
                <i class="fa-solid fa-location-crosshairs text-4xl text-alertorange animate-spin mb-3"></i>
                <p class="text-gray-600 dark:text-gray-300 font-medium animate-pulse">Mencari lokasi Anda...</p>
            </div>
        </section>

        <section class="w-full md:w-2/5 h-[60vh] md:h-full flex flex-col p-4 md:p-6 overflow-y-auto">
            
            <div class="bg-white dark:bg-darkcard border-l-4 border-alertorange rounded-lg p-5 mb-6 shadow-sm dark:shadow-lg transition-colors duration-300">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fa-solid fa-triangle-exclamation text-alertorange text-2xl animate-pulse"></i>
                    <h2 class="text-xl font-extrabold text-gray-950 dark:text-alertorange">PERINGATAN DINI AI</h2>
                </div>
                <div id="ai-warning-box">
                    <p class="text-gray-800 dark:text-gray-100 text-sm italic mb-4 animate-pulse">
                        <i class="fa-solid fa-satellite-dish mr-2"></i> Mengumpulkan data cuaca dan menganalisis risiko spasial...
                    </p>
                </div>
                <button class="w-full bg-gray-50 dark:bg-darkbg hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-950 dark:text-white py-3 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm focus:ring-2 focus:ring-alertorange focus:outline-none">
                    <i class="fa-solid fa-route text-gray-600 dark:text-gray-400"></i> Lihat Rute Evakuasi
                </button>
            </div>

            <div class="flex-1"></div> <div class="bg-white dark:bg-darkcard rounded-xl p-4 shadow-sm dark:shadow-lg border border-gray-200 dark:border-gray-800 transition-colors duration-300">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider">Lapor Kondisi Darurat (Mode Teks/Suara)</h3>
                
                <div class="relative w-full">
                    <textarea 
                        id="emergency-text"
                        class="w-full bg-gray-50 dark:bg-darkbg border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white text-base rounded-lg p-3 focus:outline-none focus:border-alertorange dark:focus:border-alertorange resize-none placeholder-gray-400 dark:placeholder-gray-500 transition-colors duration-300" 
                        rows="2" 
                        placeholder="Ketik kondisi Anda... (Cth: Air masuk rumah sedengkul, butuh perahu karet untuk kakek)"></textarea>
                    
                    <!-- WhatsApp Style Recording Overlay (Hidden by default) -->
                    <div id="recording-overlay" class="hidden absolute inset-0 bg-[#1c1c1e] rounded-lg items-center px-4 z-10 w-full h-full shadow-inner border border-gray-700">
                        <!-- Delete Icon (Left) -->
                        <div class="text-gray-300 mr-4">
                            <i class="fa-regular fa-trash-can text-lg"></i>
                        </div>
                        
                        <!-- Recording Dot & Timer -->
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-400 animate-pulse"></span>
                            <span id="recording-timer" class="text-white font-mono text-lg tracking-wider">0:00</span>
                        </div>
                        
                        <!-- Audio Visualizer (Fake Bar CSS) -->
                        <div class="flex-1 flex justify-center items-center gap-[3px] opacity-70 ml-4 mr-4">
                            <div class="w-1 bg-gray-400 rounded-full animate-soundwave" style="animation-duration: 0.4s; height: 10px;"></div>
                            <div class="w-1 bg-gray-400 rounded-full animate-soundwave" style="animation-duration: 0.3s; height: 18px;"></div>
                            <div class="w-1 bg-gray-400 rounded-full animate-soundwave" style="animation-duration: 0.6s; height: 8px;"></div>
                            <div class="w-1 bg-gray-400 rounded-full animate-soundwave" style="animation-duration: 0.5s; height: 22px;"></div>
                            <div class="w-1 bg-gray-500 rounded-full animate-soundwave" style="animation-duration: 0.4s; height: 14px;"></div>
                            <div class="w-1 bg-gray-500 rounded-full animate-soundwave" style="animation-duration: 0.7s; height: 8px;"></div>
                            <div class="w-1 bg-gray-500 rounded-full animate-soundwave" style="animation-duration: 0.3s; height: 20px;"></div>
                            <div class="w-1 bg-gray-600 rounded-full animate-soundwave" style="animation-duration: 0.5s; height: 12px;"></div>
                            <div class="w-1 bg-gray-600 rounded-full animate-soundwave" style="animation-duration: 0.6s; height: 6px;"></div>
                        </div>
                        
                        <!-- Pause Icon (Right) -->
                        <div class="text-red-400">
                            <i class="fa-solid fa-pause text-lg"></i>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-3 gap-3">
                    <button id="btn-voice" class="bg-gray-100 dark:bg-darkbg hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 p-3 rounded-lg flex items-center justify-center transition-all duration-200 shadow-sm focus:ring-2 focus:ring-gray-400 focus:outline-none touch-none select-none" title="Tahan untuk Bicara (WhatsApp Style)">
                        <i id="icon-voice" class="fa-solid fa-microphone text-xl pointer-events-none"></i>
                    </button>
                    <button id="btn-submit-report" onclick="submitEmergencyReport()" class="flex-1 bg-alertred hover:bg-red-600 text-white font-extrabold py-3.5 px-4 rounded-lg flex items-center justify-center gap-2 transition shadow-md dark:shadow-[0_0_15px_rgba(244,67,54,0.4)] focus:ring-4 focus:ring-red-300 focus:outline-none text-base">
                        <i class="fa-solid fa-paper-plane" id="icon-submit"></i> <span id="text-submit">KIRIM LAPORAN AI</span>
                    </button>
                </div>
            </div>

        </section>
    </main>

    <script>
        const htmlRoot = document.getElementById('html-root');
        const themeIcon = document.getElementById('theme-icon');

        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
                htmlRoot.classList.add('dark');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
            } else {
                htmlRoot.classList.remove('dark');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
            }
        }

        function toggleTheme() {
            if (htmlRoot.classList.contains('dark')) {
                htmlRoot.classList.remove('dark');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'light');
            } else {
                htmlRoot.classList.add('dark');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'dark');
            }
        }

        initTheme();

        // --- Map & Geolocation Logic ---
        let map;
        let userMarker;
        const defaultLocation = [-6.200000, 106.816666]; // Default: Jakarta

        function initMap(lat, lng) {
            // Sembunyikan loading
            document.getElementById('map-loading').style.display = 'none';

            // Inisialisasi peta
            map = L.map('map', {
                zoomControl: false // Kita pindahkan ke kanan bawah nanti
            }).setView([lat, lng], 16);

            // Tambahkan tile layer (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            L.control.zoom({ position: 'bottomright' }).addTo(map);

            // Icon kustom untuk lokasi pengguna (Biru)
            const userIcon = L.divIcon({
                className: 'custom-div-icon',
                html: "<div class='w-6 h-6 bg-blue-500 rounded-full border-4 border-white dark:border-darkcard shadow-lg flex items-center justify-center animate-bounce'><div class='w-2 h-2 bg-white rounded-full'></div></div>",
                iconSize: [24, 24],
                iconAnchor: [12, 24]
            });

            // Tambahkan marker ke peta
            userMarker = L.marker([lat, lng], {icon: userIcon})
                .addTo(map)
                .bindPopup('<b class="text-gray-900">Lokasi Anda Saat Ini</b><br>Koordinat dikunci.')
                .openPopup();

            // FIX: Paksa Leaflet menghitung ulang ukuran container setelah dirender 
            // agar mencegah isu "ubin abu-abu" (grey tiles) atau peta terpotong.
            setTimeout(() => {
                map.invalidateSize();
            }, 500);

            // Panggil AI Early Warning berdasarkan koordinat
            fetchEarlyWarning(lat, lng);
        }

        // --- Integrasi AI: Early Warning ---
        async function fetchEarlyWarning(lat, lng) {
            const warningBox = document.getElementById('ai-warning-box');
            try {
                const response = await fetch(`/api/warning?lat=${lat}&lng=${lng}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const aiStatus = result.warning.status_bahaya;
                    const aiMessage = result.warning.pesan_peringatan_anti_panik;
                    
                    // Deteksi warna berdasarkan keyword status untuk UX
                    let bgColor = "bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800";
                    if (aiStatus.toUpperCase().includes("MERAH") || aiStatus.toUpperCase().includes("AWAS")) {
                        bgColor = "bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800";
                    } else if (aiStatus.toUpperCase().includes("ORANYE") || aiStatus.toUpperCase().includes("SIAGA")) {
                        bgColor = "bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-800";
                    } else if (aiStatus.toUpperCase().includes("KUNING") || aiStatus.toUpperCase().includes("WASPADA")) {
                        bgColor = "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800";
                    }

                    // Inject hasil AI ke dalam DOM dengan styling status yang jelas
                    warningBox.innerHTML = `
                        <div class="mb-4">
                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-full border ${bgColor} mb-3 shadow-sm">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> STATUS: ${aiStatus}
                            </span>
                            <p class="text-gray-800 dark:text-gray-100 text-base font-medium leading-relaxed">"${aiMessage}"</p>
                        </div>
                    `;
                } else {
                    warningBox.innerHTML = `<p class="text-red-500 text-sm font-bold">Gagal memuat instruksi AI: ${result.message}</p>`;
                }
            } catch (error) {
                console.error("AI Warning Error:", error);
                warningBox.innerHTML = `<p class="text-red-500 text-sm font-bold">Terjadi kesalahan koneksi ke server AI.</p>`;
            }
        }

        // --- Integrasi AI: NLP Triage (Submit Report) ---
        async function submitEmergencyReport() {
            const textArea = document.getElementById('emergency-text');
            const btnSubmit = document.getElementById('btn-submit-report');
            const iconSubmit = document.getElementById('icon-submit');
            const textSubmit = document.getElementById('text-submit');
            
            const userText = textArea.value.trim();
            if (!userText) {
                alert("Mohon ketik kondisi darurat Anda terlebih dahulu.");
                return;
            }

            // Dapatkan koordinat saat ini dari marker user (fallback ke default jika belum ada)
            const userLat = userMarker ? userMarker.getLatLng().lat : defaultLocation[0];
            const userLng = userMarker ? userMarker.getLatLng().lng : defaultLocation[1];

            // Setup loading state
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-70', 'cursor-not-allowed');
            iconSubmit.classList.replace('fa-paper-plane', 'fa-spinner');
            iconSubmit.classList.add('fa-spin');
            textSubmit.innerText = 'AI SEDANG MELACAK...';

            try {
                // Gunakan URLSearchParams untuk x-www-form-urlencoded format
                const formData = new URLSearchParams();
                formData.append('report_text', userText);
                formData.append('lat', userLat);
                formData.append('lng', userLng);

                const response = await fetch('/api/report', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });

                const result = await response.json();

                if (result.status === 'success') {
                    const aiData = result.data;
                    
                    // Plot "Red Pin" di peta
                    plotEmergencyMarker(userLat, userLng, aiData);

                    // Bersihkan form
                    textArea.value = '';
                    alert("Laporan berhasil diproses AI dan di-plot di peta!");
                } else {
                    alert("Gagal: " + result.message);
                }
            } catch (error) {
                console.error("Submit Error:", error);
                alert("Terjadi kesalahan sistem saat menghubungi AI Brain.");
            } finally {
                // Restore button state
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
                iconSubmit.classList.remove('fa-spin');
                iconSubmit.classList.replace('fa-spinner', 'fa-paper-plane');
                textSubmit.innerText = 'KIRIM LAPORAN AI';
            }
        }

        function plotEmergencyMarker(lat, lng, aiData) {
            // Icon kustom untuk laporan darurat (Merah berdenyut)
            const dangerIcon = L.divIcon({
                className: 'custom-div-icon',
                html: "<div class='w-8 h-8 bg-red-600 rounded-full border-4 border-red-300 dark:border-red-900 shadow-[0_0_15px_rgba(244,67,54,0.8)] flex items-center justify-center animate-pulse'><i class='fa-solid fa-triangle-exclamation text-white text-xs'></i></div>",
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });

            // Ubah sedikit koordinat agar pin tidak persis menumpuk dengan lokasi user
            // Di dunia nyata, AI mungkin mengekstrak koordinat dari nama jalan
            const offsetLat = lat + (Math.random() - 0.5) * 0.005;
            const offsetLng = lng + (Math.random() - 0.5) * 0.005;

            // Warnai priority
            let badgeColor = 'bg-gray-500';
            if (aiData.priority === 'CRITICAL') badgeColor = 'bg-red-700 animate-pulse';
            if (aiData.priority === 'HIGH') badgeColor = 'bg-orange-600';
            if (aiData.priority === 'MEDIUM') badgeColor = 'bg-yellow-500';

            const popupHtml = `
                <div class="p-1 min-w-[200px]">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-bold text-white px-2 py-0.5 rounded ${badgeColor}">
                            ${aiData.priority || 'UNKNOWN'}
                        </span>
                    </div>
                    <p class="font-bold text-gray-900 mb-1 leading-tight text-sm">${aiData.status || 'Kondisi Darurat'}</p>
                    <p class="text-xs text-gray-600 mb-2"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> ${aiData.location || 'Lokasi tidak spesifik'}</p>
                    ${aiData.specific_needs && aiData.specific_needs.length > 0 
                        ? `<div class="bg-gray-100 p-2 rounded text-xs text-gray-800"><b>Butuh:</b> ${aiData.specific_needs.join(', ')}</div>` 
                        : ''}
                    <hr class="my-2 border-gray-300">
                    <p class="text-[10px] text-gray-500 italic">" ${aiData.original_text} "</p>
                </div>
            `;

            L.marker([offsetLat, offsetLng], {icon: dangerIcon})
                .addTo(map)
                .bindPopup(popupHtml)
                .openPopup();
                
            // Geser pandangan peta ke marker baru
            map.flyTo([offsetLat, offsetLng], 15);
        }

        // --- Fitur Akesibilitas: Voice Input (Web Speech API) - WhatsApp Style ---
        let recognition = null;
        let isRecording = false;

        function setupSpeechRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                console.warn("Browser tidak mendukung Web Speech API.");
                return;
            }

            recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            const btnVoice = document.getElementById('btn-voice');
            const iconVoice = document.getElementById('icon-voice');
            const textArea = document.getElementById('emergency-text');
            const overlay = document.getElementById('recording-overlay');
            const timerEl = document.getElementById('recording-timer');
            let timerInterval;
            let recordingSeconds = 0;

            recognition.onstart = function() {
                isRecording = true;
                
                // Tampilkan Overlay UI WhatsApp (Menutupi Textarea)
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                
                // Mulai Timer
                recordingSeconds = 0;
                timerEl.textContent = "0:00";
                timerInterval = setInterval(() => {
                    recordingSeconds++;
                    let m = Math.floor(recordingSeconds / 60);
                    let s = recordingSeconds % 60;
                    timerEl.textContent = m + ":" + (s < 10 ? "0" : "") + s;
                }, 1000);

                // UI Visual cue saat merekam (WhatsApp Style: Membulat sempurna, Hijau WA, Membesar menonjol)
                btnVoice.classList.add('bg-green-500', 'dark:bg-green-600', 'border-green-500', 'scale-125', 'rounded-full', 'shadow-lg');
                btnVoice.classList.remove('bg-gray-100', 'dark:bg-darkbg', 'rounded-lg');
                
                iconVoice.classList.replace('text-gray-600', 'text-white');
                iconVoice.classList.replace('dark:text-gray-300', 'text-white');
                iconVoice.classList.replace('text-red-500', 'text-white'); // Fallback pembersihan sebelumnya
                iconVoice.classList.add('animate-pulse');
            };

            recognition.onresult = function(event) {
                const speechResult = event.results[0][0].transcript;
                // Tambahkan spasi jika sudah ada teks
                textArea.value += (textArea.value.length > 0 ? " " : "") + speechResult;
            };

            recognition.onspeechend = function() {
                if(isRecording) stopRecording();
            };

            recognition.onend = function() {
                isRecording = false;
                
                // Sembunyikan Overlay WhatsApp UI
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                clearInterval(timerInterval);
                
                // Kembalikan UI ke tombol kotak abu-abu normal
                btnVoice.classList.remove('bg-green-500', 'dark:bg-green-600', 'border-green-500', 'scale-125', 'rounded-full', 'shadow-lg');
                btnVoice.classList.add('bg-gray-100', 'dark:bg-darkbg', 'rounded-lg');
                
                iconVoice.classList.replace('text-white', 'text-gray-600');
                if(htmlRoot.classList.contains('dark')){
                   iconVoice.classList.replace('text-gray-600', 'dark:text-gray-300'); 
                }
                
                iconVoice.classList.remove('animate-pulse');
            };

            recognition.onerror = function(event) {
                console.error("Speech Recognition Error:", event.error);
                if (event.error !== 'aborted') { // Aborted biasa terjadi dari stop() manual
                    alert("Gagal mendengarkan: " + event.error);
                }
                stopRecording();
            };

            // Event Listeners untuk Hold-to-Talk pada btnVoice
            const startRecording = (e) => {
                e.preventDefault(); // Mencegah double trigger di mobile
                if (!isRecording && recognition) {
                    try { recognition.start(); } catch(e) {}
                }
            };

            const stopRecording = (e) => {
                if (e) e.preventDefault();
                if (isRecording && recognition) {
                    try { recognition.stop(); } catch(e) {}
                    isRecording = false;
                }
            };

            // Support untuk Mouse Desktop
            btnVoice.addEventListener('mousedown', startRecording);
            btnVoice.addEventListener('mouseup', stopRecording);
            btnVoice.addEventListener('mouseleave', stopRecording); // Jika kursor keluar tombol saat menahan

            // Support untuk Touch Mobile
            btnVoice.addEventListener('touchstart', startRecording, {passive: false});
            btnVoice.addEventListener('touchend', stopRecording);
            btnVoice.addEventListener('touchcancel', stopRecording);
        }

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        console.log("Lokasi ditemukan:", lat, lng);
                        initMap(lat, lng);
                    },
                    (error) => {
                        console.warn("Gagal mendapatkan lokasi:", error.message);
                        // Hapus alert bawaan agar tidak mengganggu jika gagal
                        console.log("Tidak dapat mengakses GPS. Menggunakan lokasi default (Jakarta).");
                        initMap(defaultLocation[0], defaultLocation[1]);
                    },
                    // Desktop (terutama tanpa Wi-Fi card) sering gagal jika High Accuracy dipaksa true
                    { enableHighAccuracy: false, timeout: 15000, maximumAge: 0 }
                );
            } else {
                console.warn("Geolocation tidak didukung oleh browser ini.");
                initMap(defaultLocation[0], defaultLocation[1]);
            }
        }

        // Panggil saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            getLocation();
            setupSpeechRecognition(); // Inisialisasi WhatsApp Style Voice Input
        });
    </script>
</body>
</html>