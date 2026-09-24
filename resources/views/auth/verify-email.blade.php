<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email</title>
    <style>
        :root {
            --sage: #84a98c;
            --sage-dark: #5a7a62;
            --sage-soft: rgba(132,169,140,0.12);
            --cream: #fefdf8;
            --text: #4b5563;
            --border: #e5e4e2;
        }
        * { box-sizing: border-box; font-family: -apple-system, 'Segoe UI', sans-serif; }
        body {
            margin: 0; min-height: 100vh; color: var(--text);
            display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;
            background-color: var(--cream);
            background-image:
                radial-gradient(circle at 8% 8%, rgba(132,169,140,0.18), transparent 42%),
                radial-gradient(circle at 92% 90%, rgba(212,160,23,0.14), transparent 45%);
            background-attachment: fixed;
        }

        .login-card {
            width: 100%; max-width: 420px; background: #fff; border-radius: 18px;
            padding: 2.25rem 2rem; box-shadow: 0 10px 35px rgba(0,0,0,0.06); text-align: center;
        }
        .logo-wrap { width: 100%; max-width: 170px; margin: 0 auto 1.1rem; }
        .logo-wrap img { width: 100%; height: auto; display: block; }
        .icon-wrap {
            width: 52px; height: 52px; border-radius: 50%; background: var(--sage-soft); color: var(--sage-dark);
            display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;
        }
        .icon-wrap svg { width: 26px; height: 26px; }
        h1 { font-size: 20px; margin: 0 0 10px; color: #1f2937; }
        .sub-line { font-size: 13.5px; color: #9ca3af; margin: 0 0 1.5rem; line-height: 1.6; }

        .status-text { background: var(--sage-soft); color: var(--sage-dark); font-size: 13px; padding: 10px 12px; border-radius: 10px; margin-bottom: 1.25rem; }

        .btn-masuk {
            width: 100%; background: var(--sage); color: #fff; border: none; padding: 13px;
            border-radius: 12px; font-size: 14.5px; font-weight: 600; cursor: pointer;
            transition: background 0.18s ease, transform 0.08s ease;
        }
        .btn-masuk:hover { background: var(--sage-dark); }
        .btn-masuk:active { transform: scale(0.99); }

        .btn-logout {
            width: 100%; background: none; border: none; color: #9ca3af; font-size: 13px;
            padding: 10px; margin-top: 6px; cursor: pointer; border-radius: 10px;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .btn-logout:hover { background: #FBEAEA; color: #B91C1C; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-wrap">
            <img src="{{ asset('images/logo-nada.png') }}" alt="NADA - Nota Arsip Digital Akuntansi">
        </div>

        <div class="icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg>
        </div>

        <h1>Verifikasi email Anda</h1>
        <p class="sub-line">Sebelum melanjutkan, silakan verifikasi alamat email Anda melalui tautan yang telah kami kirimkan. Jika email belum diterima, kami dapat mengirimkannya kembali.</p>

        @if (session('status') == 'verification-link-sent')
            <div class="status-text">Tautan verifikasi baru telah dikirim ke alamat email yang terdaftar.</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-masuk">Kirim ulang email verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
</body>
</html>