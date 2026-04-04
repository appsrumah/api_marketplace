<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menghubungkan Akun — Kios Q</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .card {
            text-align: center;
            padding: 3rem 2.5rem;
            max-width: 420px;
            width: 100%;
        }
        .spinner-ring {
            width: 72px;
            height: 72px;
            border: 4px solid rgba(99, 179, 237, 0.15);
            border-top-color: #63b3ed;
            border-right-color: #4299e1;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto 2rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .steps {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            text-align: left;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            background: rgba(255,255,255,0.05);
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        .step.active {
            background: rgba(99, 179, 237, 0.15);
            border: 1px solid rgba(99, 179, 237, 0.3);
        }
        .step.done {
            background: rgba(52, 211, 153, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.2);
        }
        .step-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.75rem;
        }
        .step-icon.pending { background: rgba(255,255,255,0.1); }
        .step-icon.active { background: #3b82f6; animation: pulse 1s ease infinite; }
        .step-icon.done { background: #10b981; }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.6; } }
        h2 { font-size: 1.375rem; font-weight: 700; margin-bottom: 0.5rem; }
        p { color: rgba(255,255,255,0.55); font-size: 0.875rem; line-height: 1.5; }
        .warning {
            margin-top: 2rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.2);
            font-size: 0.75rem;
            color: rgba(251, 191, 36, 0.9);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner-ring"></div>

        <h2>Menghubungkan Akun TikTok</h2>
        <p>Mohon tunggu, sedang memproses otentikasi dan mengambil data produk Anda...</p>

        <div class="steps">
            <div class="step done" id="step1">
                <div class="step-icon done">✓</div>
                <span>Menerima otorisasi dari TikTok</span>
            </div>
            <div class="step active" id="step2">
                <div class="step-icon active">⟳</div>
                <span>Menyimpan access token ke database</span>
            </div>
            <div class="step" id="step3">
                <div class="step-icon pending">3</div>
                <span>Mengambil informasi toko (shop cipher)</span>
            </div>
            <div class="step" id="step4">
                <div class="step-icon pending">4</div>
                <span>Mengambil semua produk dari TikTok</span>
            </div>
        </div>

        <div class="warning">
            ⏳ Proses ini bisa memakan waktu 1–3 menit tergantung jumlah produk. Jangan tutup halaman ini.
        </div>
    </div>

    <script>
        // Animasi progress step
        const steps = [
            document.getElementById('step2'),
            document.getElementById('step3'),
            document.getElementById('step4'),
        ];
        let current = 0;
        function progressStep() {
            if (current >= steps.length) return;
            const el = steps[current];
            el.classList.add('active');
            el.querySelector('.step-icon').classList.remove('pending');
            el.querySelector('.step-icon').classList.add('active');
            el.querySelector('.step-icon').textContent = '⟳';
            if (current > 0) {
                const prev = steps[current - 1];
                prev.classList.remove('active');
                prev.classList.add('done');
                prev.querySelector('.step-icon').classList.remove('active');
                prev.querySelector('.step-icon').classList.add('done');
                prev.querySelector('.step-icon').textContent = '✓';
            }
            current++;
        }
        // Kemajuan simulasi visual
        setTimeout(progressStep, 1800);
        setTimeout(progressStep, 4000);
    </script>
</body>
</html>
