<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | Safeguard Monitoring System</title>

    <!-- Favicon Geo Dipa -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#087c68">

    <meta
        name="description"
        content="Halaman login Safeguard Monitoring System."
    >
    <meta name="application-name" content="Safeguard Monitoring System - HSSE PROJECT">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --primary: #087c68;
            --primary-hover: #066653;
            --text-dark: #263746;
            --text-muted: #718096;
            --border-color: #d5dde5;
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            padding: 28px 16px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

            background:
                linear-gradient(
                    180deg,
                    rgba(5, 35, 57, 0.48),
                    rgba(4, 28, 43, 0.78)
                ),
                url("{{ asset('images/backgrounds/login-dieng.webp') }}")
                center center / cover no-repeat fixed;
        }

        /* Pembungkus halaman login */
        .login-wrapper {
            width: 100%;
            max-width: 390px;
            margin: auto;
        }

        /* Identitas perusahaan */
        .brand {
            margin-bottom: 25px;
            text-align: center;
            color: #fff;
        }

        .brand-logo {
            display: block;
            width: 90px;
            height: 90px;
            margin: 0 auto 12px;

            object-fit: contain;
            background: transparent;
            border: none;
        }

        .brand h1 {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.4);
        }

        .brand p {
            margin: 0;
            color: #f0f4f7;
            font-size: 12px;
            line-height: 1.6;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        }

        /* Kartu login */
        .login-card {
            width: 100%;
            padding: 26px;

            background: #fff;
            border: none;
            border-radius: 10px;

            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.24);
        }

        .login-title {
            margin-bottom: 6px;
            color: var(--text-dark);
            font-size: 19px;
            font-weight: 700;
        }

        .login-subtitle {
            margin-bottom: 22px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        /* Label dan input */
        .form-label {
            margin-bottom: 7px;
            color: var(--text-dark);
            font-size: 12px;
            font-weight: 600;
        }

        .form-control {
            min-height: 42px;
            padding: 10px 12px;

            border: 1px solid var(--border-color);
            border-radius: 5px;

            color: var(--text-dark);
            font-size: 12px;
        }

        .form-control::placeholder {
            color: #9aa6b2;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(8, 124, 104, 0.12);
        }

        /* Input password dan tombol tampilkan password */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 72px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);

            padding: 4px 0;
            border: none;
            background: transparent;

            color: var(--primary);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .toggle-password:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
        }

        /* Ingat saya dan lupa password */
        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            margin: 4px 0 21px;
            font-size: 11px;
        }

        .form-check {
            min-height: auto;
            margin: 0;
        }

        .form-check-input {
            margin-top: 2px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            color: #475569;
            cursor: pointer;
        }

        .forgot-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .forgot-link-btn {
            padding: 2px 0;
            border: none;
            background: transparent;
            font: inherit;
            cursor: pointer;
        }

        .forgot-link:hover,
        .forgot-link-btn:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .forgot-link-btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
            border-radius: 3px;
        }

        /* Modal Lupa Password */
        .forgot-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1050;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(4, 28, 43, 0.62);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        .forgot-modal-backdrop.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .forgot-modal-dialog {
            width: 100%;
            max-width: 390px;
            max-height: calc(100dvh - 32px);
            overflow-y: auto;
            padding: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 24px 50px rgba(4, 28, 43, 0.34);
            transform: translateY(8px) scale(0.98);
            transition: transform 0.2s ease;
        }

        .forgot-modal-backdrop.is-open .forgot-modal-dialog {
            transform: translateY(0) scale(1);
        }

        .forgot-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .forgot-modal-title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .forgot-modal-badge {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(8, 124, 104, 0.1);
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .forgot-modal-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.25;
        }

        .forgot-modal-close-x {
            width: 32px;
            height: 32px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .forgot-modal-close-x:hover {
            background: #e2e8f0;
            color: var(--text-dark);
        }

        .forgot-modal-close-x:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .forgot-modal-desc {
            margin: 0 0 16px;
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
        }

        .forgot-contact-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 15px;
            margin-bottom: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .forgot-wa-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: #dcfce7;
            color: #16a34a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .forgot-contact-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .forgot-contact-number {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: 0.25px;
            line-height: 1.3;
        }

        .btn-forgot-close {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--primary);
            border-radius: 6px;
            background: var(--primary);
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-forgot-close:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-forgot-close:focus-visible {
            outline: 3px solid rgba(8, 124, 104, 0.3);
            outline-offset: 2px;
        }

        @media (max-width: 420px) {
            .forgot-modal-dialog {
                padding: 20px 18px;
            }
        }

        /* Tombol masuk */
        .btn-login {
            width: 100%;
            min-height: 43px;

            border: 1px solid var(--primary);
            border-radius: 5px;

            background: var(--primary);
            color: #fff;

            font-size: 13px;
            font-weight: 700;

            transition:
                background-color 0.2s ease,
                border-color 0.2s ease;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            color: #fff;
        }

        .btn-login:focus-visible {
            outline: 3px solid rgba(8, 124, 104, 0.3);
            outline-offset: 2px;
        }

        /* Pesan validasi */
        .alert {
            margin-bottom: 18px;
            padding: 10px 12px;
            border-radius: 5px;
            font-size: 12px;
            line-height: 1.5;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        /* Footer */
        .login-footer {
            margin-top: 20px;
            color: #f0f4f7;
            font-size: 11px;
            text-align: center;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        }

        /* Tampilan layar kecil */
        @media (max-width: 480px) {
            body {
                padding: 24px 16px;
                background-attachment: scroll;
            }

            .login-wrapper {
                max-width: 350px;
            }

            .brand {
                margin-bottom: 20px;
            }

            .brand-logo {
                width: 82px;
                height: 82px;
            }

            .brand h1 {
                font-size: 19px;
            }

            .login-card {
                padding: 22px;
            }
        }

        /* Mengurangi animasi bagi pengguna yang memilihnya */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }

        .login-options {
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 420px) {
            body {
                padding: 16px 12px;
            }
            .login-card {
                padding: 22px 16px;
            }
            .btn-login {
                min-height: 44px;
            }
        }
    </style>
