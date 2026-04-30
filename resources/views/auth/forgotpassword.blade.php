<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Guard Analytics</title>
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

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(79, 111, 82, 0.08);
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) { width: 300px; height: 300px; top: -100px; left: -100px; }
        .shape:nth-child(2) { width: 200px; height: 200px; bottom: -50px; right: -50px; animation-delay: 5s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .forgot-container {
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
        }

        .btn-forest:hover {
            background: #3f5640;
            transform: translateY(-2px);
        }

        .btn-forest:disabled {
            background: #a0aec0;
            cursor: not-allowed;
            transform: none;
        }

        .back-to-login {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #718096;
            text-decoration: none;
        }

        .back-to-login:hover {
            color: var(--forest-green);
        }

        .otp-section {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        #loader {
            display: none;
            justify-content: center;
            margin-bottom: 15px;
        }

        .error-alert {
            display: none;
            background: #fff5f5;
            color: #c53030;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 15px;
            border: 1px solid #feb2b2;
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="forgot-container">
        <div class="logo-container">
            <img src="{{ asset('images/logo.png') }}" alt="PugArch">
            <h1>Forgot Password</h1>
            <p class="subtitle">Enter your registered mobile number to receive an OTP</p>
        </div>

        <div id="errorBox" class="error-alert"></div>

        <div id="mobileSection">
            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <div class="input-wrapper">
                    <i class="input-icon bi bi-telephone-fill"></i>
                    <input type="tel" id="mobile" placeholder="Enter 10-digit number" maxlength="10">
                </div>
            </div>
            
            <div id="loader">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <button id="getOtpBtn" class="btn-forest" disabled>Get OTP</button>
        </div>

        <div id="otpSection" class="otp-section">
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <div class="input-wrapper">
                    <i class="input-icon bi bi-shield-lock-fill"></i>
                    <input type="text" id="otp" placeholder="Enter 6-digit OTP" maxlength="6">
                </div>
            </div>
            <button id="verifyOtpBtn" class="btn-forest">Verify OTP</button>
        </div>

        <a href="{{ route('login') }}" class="back-to-login">
            <i class="bi bi-arrow-left"></i> Back to Login
        </a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const mobileInput = $('#mobile');
            const getOtpBtn = $('#getOtpBtn');
            const errorBox = $('#errorBox');
            const loader = $('#loader');

            mobileInput.on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                getOtpBtn.prop('disabled', this.value.length !== 10);
            });

            getOtpBtn.on('click', function() {
                const mobile = mobileSection.querySelector('#mobile').value;
                errorBox.hide();
                loader.css('display', 'flex');
                getOtpBtn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('getOTP') }}",
                    method: 'GET',
                    data: { mobile: mobile },
                    success: function(response) {
                        loader.hide();
                        if (response.status === 'success') {
                            $('#mobileSection').hide();
                            $('#otpSection').fadeIn();
                        } else {
                            errorBox.text(response.message || 'Error sending OTP').show();
                            getOtpBtn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        loader.hide();
                        errorBox.text('Something went wrong. Please try again.').show();
                        getOtpBtn.prop('disabled', false);
                    }
                });
            });

            $('#verifyOtpBtn').on('click', function() {
                const mobile = $('#mobile').val();
                const otp = $('#otp').val();
                errorBox.hide();
                
                if (otp.length !== 6) {
                    errorBox.text('Please enter a valid 6-digit OTP').show();
                    return;
                }

                $(this).prop('disabled', true);

                $.ajax({
                    url: "{{ route('verifyOTP') }}",
                    method: 'GET',
                    data: { mobile: mobile, otp: otp },
                    success: function(response) {
                        if (response.status === 'success') {
                            window.location.href = "{{ url('reset-password') }}/" + mobile;
                        } else {
                            errorBox.text(response.message || 'Invalid OTP').show();
                            $('#verifyOtpBtn').prop('disabled', false);
                        }
                    },
                    error: function() {
                        errorBox.text('Error verifying OTP').show();
                        $('#verifyOtpBtn').prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>
