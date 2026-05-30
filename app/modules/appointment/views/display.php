<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?? 'Layar Antrean Utama - SIMRS' ?></title>
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #0b132b;
            --card-bg: rgba(28, 37, 65, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #8ecae6;
            --primary-color: #2196f3;
            --accent-color: #00e676;
            --accent-orange: #ff9100;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: radial-gradient(circle at top left, #1c2541, #0b132b);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 24px;
        }

        /* Header Layout */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            backdrop-filter: blur(12px);
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .hospital-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .hospital-logo {
            font-size: 32px;
            color: var(--primary-color);
            background: rgba(33, 150, 243, 0.15);
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }

        .hospital-name h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
            background: linear-gradient(45deg, #ffffff, #8ecae6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hospital-name p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .clock-container {
            text-align: right;
        }

        #clock-time {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }

        #clock-date {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Main Workspace Grid */
        .display-grid {
            display: grid;
            grid-template-columns: 4.5fr 7.5fr;
            gap: 24px;
            flex: 1;
            min-height: 0; /* Important for scroll containment */
        }

        /* Left Side: Call Spotlight */
        .spotlight-panel {
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            padding: 30px;
            text-align: center;
            justify-content: center;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .spotlight-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .spotlight-header {
            font-size: 18px;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        .spotlight-ticket {
            font-size: 96px;
            font-weight: 900;
            color: var(--accent-orange);
            text-shadow: 0 0 30px rgba(255, 145, 0, 0.4);
            margin: 20px 0;
            letter-spacing: -2px;
            animation: pulse-glow 2s infinite alternate;
        }

        @keyframes pulse-glow {
            from {
                text-shadow: 0 0 20px rgba(255, 145, 0, 0.3);
                transform: scale(1);
            }
            to {
                text-shadow: 0 0 40px rgba(255, 145, 0, 0.6);
                transform: scale(1.03);
            }
        }

        .spotlight-destination {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .spotlight-doctor {
            font-size: 18px;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 16px;
            border-radius: 30px;
            display: inline-block;
            align-self: center;
            margin-top: 15px;
        }

        /* Right Side: Polyclinics Queue Grid */
        .queues-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .panel-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .queues-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            overflow-y: auto;
            flex: 1;
            padding-right: 5px;
        }

        /* Custom Scrollbar for Grid */
        .queues-grid::-webkit-scrollbar {
            width: 8px;
        }
        .queues-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        .queues-grid::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .poly-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            backdrop-filter: blur(12px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            transition: all 0.3s;
        }

        .poly-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-color);
            border-radius: 4px 0 0 4px;
        }

        .poly-card.active-calling::after {
            background: var(--accent-color);
            box-shadow: 0 0 15px var(--accent-color);
        }

        .poly-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .poly-code {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .poly-ticket {
            font-size: 48px;
            font-weight: 800;
            color: var(--text-primary);
            text-align: right;
            margin-top: 10px;
            font-variant-numeric: tabular-nums;
        }

        .poly-ticket.empty {
            color: rgba(255, 255, 255, 0.2);
            font-size: 32px;
            font-weight: 500;
        }

        /* Footer announcement line */
        footer {
            margin-top: 24px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(12px);
        }

        .announcement-badge {
            background: var(--accent-orange);
            color: #000000;
            font-size: 12px;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        marquee {
            font-size: 16px;
            color: var(--text-secondary);
            font-weight: 500;
        }
    </style>
</head>
<body>

    <!-- Header Block -->
    <header>
        <div class="hospital-brand">
            <div class="hospital-logo">
                <i class="fa-solid fa-hospital-user"></i>
            </div>
            <div class="hospital-name">
                <h1>SIMRS RUMAH SAKIT SEHAT</h1>
                <p>Sistem Layanan Papan Informasi Antrean Real-Time Elektronik</p>
            </div>
        </div>
        <div class="clock-container">
            <div id="clock-time">00:00:00</div>
            <div id="clock-date">Memuat Tanggal...</div>
        </div>
    </header>

    <!-- Main Grid -->
    <div class="display-grid">
        <!-- Spotlight Panel -->
        <div class="spotlight-panel">
            <div class="spotlight-header">
                <i class="fa-solid fa-volume-high"></i> Panggilan Utama
            </div>
            <div id="spotlight-ticket" class="spotlight-ticket">-</div>
            <div id="spotlight-destination" class="spotlight-destination">Silakan Menunggu</div>
            <div id="spotlight-doctor" class="spotlight-doctor">Petunjuk suara otomatis diaktifkan</div>
        </div>

        <!-- Polyclinics Grid Panel -->
        <div class="queues-panel">
            <div class="panel-title">
                <i class="fa-solid fa-layer-group"></i> Layanan Poliklinik Aktif Hari Ini
            </div>
            <div class="queues-grid" id="queues-grid-container">
                <?php foreach ($polyclinics as $poly): ?>
                    <div class="poly-card" id="poly-card-<?= (int)$poly['id'] ?>" data-poly-id="<?= (int)$poly['id'] ?>">
                        <div>
                            <div class="poly-name"><?= e($poly['name']) ?></div>
                            <div class="poly-code">Kode Loket: <?= e($poly['code']) ?></div>
                        </div>
                        <div class="poly-ticket empty" id="poly-ticket-<?= (int)$poly['id'] ?>">-</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Announcement Bar -->
    <footer>
        <div class="announcement-badge">
            <i class="fa-solid fa-bullhorn"></i> Pengumuman
        </div>
        <marquee scrollamount="5">
            Selamat datang di Rumah Sakit Sehat. Mohon untuk selalu menyiapkan kartu identitas, kartu asuransi/BPJS, serta lembar rujukan saat mendaftar di loket pelayanan. Terima kasih atas kepercayaan Anda.
        </marquee>
    </footer>

    <script>
        // Local state
        let lastCalledTicketId = null;

        // Clock Update
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            document.getElementById('clock-time').textContent = `${hours}:${minutes}:${seconds}`;
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('clock-date').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Indonesian Text-to-Speech
        function speakTicket(ticketNumber, polyclinicName) {
            if (!('speechSynthesis' in window)) {
                console.warn('Speech synthesis not supported in this browser.');
                return;
            }

            // Split ticket code & number. E.g. "A-003" -> Code: A, Number: 3
            const parts = ticketNumber.split('-');
            const letter = parts[0] || '';
            const rawNum = parts[1] || '';
            const num = parseInt(rawNum) || 0;

            // Voice announcement text
            const text = `Nomor antrean, ${letter}, ${num}. Silakan menuju ke, ${polyclinicName}`;

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.85; // Slightly slower for absolute clarity
            utterance.pitch = 1.0;

            // Attempt to find Indonesian voice
            const voices = window.speechSynthesis.getVoices();
            const idVoice = voices.find(voice => voice.lang.includes('id') || voice.lang.includes('ID'));
            if (idVoice) {
                utterance.voice = idVoice;
            }

            window.speechSynthesis.speak(utterance);
        }

        // Fetch queues updates
        function pollQueues() {
            fetch('<?= url("queue/active-calls") ?>')
                .then(res => res.json())
                .then(data => {
                    // 1. Reset all polycards status in UI
                    const cards = document.querySelectorAll('.poly-card');
                    cards.forEach(card => {
                        card.classList.remove('active-calling');
                    });

                    // 2. Update each active polyclinic card
                    if (data.calls && data.calls.length > 0) {
                        data.calls.forEach(call => {
                            const polyId = call.polyclinic_id;
                            const ticketEl = document.getElementById(`poly-ticket-${polyId}`);
                            const cardEl = document.getElementById(`poly-card-${polyId}`);
                            
                            if (ticketEl) {
                                ticketEl.textContent = call.queue_number;
                                ticketEl.classList.remove('empty');
                            }
                            if (cardEl) {
                                cardEl.classList.add('active-calling');
                            }
                        });
                    }

                    // 3. Update Spotlight (Latest Called Ticket)
                    if (data.latestCall) {
                        const spotTicket = document.getElementById('spotlight-ticket');
                        const spotDest = document.getElementById('spotlight-destination');
                        const spotDoc = document.getElementById('spotlight-doctor');

                        spotTicket.textContent = data.latestCall.queue_number;
                        spotDest.textContent = data.latestCall.polyclinic_name;
                        spotDoc.textContent = data.latestCall.doctor_name ? `Dokter: Dr. ${data.latestCall.doctor_name}` : 'Petunjuk suara otomatis diaktifkan';

                        // Check if this is a new call
                        if (lastCalledTicketId !== null && lastCalledTicketId !== data.latestCall.id) {
                            // Flash spotlight container
                            const spotlight = document.querySelector('.spotlight-panel');
                            spotlight.style.backgroundColor = 'rgba(33, 150, 243, 0.3)';
                            setTimeout(() => {
                                spotlight.style.backgroundColor = '';
                            }, 500);

                            // Announce the ticket number
                            speakTicket(data.latestCall.queue_number, data.latestCall.polyclinic_name);
                        }

                        lastCalledTicketId = data.latestCall.id;
                    }
                })
                .catch(err => console.error('Error polling queue display:', err));
        }

        // Run initially, then poll every 3 seconds
        pollQueues();
        setInterval(pollQueues, 3000);

        // Pre-load voices for SpeechSynthesis
        if ('speechSynthesis' in window) {
            window.speechSynthesis.getVoices();
        }
    </script>
</body>
</html>
