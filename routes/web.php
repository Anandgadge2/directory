<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PatrolController;
use App\Http\Controllers\PatrolAnalyticsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\ExecutiveAnalyticsController;
use App\Http\Controllers\GuardDetailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeatMapController;

/* Auth Routes */
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Forgot Password Flow
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgotPassword');
Route::get('/api/get-otp', [AuthController::class, 'getOTP'])->name('getOTP');
Route::get('/api/verify-otp', [AuthController::class, 'verifyOTP'])->name('verifyOTP');
Route::get('/reset-password/{phone}', [AuthController::class, 'forgotPasswordAction'])->name('forgotPasswordAction');
Route::post('/reset-password/save', [AuthController::class, 'forgotPasswordSave'])->name('forgotPasswordSave');

/* Root redirect - redirect to login if not authenticated */
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

/* Protected Routes - Require Authentication */
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index']); // Alias for home

    /* Profile Management */
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    /* API Routes */
    Route::prefix('api')->group(function () {
        Route::get('/guard-details/{guardId}', [GuardDetailController::class, 'getGuardDetails']);
        Route::get('/patrol-session/{sessionId}', [PatrolController::class, 'getSessionDetails']);
    });

    /* Executive Analytics */
    Route::get('/analytics/executive', [ExecutiveAnalyticsController::class, 'executiveDashboard'])->name('analytics.executive');
    Route::get('/analytics/executive/api/kpis', [ExecutiveAnalyticsController::class, 'getKPIsApi'])->name('analytics.executive.api.kpis');
    
    /* Debug Route - Remove in production */
    Route::get('/debug/db-test', function() {
        try {
            $pdo = DB::connection()->getPdo();
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
            
            $usersCount = DB::table('users')->where('company_id', $companyId)->count();
            $sitesCount = DB::table('site_details')->where('company_id', $companyId)->count();
            $patrolsCount = DB::table('patrol_sessions')->where('company_id', $companyId)->count();
            
            return response()->json([
                'status' => 'success',
                'database' => 'connected',
                'company_id' => $companyId,
                'counts' => [
                    'users' => $usersCount,
                    'sites' => $sitesCount,
                    'patrols' => $patrolsCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    /* Attendance */
    Route::prefix('attendance')->group(function () {
        Route::get('/summary', [AttendanceController::class, 'summary']);
        Route::get('/requests', [AttendanceController::class, 'requests'])->name('attendance.requests');
        Route::post('/approve/{id}', [AttendanceController::class, 'approve'])->name('attendance.approve');
    });


    /* Patrol */
    Route::prefix('patrol')->group(function () {
        Route::get('/foot-summary', [PatrolController::class, 'footSummary'])->name('patrol.foot.summary');
        Route::get('/night-summary', [PatrolController::class, 'nightSummary'])->name('patrol.night.summary');
        Route::get('/night-explorer', [PatrolController::class, 'nightExplorer'])->name('patrol.night.explorer');
        Route::get('/analytics', [PatrolAnalyticsController::class, 'patrolAnalytics'])->name('patrol.analytics');
        Route::get('/foot-explorer', [PatrolController::class, 'footExplorer'])->name('patrol.foot.explorer');
        Route::get('/foot/guard-distance', [PatrolController::class, 'footDistanceByGuard'])->name('patrol.foot.guard.distance');
        Route::get('/maps', [PatrolController::class, 'kmlView'])->name('patrol.kml.view');
        Route::get('/guard-details/{id}', [PatrolController::class, 'guardDetailsApi'])->name('patrol.guard.details.api');
        Route::get('/api/filtered-data', [PatrolController::class, 'getFilteredData'])->name('patrol.api.filtered.data');
        Route::get('/type/{type}', [ExecutiveAnalyticsController::class, 'getPatrolsByType'])->name('patrol.by-type');
    });

    /* Analytical Reports Hub */
    Route::prefix('reports')->group(function () {
        Route::get('/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/camera-tracking', [ReportController::class, 'cameraTracking']);
    });



    /* Filter API Routes */
    Route::get('/filters/beats/{rangeId?}', [FilterController::class, 'beats']);
    Route::get('/filters/users', [FilterController::class, 'users']);
    Route::get('/filters/guards/autocomplete', [FilterController::class, 'guardAutocomplete']);
    Route::get('/filters/compartments/{beat}', [FilterController::class, 'compartments']);
    
    /* KPI Modal API Routes */
    Route::get('/api/active-guards', [ExecutiveAnalyticsController::class, 'getActiveGuards']);
    Route::get('/api/beats-details', [ExecutiveAnalyticsController::class, 'getBeatsDetails']);
    Route::get('/api/coverage-analysis', [ExecutiveAnalyticsController::class, 'getCoverageAnalysisApi']);
    Route::get('/api/patrol-analytics', [ExecutiveAnalyticsController::class, 'getPatrolAnalyticsApi']);
    Route::get('/api/patrols-by-type', [ExecutiveAnalyticsController::class, 'getPatrolDetailsByTypeApi']);
    Route::get('/api/incidents-details', [ExecutiveAnalyticsController::class, 'getIncidentsDetailsApi']);
    Route::get('/api/distance-details', [ExecutiveAnalyticsController::class, 'getDistanceDetailsApi']);
    Route::get('/api/attendance-details', [ExecutiveAnalyticsController::class, 'getAttendanceDetailsApi']);


    /* Incidents */
    Route::prefix('incidents')->group(function () {
        Route::get('/summary', [IncidentController::class, 'summary'])->name('incidents.summary');
        // Route::get('/nearby', [IncidentController::class, 'nearby'])->name('incidents.nearby');
        Route::get('/{id}/details', [IncidentController::class, 'getIncidentDetails'])->name('incidents.details');
        Route::get('/type/{type?}', [IncidentController::class, 'getIncidentsByType'])->name('incidents.by-type');
    });


    /* Guard Details (Web View) */
    Route::get('/guard-details/{id}', [PatrolController::class, 'guardDetails'])->name('guard.details');
    Route::get('/filters/beats/{rangeId}', [PatrolController::class, 'getBeats']);
    Route::get('/filters/compartments/{beatId}', [PatrolController::class, 'getCompartments']);

    /* Know Your Area */
    Route::get('/know-your-area', [BeatMapController::class, 'forestIndex'])->name('know-your-area');
    Route::get('/api/know-your-area/data', [BeatMapController::class, 'getMapData'])->name('know-your-area.data');
});
