@extends('layouts.app')

@section('content')
<style>
    :root {
        --forest-green: #4f6f52;
        --forest-accent: #10b981;
        --card-bg: rgba(255, 255, 255, 0.95);
        --glass-border: rgba(79, 111, 82, 0.15);
    }

    .profile-header {
        background: linear-gradient(135deg, var(--forest-green), #3f5640);
        border-radius: 20px;
        color: white;
        padding: 40px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(79, 111, 82, 0.2);
    }

    .profile-header::after {
        content: "";
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        background: white;
        color: var(--forest-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        border: 4px solid rgba(255, 255, 255, 0.3);
        margin-bottom: 15px;
    }

    .profile-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        height: 100%;
        transition: transform 0.3s ease;
    }

    .profile-card:hover {
        transform: translateY(-5px);
    }

    .card-header-forest {
        padding: 20px 25px;
        border-bottom: 1px solid var(--glass-border);
        background: transparent;
    }

    .card-title-forest {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2f1f;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-label {
        font-weight: 600;
        color: #4a5568;
        font-size: 0.9rem;
    }

    .form-control {
        border-radius: 12px;
        padding: 12px 16px;
        border: 2px solid #edf2f7;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--forest-green);
        box-shadow: 0 0 0 4px rgba(79, 111, 82, 0.1);
    }

    .btn-forest {
        background: var(--forest-green);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(79, 111, 82, 0.2);
    }

    .btn-forest:hover {
        background: #3f5640;
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 6px 15px rgba(79, 111, 82, 0.3);
    }

    .alert-forest {
        border-radius: 12px;
        border: none;
        background: rgba(16, 185, 129, 0.1);
        color: #065f46;
        padding: 15px 20px;
    }

    .alert-danger-custom {
        border-radius: 12px;
        border: none;
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
        padding: 15px 20px;
    }
</style>

<div class="row justify-content-center pt-4">
    <div class="col-lg-10">
        
        {{-- Profile Header --}}
        <div class="profile-header d-flex flex-column flex-md-row align-items-center gap-4">
            <div class="profile-avatar text-uppercase">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="text-center text-md-start">
                <h1 class="mb-1 text-white">{{ $user->name }}</h1>
                <p class="mb-0 opacity-75">
                    <i class="bi bi-shield-check me-2"></i> Role: 
                    @php
                        $roles = [1 => 'DFO', 2 => 'Ranger', 7 => 'ACF', 4 => 'Client'];
                    @endphp
                    {{ $roles[$user->role_id] ?? 'User' }}
                </p>
                <p class="mb-0 opacity-75"><i class="bi bi-geo-alt me-2"></i> Forestry Analytics Division</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-forest alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger-custom alert-dismissible fade show mb-4" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            {{-- General Information --}}
            <div class="col-md-7">
                <div class="profile-card">
                    <div class="card-header-forest">
                        <h2 class="card-title-forest">
                            <i class="bi bi-person-lines-fill text-primary"></i> 
                            Account Details
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="contact" class="form-control" value="{{ old('contact', $user->contact) }}" required>
                                <small class="text-muted">Used for login identification.</small>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn-forest">Update Information</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Security --}}
            <div class="col-md-5">
                <div class="profile-card">
                    <div class="card-header-forest">
                        <h2 class="card-title-forest">
                            <i class="bi bi-shield-lock text-danger"></i> 
                            Security Settings
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('profile.change-password') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                            </div>

                            <div class="mb-4 border-top pt-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Min 5 characters" required minlength="5">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password" required minlength="5">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn-forest w-100">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
