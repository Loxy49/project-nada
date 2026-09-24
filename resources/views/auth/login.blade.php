<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - NADA</title>
    <style>
        :root {
            --sage: #84a98c;
            --sage-dark: #5a7a62;
            --sage-soft: rgba(132,169,140,0.12);
            --text: #4b5563;
            --border: #e5e4e2;
            --accent: #d4a017;
            --accent-dark: #b8890f;
        }
        * { box-sizing: border-box; font-family: -apple-system, 'Segoe UI', sans-serif; }
        body { margin: 0; min-height: 100vh; color: var(--text); background: #fff; }

        /* ===== Wrapper: semua di tengah halaman ===== */
        .page-wrap {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 2rem 1.5rem; background: #fff;
        }
        .login-card {
            width: 100%; max-width: 400px; background: #fff;
            border: 1px solid var(--border); border-radius: 20px;
            padding: 2.25rem 2rem; box-shadow: 0 8px 30px rgba(0,0,0,0.04);
        }
        .brand-logo { display: block; width: 130px; height: auto; margin: 0 auto 1.5rem; }
        .form-sub { font-size: 13.5px; color: #9ca3af; margin: 0 0 1.75rem; text-align: center; }

        .status-text { background: var(--sage-soft); color: var(--sage-dark); font-size: 13px; padding: 10px 12px; border-radius: 10px; margin-bottom: 1.25rem; }

        .field { margin-bottom: 16px; text-align: left; }
        .field label { font-size: 13.5px; color: #374151; font-weight: 500; display: block; margin-bottom: 6px; }
        .field label .req { color: #d97a6a; }

        .input-wrap { position: relative; }
        .input-wrap .input-icon-left {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #9ca3af; display: flex;
        }
        .input-wrap .input-icon-left svg { width: 17px; height: 17px; }
        .input-wrap input {
            width: 100%; padding: 11px 14px 11px 40px; border: 1px solid var(--border); border-radius: 10px;
            font-size: 14px; background: #fff; transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .input-wrap input:focus { outline: none; border-color: var(--sage); box-shadow: 0 0 0 3px var(--sage-soft); }
        .input-wrap input.has-toggle { padding-right: 42px; }
        .toggle-eye {
            position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
            width: 30px; height: 30px; border: none; background: none; cursor: pointer;
            color: #9ca3af; display: flex; align-items: center; justify-content: center; border-radius: 8px;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .toggle-eye:hover { background: var(--sage-soft); color: var(--sage-dark); }
        .toggle-eye svg { width: 18px; height: 18px; }

        .error-text { color: #d97a6a; font-size: 12px; margin-top: 5px; text-align: left; }

        .forgot-row { text-align: right; margin: -6px 0 18px; }
        .forgot-row a { font-size: 12.5px; color: var(--accent); font-weight: 600; text-decoration: none; }
        .forgot-row a:hover { color: var(--accent-dark); text-decoration: underline; }

        .remember-row { display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #4b5563; margin: 0 0 20px; text-align: left; }
        .remember-row input { accent-color: var(--sage); width: 15px; height: 15px; cursor: pointer; }
        .remember-row label { cursor: pointer; }

        .btn-masuk {
            width: 100%; background: var(--sage); color: #fff; border: none; padding: 13px;
            border-radius: 12px; font-size: 14.5px; font-weight: 600; cursor: pointer;
            transition: background 0.18s ease, transform 0.08s ease;
        }
        .btn-masuk:hover { background: var(--sage-dark); }
        .btn-masuk:active { transform: scale(0.99); }

        .bottom-line { text-align: center; font-size: 13.5px; color: #9ca3af; margin: 1.75rem 0 0; }
        .bottom-line a { color: var(--accent); font-weight: 600; text-decoration: none; }
        .bottom-line a:hover { color: var(--accent-dark); text-decoration: underline; }

        @media (max-width: 480px) {
            /* di HP: card tanpa border supaya terasa lega */
            .login-card { border: none; box-shadow: none; padding: 1rem 0; }
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="login-card">
            <img class="brand-logo" src="{{ asset('images/logo-nada.png') }}" alt="NADA - Nota Arsip Digital Akuntansi">
            @if (session('status'))
                <div class="status-text">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field">
                    <label for="email">Alamat email <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="input-icon-left">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg>
                        </span>
                        <input id="email" type="email" name="email" placeholder="nama@kantor.com" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    @error('email') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="password">Kata sandi <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="input-icon-left">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="9.5" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
                        </span>
                        <input id="password" class="has-toggle" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                        <button type="button" class="toggle-eye" onclick="toggleLihatSandi()" aria-label="Lihat/sembunyikan kata sandi">
                            <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                @if (Route::has('password.request'))
                    <div class="forgot-row">
                        <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
                    </div>
                @endif

                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Ingat saya</label>
                </div>

                <button type="submit" class="btn-masuk">Masuk</button>
            </form>

            <p class="bottom-line">Belum punya akun? Hubungi admin.</p>
        </div>
    </div>

    <script>
        function toggleLihatSandi() {
            const input = document.getElementById('password');
            const eye = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                eye.innerHTML = '<path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 4.24A9.9 9.9 0 0 1 12 4c6.4 0 10 7 10 7a17.7 17.7 0 0 1-3.1 4.09M6.6 6.6C4.1 8.1 2 11 2 11s3.6 7 10 7a9.7 9.7 0 0 0 3.4-.6"/>';
            } else {
                input.type = 'password';
                eye.innerHTML = '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
</body>
</html>