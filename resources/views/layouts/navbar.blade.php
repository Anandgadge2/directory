@php
    use App\Models\User;
    use Illuminate\Support\Facades\Auth;

    // Use Auth helper if session isn't manually set, otherwise fallback to session
    $user = Auth::user() ?? session('user');
    $company = session('company'); // Ensure this is managed in AuthController or Middleware
    $date = now()->format('Y-m-d');
    $notificationsCount = 0;

    // Define Role Labels
    // Use nullsafe operator for company properties
    $isForest = $company?->is_forest ?? false;
    $roles = [
        1 => $isForest ? 'DFO' : 'Superadmin',
        2 => $isForest ? 'Ranger' : 'Supervisor',
        7 => $isForest ? 'ACF' : 'Admin',
        4 => 'Client'
    ];
@endphp

<nav class="navbar navbar-expand navbar-light bg-white shadow-sm px-3 py-2 mb-2">
    
    <button class="btn border-0 me-2" id="sidebarToggle" type="button" style="z-index: 1100;">
        <i class="bi bi-list fs-4 text-secondary"></i>
    </button>

    <div class="d-flex align-items-center">
        <a class="navbar-brand fw-semibold text-primary d-flex align-items-center me-3" href="{{ url('/home') }}">
            <img src="{{ asset('images/logo1.png') }}" alt="Logo" class="img-fluid" style="height: 32px;">
        </a>
        
        <span class="d-none d-md-block fw-bold text-dark border-start ps-3" style="font-size: 1.1rem;">
            {{ $company?->name ?? 'Patrol Analytics' }}
        </span>
    </div>

    <ul class="navbar-nav ms-auto align-items-center flex-row">

        <li class="nav-item position-relative me-4">
            <a href="#" class="nav-link text-secondary p-0 position-relative">
                <i class="bi bi-bell fs-5"></i>
                @if(isset($notificationsCount) && $notificationsCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.6rem;">
                        {{ $notificationsCount }}
                    </span>
                @endif
            </a>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                
                @if(isset($user->profile_pic) && $user->profile_pic)
                    <img src="{{ $user->profile_pic }}" alt="Profile" class="rounded-circle object-fit-cover" width="36" height="36">
                @else
                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 36px; height: 36px; font-weight: bold;">
                        {{ substr($user?->name ?? 'U', 0, 1) }}
                    </div>
                @endif


                <div class="d-none d-md-block ms-2 lh-sm text-start">
                    <span class="d-block fw-semibold text-dark" style="font-size: 0.9rem;">{{ $user?->name ?? 'Guest' }}</span>
                    <small class="text-muted" style="font-size: 0.75rem;">
                        {{ $roles[$user?->role_id ?? 0] ?? 'User' }}
                    </small>
                </div>
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="profileDropdown" style="min-width: 16rem;">
                
                <li class="px-3 py-2 border-bottom mb-2 bg-light">
                    <div class="fw-bold text-dark">{{ $user?->name ?? 'User' }}</div>
                    <div class="small text-muted">{{ $company?->name ?? '' }}</div>
                </li>

                <li>
                    <a class="dropdown-item py-2" href="{{ route('profile') }}">
                        <i class="bi bi-person me-2 text-primary"></i> My Profile
                    </a>
                </li>
                
                <li><hr class="dropdown-divider"></li>

                <li>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </li>

    </ul>
</nav>