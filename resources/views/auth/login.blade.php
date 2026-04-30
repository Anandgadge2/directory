<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Forest Guard Analytics</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f2f6f3;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Soft vignette with a little green */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center,
                    rgba(255, 255, 255, 0) 55%,
                    rgba(238, 246, 240, 0.9) 100%);
            pointer-events: none;
            z-index: 0;
        }

        /* Subtle nature-inspired shapes */
        .bg-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(79, 111, 82, 0.08);
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -50px;
            right: -50px;
            animation-delay: 5s;
        }

        .shape:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            right: 10%;
            animation-delay: 10s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-30px) rotate(180deg);
            }
        }

        .login-container {
            background: #fcfefc;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(47, 62, 47, 0.06);
            padding: 36px;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
            border: 1px solid rgba(79, 111, 82, 0.12);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-container .logo {
            display: block;
            margin: 0 auto 12px;
            max-width: 200px;
            height: auto;
            object-fit: contain;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2f1f;
            margin-bottom: 6px;
        }

        .subtitle {
            color: #4a5568;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1f2f1f;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #4f6f52;
            font-size: 18px;
        }

        input[type="text"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #fff;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #4f6f52;
            box-shadow: 0 0 0 4px rgba(79, 111, 82, 0.12);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            cursor: pointer;
            z-index: 10;
            padding: 4px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #4f6f52;
        }

        .error-message {
            color: #e53e3e;
            font-size: 13px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .error-message::before {
            content: "⚠";
        }

        .input-wrapper.error input {
            border-color: #e53e3e;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #4f6f52;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(79, 111, 82, 0.25);
            margin-top: 4px;
        }

        .btn-login:hover {
            background: #3f5640;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 111, 82, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: #718096;
            font-size: 12px;
        }

        .forgot-link {
            display: block;
            text-align: right;
            margin-top: 8px;
            font-size: 13px;
            color: #4f6f52;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 28px 20px;
            }

            h1 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="logo-container">
            <img src="{{ asset('images/logo.png') }}" alt="PugArch" class="logo">
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to access Forest Guard Analytics</p>
        </div>

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-wrapper {{ $errors->has('phone') ? 'error' : '' }}">
                    <i class="input-icon bi bi-telephone-fill"></i>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                        placeholder="Enter 10-digit number" required autofocus pattern="[0-9]{10}" minlength="10"
                        maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                        title="Exactly 10 digits required">
                </div>
                @error('phone')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper {{ $errors->has('password') ? 'error' : '' }}">
                    <i class="input-icon bi bi-lock-fill"></i>
                    <input type="password" id="password" name="password" placeholder="" required minlength="5"
                        maxlength="12">
                    <i class="toggle-password bi bi-eye" id="togglePassword"></i>
                </div>
                <a href="{{ route('forgotPassword') }}" class="forgot-link">Forgot Password?</a>
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-login">
                Sign In
            </button>
        </form>

        <div class="footer-text">
            © 2026 Forest Guard Analytics. All rights reserved.
        </div>
    </div>

    <script>
        // Double-enforce numeric input for better mobile experience
        document.getElementById('phone')?.addEventListener('keypress', function (e) {
            if (e.which < 48 || e.which > 57) e.preventDefault();
        });

        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle icon
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }
    </script>
</body>

</html>