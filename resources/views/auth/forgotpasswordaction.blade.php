<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Guard Analytics</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --forest-green: #4f6f52;
            --forest-light: #f2f6f3;
            --text-dark: #1f2f1f;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--forest-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(255,255,255,0) 55%, rgba(238,246,240,0.9) 100%);
            pointer-events: none;
            z-index: 0;
        }

        .bg-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .float-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(79, 111, 82, 0.08);
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .reset-container {
            background: #fcfefc;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(47, 62, 47, 0.06);
            padding: 36px;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(79, 111, 82, 0.12);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-container img {
            max-width: 180px;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
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
            color: var(--forest-green);
            font-size: 18px;
        }

        input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--forest-green);
            box-shadow: 0 0 0 4px rgba(79, 111, 82, 0.12);
        }

        .btn-forest {
            width: 100%;
            padding: 13px;
            background: var(--forest-green);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(79, 111, 82, 0.25);
            margin-top: 10px;
        }

        .btn-forest:hover {
            background: #3f5640;
            transform: translateY(-2px);
        }

        .error-message {
            color: #e53e3e;
            font-size: 13px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="float-shape" style="width:300px; height:300px; top:-100px; left:-100px;"></div>
        <div class="float-shape" style="width:200px; height:200px; bottom:-50px; right:-50px; animation-delay:5s;"></div>
    </div>

    <div class="reset-container">
        <div class="logo-container">
            <img src="{{ asset('images/logo.png') }}" alt="PugArch">
            <h1>Reset Password</h1>
            <p class="subtitle">Set a new password for {{ $phone }}</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="font-size: 13px; border-radius: 8px; padding: 10px;">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('forgotPasswordSave') }}" method="POST">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-wrapper">
                    <i class="input-icon bi bi-lock-fill"></i>
                    <input type="password" id="password" name="password" placeholder="Min 5 characters" required minlength="5">
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-wrapper">
                    <i class="input-icon bi bi-lock-check-fill"></i>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat password" required minlength="5">
                </div>
            </div>

            <button type="submit" class="btn-forest">Update Password</button>
        </form>
    </div>
</body>
</html>