</head>

<body>
    <main class="login-wrapper">

        <!-- Identitas perusahaan -->
        <header class="brand">
            <img
                src="{{ asset('images/logo/geo-dipa.png') }}"
                alt="Logo Geo Dipa Energi"
                class="brand-logo"
            >

            <h1>Safeguard Monitoring System</h1>
        </header>

        <!-- Form login -->
        <section class="login-card" aria-labelledby="login-heading">

            <h2 id="login-heading" class="login-title">Login</h2>

            <p class="login-subtitle">
                Masuk untuk mengakses Safeguard Monitoring System.
            </p>

            <!-- Pesan error dari Laravel -->
            @if ($errors->any())
                <div
                    class="alert alert-danger"
                    role="alert"
                    aria-live="polite"
                >
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.process') }}" method="POST">
                @csrf

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="nama@perusahaan.com"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        aria-describedby="email-error"
                        required
                        autofocus
                    >

                    @error('email')
                        <div id="email-error" class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password
                    </label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            id="togglePassword"
                            class="toggle-password"
                            aria-controls="password"
                            aria-pressed="false"
                        >
                            Lihat
                        </button>
                    </div>
                </div>

                <!-- Opsi login -->
                <div class="login-options">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="remember"
                            name="remember"
                            value="1"
                            @checked(old('remember'))
                        >

                        <label
                            class="form-check-label"
                            for="remember"
                        >
                            Ingat saya
                        </label>
                    </div>

                    <button
                        type="button"
                        id="forgotPasswordBtn"
                        class="forgot-link forgot-link-btn"
                        aria-haspopup="dialog"
                        aria-controls="forgotPasswordModal"
                    >
                        Lupa password?
                    </button>
                </div>

                <!-- Tombol submit -->
                <button type="submit" class="btn btn-login">
                    Masuk
                </button>
            </form>
        </section>

        <!-- Footer -->
        <footer class="login-footer">
            Safeguard Monitoring System &copy; {{ date('Y') }}
        </footer>

    </main>

    <!-- Popup / Modal Lupa Password -->
    <div
        id="forgotPasswordModal"
        class="forgot-modal-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="forgotPasswordModalTitle"
        aria-hidden="true"
    >
        <div class="forgot-modal-dialog">
            <div class="forgot-modal-header">
                <div class="forgot-modal-title-group">
                    <span class="forgot-modal-badge" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <h3 id="forgotPasswordModalTitle" class="forgot-modal-title">Lupa Password?</h3>
                </div>
                <button
                    type="button"
                    id="forgotModalCloseIcon"
                    class="forgot-modal-close-x"
                    aria-label="Tutup popup"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <p class="forgot-modal-desc">
                Untuk bantuan penggantian password, silakan hubungi Administrator HSSE.
            </p>

            <div class="forgot-contact-box">
                <span class="forgot-wa-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.79 14.01c-.24.68-1.42 1.3-1.97 1.38-.52.08-1.18.11-1.91-.12-.45-.14-1.02-.34-1.75-.66-3.07-1.32-5.08-4.42-5.23-4.63-.15-.21-1.25-1.66-1.25-3.17s.79-2.25 1.07-2.55c.28-.3.61-.38.82-.38.2 0 .41 0 .59.01.19.01.44-.07.69.53.25.61.86 2.1.94 2.25.08.15.13.33.03.53-.1.21-.15.33-.31.51-.15.18-.32.4-.46.54-.15.15-.31.32-.13.62.18.3.79 1.31 1.7 2.12 1.17 1.04 2.15 1.37 2.46 1.52.3.15.48.13.66-.08.18-.2.76-.88.96-1.18.2-.3.41-.25.69-.15.28.1 1.78.84 2.09.99.3.15.51.23.58.36.08.13.08.76-.16 1.44z"/>
                    </svg>
                </span>
                <div>
                    <div class="forgot-contact-label">Nomor Admin Alfian(WhatsApp)</div>
                    <div class="forgot-contact-number">085 60666 XXXX</div>
                </div>
            </div>

            <button type="button" id="forgotModalCloseBtn" class="btn-forgot-close">
                Tutup
            </button>
        </div>
    </div>

    <!-- Interaksi tampilkan password & modal lupa password -->
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';
            togglePassword.textContent = isPassword ? 'Sembunyikan' : 'Lihat';
            togglePassword.setAttribute('aria-pressed', String(isPassword));
        });

        (function () {
            const triggerBtn = document.getElementById('forgotPasswordBtn');
            const modalEl = document.getElementById('forgotPasswordModal');
            const closeBtn = document.getElementById('forgotModalCloseBtn');
            const closeIconBtn = document.getElementById('forgotModalCloseIcon');

            if (!triggerBtn || !modalEl) return;

            function openForgotModal() {
                modalEl.classList.add('is-open');
                modalEl.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                closeBtn?.focus();
            }

            function closeForgotModal() {
                modalEl.classList.remove('is-open');
                modalEl.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                triggerBtn.focus();
            }

            triggerBtn.addEventListener('click', openForgotModal);
            closeBtn?.addEventListener('click', closeForgotModal);
            closeIconBtn?.addEventListener('click', closeForgotModal);

            modalEl.addEventListener('click', function (e) {
                if (e.target === modalEl) {
                    closeForgotModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modalEl.classList.contains('is-open')) {
                    closeForgotModal();
                }
            });
        })();
    </script>
</body>
</html>
