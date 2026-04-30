<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Traits\FilterDataTrait;
use App\Services\RoleBasedFilterService; // ✅ Add role-based filtering

class PatrolController extends Controller
{
    use FilterDataTrait;

    public function footSummary(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            // ✅ Get accessible users based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

            $base = DB::table('patrol_sessions')
                ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
                ->leftJoin('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds) // ✅ Role-based filter
                ->whereIn('patrol_sessions.session', ['Foot', 'Vehicle']);

            $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

            /* ================= KPIs ================= */
            $totalSessions = (clone $base)->count();
            
            $completed = (clone $base)
                ->whereNotNull('patrol_sessions.ended_at')
                ->count();
            
            $ongoing = (clone $base)
                ->whereNull('patrol_sessions.ended_at')
                ->count();

            $totalDistance = round(
                (clone $base)
                    ->whereNotNull('patrol_sessions.ended_at')
                    ->sum(DB::raw('COALESCE(patrol_sessions.distance,0)')) / 1000, 
                2
            );

            /* ================= TABLE DATA ================= */
            $patrols = (clone $base)
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'site_details.client_name as range',
                    'site_details.name as beat',
                    'patrol_sessions.started_at',
                    'patrol_sessions.ended_at',
                    DB::raw('ROUND(COALESCE(patrol_sessions.distance,0)/1000,2) as distance'),
                    DB::raw('ROUND(
                        (COALESCE(patrol_sessions.distance,0)/1000) / 
                        NULLIF(TIMESTAMPDIFF(MINUTE, patrol_sessions.started_at, patrol_sessions.ended_at)/60,0), 
                    2) as speed')
                )
                ->orderByDesc('patrol_sessions.started_at')
                ->paginate(25)
                ->withQueryString();

            /* ================= GUARD STATS ================= */
            $guardStats = (clone $base)
                ->groupBy('users.id', 'users.name')
                ->selectRaw('
                    users.id as user_id,
                    users.name as guard,
                    COUNT(*) as total_sessions,
                    SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN patrol_sessions.ended_at IS NULL THEN 1 ELSE 0 END) as ongoing,
                    ROUND(SUM(COALESCE(patrol_sessions.distance,0))/1000, 2) as total_distance
                ')
                ->orderByDesc('total_distance')
                ->get();

            /* ================= RANGE STATS ================= */
            // Use filtered base query to ensure range stats respect all filters
            $rangeStats = (clone $base)
                ->whereNotNull('site_details.client_name')
                ->whereNotNull('patrol_sessions.ended_at')
                ->groupBy('site_details.client_name')
                ->selectRaw('
                    site_details.client_name as range_name,
                    ROUND(SUM(COALESCE(patrol_sessions.distance,0))/1000, 2) as distance
                ')
                ->having('distance', '>', 0)
                ->orderByDesc('distance')
                ->get();

            /* ================= DAILY TREND ================= */
            // Apply all filters to daily trend for consistency
            $trendBase = DB::table('patrol_sessions')
                ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
                ->leftJoin('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.session', ['Foot', 'Vehicle'])
                ->where('users.isActive', 1);

            // Apply all canonical filters (date, site, user, guard_search)
            $this->applyCanonicalFilters(
                $trendBase,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id'
            );

            // ✅ Apply role-based filter to trend
            $trendBase->whereIn('patrol_sessions.user_id', $accessibleUserIds);

            // Default to last 30 days if no date filter is set
            if (!$request->filled('start_date') && !$request->filled('end_date')) {
                $trendBase->where('patrol_sessions.started_at', '>=', now()->subDays(30)->startOfDay());
            }

            $dailyTrend = $trendBase
                ->whereNotNull('patrol_sessions.ended_at')
                ->selectRaw('
                    DATE(patrol_sessions.started_at) as day,
                    ROUND(SUM(COALESCE(patrol_sessions.distance,0))/1000, 2) as distance
                ')
                ->groupBy(DB::raw('DATE(patrol_sessions.started_at)'))
                ->orderBy('day')
                ->get();

            Log::info('Daily Trend Data:', ['data' => $dailyTrend]);

            $filterData = $this->filterData();

        } catch (\Exception $e) {
            Log::error('Foot Summary Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            $totalSessions = $completed = $ongoing = $totalDistance = 0;
            $patrols = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
            $guardStats = $rangeStats = $dailyTrend = collect([]);
            $filterData = ['ranges' => collect(), 'beats' => collect(), 'users' => collect()];
            
            // Return view with empty data instead of redirecting to prevent loops
            if ($request->ajax()) {
                return view('patrol.partials.foot-table', compact('patrols', 'guardStats'))->render();
            }
            
            return view('patrol.foot-summary', array_merge(
                $filterData,
                compact('totalSessions', 'completed', 'ongoing', 'totalDistance', 'patrols', 'guardStats', 'rangeStats', 'dailyTrend')
            ))->with('error', 'Failed to load patrol data. Please try again or contact support if the issue persists.');
        }

        if ($request->ajax()) {
            return view('patrol.partials.foot-table', compact('patrols', 'guardStats'))->render();
        }

        return view('patrol.foot-summary', array_merge(
            $filterData,
            compact(
                'totalSessions',
                'completed',
                'ongoing',
                'totalDistance',
                'patrols',
                'guardStats',
                'rangeStats',
                'dailyTrend'
            )
        ));
    }

    public function footExplorer(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            // ✅ Get accessible users based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

            $base = DB::table('patrol_sessions')
                ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                ->whereIn('patrol_sessions.session', ['Foot', 'Vehicle'])
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds); // ✅ Role-based filter

            $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

            $patrols = $base->select(
                'users.id as user_id',
                'users.name as user_name',
                'site_details.client_name as range',
                'site_details.name as beat',
                'patrol_sessions.started_at',
                'patrol_sessions.ended_at',
                DB::raw('ROUND(COALESCE(patrol_sessions.distance,0) / 1000, 2) as distance'),
                DB::raw('ROUND(
                        (COALESCE(patrol_sessions.distance,0)/1000) / 
                        NULLIF(TIMESTAMPDIFF(MINUTE, patrol_sessions.started_at, patrol_sessions.ended_at)/60,0), 
                    2) as speed')
            )
                ->orderByDesc('patrol_sessions.started_at')
                ->paginate(25)
                ->withQueryString();

            $filterData = $this->filterData();
        } catch (\Exception $e) {
            $patrols = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
            $filterData = ['ranges' => [], 'beats' => [], 'users' => []];
        }

        return view('patrol.foot-explorer', array_merge(
            $filterData,
            compact('patrols')
        ));
    }

    public function footDistanceByGuard(Request $request)
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        // ✅ Get accessible users based on role
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        $base = DB::table('patrol_sessions')
            ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
            ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
            ->whereIn('patrol_sessions.session', ['Foot', 'Vehicle'])
            ->whereNotNull('patrol_sessions.ended_at')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereIn('patrol_sessions.user_id', $accessibleUserIds); // ✅ Role-based filter

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $base->whereBetween('patrol_sessions.started_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

        return $base->groupBy('users.id', 'users.name')
            ->selectRaw('
            users.name as guard,
            ROUND(SUM(COALESCE(patrol_sessions.distance,0))/ 1000,2) as total_distance
        ')
            ->orderByDesc('total_distance')
            ->get();
    }

    public function nightSummary(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            // ✅ Get accessible users based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

            $base = DB::table('patrol_sessions')
                ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                ->whereIn('patrol_sessions.session', ['Foot', 'Vehicle'])
                ->where(function ($base) {
                    $base->whereTime('patrol_sessions.started_at', '>=', '18:00:00')
                        ->orWhereTime('patrol_sessions.started_at', '<', '06:00:00');
                })
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds); // ✅ Role-based filter

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $base->whereBetween('patrol_sessions.started_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }
            $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

            /* ================= KPIs ================= */
            $totalSessions = (clone $base)->count();
            $completed = (clone $base)->whereNotNull('patrol_sessions.ended_at')->count();
            $ongoing = (clone $base)->whereNull('patrol_sessions.ended_at')->count();

            $totalDistance = round(
                (clone $base)
                    ->whereNotNull('patrol_sessions.ended_at')
                    ->sum(DB::raw('COALESCE(patrol_sessions.distance,0)')) / 1000,
                2
            );

            /* ================= TABLE ================= */
            $patrolsQuery = (clone $base)
                ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                ->select(
                    'patrol_sessions.*',
                    'users.id as user_id',
                    'users.name as user_name',
                    'site_details.client_name as range',
                    'site_details.name as beat',
                    DB::raw('ROUND(COALESCE(patrol_sessions.distance,0) / 1000, 2) as distance'),
                    DB::raw('ROUND(
                        (COALESCE(patrol_sessions.distance,0)/1000) / 
                        NULLIF(TIMESTAMPDIFF(MINUTE, patrol_sessions.started_at, patrol_sessions.ended_at)/60,0),
                    2) as speed')
                )
                ->orderByDesc('patrol_sessions.started_at');

            $patrols = $patrolsQuery->paginate(15)->withQueryString();
            
            /* ================= GUARD STATS ================= */
            $guardStats = (clone $base)
                 ->groupBy('users.id', 'users.name')
                 ->selectRaw('
                    users.id as user_id,
                    users.name as guard,
                    COUNT(*) as total_sessions,
                    SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN patrol_sessions.ended_at IS NULL THEN 1 ELSE 0 END) as ongoing,
                    ROUND(SUM(COALESCE(patrol_sessions.distance,0))/1000,2) as total_distance
                 ')
                 ->orderByDesc('total_distance')
                 ->get();


            /* ================= SPEED ================= */
            $speedStats = (clone $base)
                ->whereNotNull('patrol_sessions.ended_at')
                ->groupBy('users.id', 'users.name')
                ->selectRaw('
                    users.name as guard,
                    ROUND(
                        (SUM(COALESCE(patrol_sessions.distance,0))/1000) /
                        NULLIF(SUM(TIMESTAMPDIFF(MINUTE, patrol_sessions.started_at, patrol_sessions.ended_at))/60,0),
                    2) as speed
                ')
                ->orderByDesc('speed')
                ->get();

            $filterData = $this->filterData();
            
            if ($request->ajax()) {
                return view('patrol.partials.night-table', compact('patrols', 'guardStats'))->render();
            }

        } catch (\Exception $e) {
            $totalSessions = 0; $completed = 0; $ongoing = 0; $totalDistance = 0;
            $patrols = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
            $guardStats = collect([]);
            $speedStats = collect([]);
            $filterData = ['ranges' => [], 'beats' => [], 'users' => []];
        }

        return view('patrol.night-summary', array_merge(
            $filterData,
            compact(
                'totalSessions',
                'completed',
                'ongoing',
                'totalDistance',
                'patrols',
                'guardStats',
                'speedStats'
            )
        ));
    }

    public function nightExplorer(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            // ✅ Get accessible users based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

            $base = DB::table('patrol_sessions')
                ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                ->whereIn('patrol_sessions.session', ['Foot', 'Vehicle'])
                ->where(function ($q) {
                    $q->whereTime('patrol_sessions.started_at', '>=', '18:00:00')
                        ->orWhereTime('patrol_sessions.started_at', '<', '06:00:00');
                })
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds); // ✅ Role-based filter

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $base->whereBetween('patrol_sessions.started_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }
            $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

            $nightHeatmap = (clone $base)
                ->whereNotNull('patrol_sessions.path_geojson')
                ->select('patrol_sessions.path_geojson', 'patrol_sessions.started_at')
                ->get();

            $totalBeats = DB::table('site_details')
                ->where('company_id', $companyId)
                ->whereNotNull('name')
                ->distinct('name')
                ->count('name');

            $patrolledBeats = (clone $base)
                ->whereNotNull('patrol_sessions.ended_at')
                ->distinct('site_details.name')
                ->count('site_details.name');

            $kpis = [
                'total_sessions' => (clone $base)->count(),
                'completed' => (clone $base)->whereNotNull('ended_at')->count(),
                'ongoing' => (clone $base)->whereNull('ended_at')->count(),
                'active_guards' => (clone $base)->distinct('user_id')->count('user_id'),
                'total_distance' => round(
                    (clone $base)->whereNotNull('ended_at')->sum(DB::raw('COALESCE(distance,0)')) / 1000,
                    2
                ),
                'beats_covered_pct' => $totalBeats > 0
                    ? round(($patrolledBeats / $totalBeats) * 100, 1)
                    : 0
            ];

            $patrols = (clone $base)
                ->select(
                    'users.id as user_id',
                    'users.name as guard',
                    'patrol_sessions.session as type',
                    'patrol_sessions.started_at',
                    'patrol_sessions.ended_at',
                    DB::raw('ROUND(COALESCE(patrol_sessions.distance,0)/1000,2) as distance'),
                    'patrol_sessions.id as session_id',
                    'patrol_sessions.path_geojson'
                )
                ->orderByDesc('patrol_sessions.started_at')
                ->paginate(25)
                ->withQueryString();

            $speedStats = (clone $base)
                ->whereNotNull('ended_at')
                ->groupBy('users.id', 'users.name')
                ->selectRaw('
                    users.name as guard,
                    ROUND(
                        (SUM(COALESCE(distance,0))/1000) /
                        NULLIF(SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at))/60,0),
                    2) as speed
                ')
                ->orderByDesc('speed')
                ->get();

            $nightDistanceByGuard = (clone $base)
                ->whereNotNull('ended_at')
                ->groupBy('users.id', 'users.name')
                ->selectRaw('
                    users.name as guard,
                    ROUND(SUM(COALESCE(distance,0))/1000,2) as total_distance
                ')
                ->orderByDesc('total_distance')
                ->get();

            $filterData = $this->filterData();
        } catch (\Exception $e) {
            $patrols = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
            $kpis = ['total_sessions' => 0, 'completed' => 0, 'ongoing' => 0, 'active_guards' => 0, 'total_distance' => 0, 'beats_covered_pct' => 0];
            $speedStats = collect([]);
            $nightHeatmap = collect([]);
            $nightDistanceByGuard = collect([]);
            $filterData = ['ranges' => [], 'beats' => [], 'users' => []];
        }

        return view('patrol.night-explorer', array_merge(
            $filterData,
            compact(
                'patrols',
                'kpis',
                'speedStats',
                'nightHeatmap',
                'nightDistanceByGuard'
            )
        ));
    }

    public function getSessionDetails($sessionId)
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        $session = DB::table('patrol_sessions')
            ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
            ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
            ->where('patrol_sessions.id', $sessionId)
            ->where('patrol_sessions.company_id', $companyId)
            ->select(
                'patrol_sessions.id as session_id',
                'patrol_sessions.user_id',
                'patrol_sessions.site_id',
                'users.name as user_name',
                'users.profile_pic as user_profile',
                'users.contact as user_contact',
                'site_details.name as site_name',
                'site_details.client_name as range_name',
                'site_details.address as site_address',
                'patrol_sessions.type',
                'patrol_sessions.session',
                'patrol_sessions.method',
                'patrol_sessions.started_at',
                'patrol_sessions.ended_at',
                'patrol_sessions.start_lat',
                'patrol_sessions.start_lng',
                'patrol_sessions.end_lat',
                'patrol_sessions.end_lng',
                'patrol_sessions.path_geojson',
                'patrol_sessions.distance',
                DB::raw("CASE 
                    WHEN patrol_sessions.ended_at IS NULL THEN 'In Progress'
                    WHEN patrol_sessions.ended_at IS NOT NULL THEN 'Completed'
                    ELSE 'Unknown'
                END as status"),
                DB::raw("ROUND(COALESCE(patrol_sessions.distance, 0) / 1000, 2) as distance_km"),
                DB::raw("TIMESTAMPDIFF(MINUTE, patrol_sessions.started_at, COALESCE(patrol_sessions.ended_at, NOW())) as duration_minutes")
            )
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $logs = DB::table('patrol_logs')
            ->where('patrol_session_id', $sessionId)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'session' => $session,
            'logs' => $logs
        ]);
    }

    public function guardDetails($userId, Request $request)
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        $guard = DB::table('users')
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->first();

        if (!$guard) {
            abort(404, 'Guard not found');
        }

        $base = DB::table('patrol_sessions')
            ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
            ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
            ->where('patrol_sessions.user_id', $userId)
            ->whereNotNull('patrol_sessions.started_at')
            ->where('patrol_sessions.company_id', $companyId);

        $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

        $sessions = $base->select(
            'patrol_sessions.id as session_id',
            'patrol_sessions.user_id',
            'patrol_sessions.site_id',
            'users.name as user_name',
            'users.profile_pic as user_profile',
            'users.contact as user_contact',
            'users.designation as user_designation',
            'site_details.name as site_name',
            'site_details.client_name as range_name',
            'site_details.address as site_address',
            'patrol_sessions.type',
            'patrol_sessions.session',
            'patrol_sessions.method',
            'patrol_sessions.started_at',
            'patrol_sessions.ended_at',
            'patrol_sessions.start_lat',
            'patrol_sessions.start_lng',
            'patrol_sessions.end_lat',
            'patrol_sessions.end_lng',
            'patrol_sessions.path_geojson',
            'patrol_sessions.distance',
            DB::raw("CASE 
                    WHEN patrol_sessions.ended_at IS NULL THEN 'In Progress'
                    WHEN patrol_sessions.ended_at IS NOT NULL THEN 'Completed'
                    ELSE 'Unknown'
                END as status"),
            DB::raw("ROUND(COALESCE(patrol_sessions.distance, 0) / 1000, 2) as distance_km"),
            DB::raw("TIMESTAMPDIFF(MINUTE, patrol_sessions.started_at, COALESCE(patrol_sessions.ended_at, NOW())) as duration_minutes")
        )
            ->orderByDesc('patrol_sessions.started_at')
            ->paginate(25)
            ->withQueryString();

        $assignedSites = DB::table('site_assign')
            ->leftJoin('site_details', 'site_details.id', '=', 'site_assign.site_id')
            ->leftJoin('client_details', 'client_details.id', '=', 'site_assign.client_id')
            ->where('site_assign.user_id', $userId)
            ->where('site_assign.endDate', '>=', date('Y-m-d'))
            ->where('site_assign.company_id', $companyId)
            ->select(
                'site_details.name as site_name',
                'site_details.address as site_address',
                'client_details.name as client_name',
                'site_assign.shift_name',
                'site_assign.startDate as start_date',
                'site_assign.endDate as end_date'
            )
            ->get();

        $guardRegions = DB::table('site_geofences')
            ->leftJoin('site_details', 'site_details.id', '=', 'site_geofences.site_id')
            ->whereIn('site_geofences.site_id', function ($query) use ($userId) {
                $query->select('site_id')
                    ->from('site_assign')
                    ->where('user_id', $userId)
                    ->where('endDate', '>=', date('Y-m-d'));
            })
            ->where('site_geofences.company_id', $companyId)
            ->select(
                'site_geofences.*',
                'site_details.name as site_name'
            )
            ->get();

        $patrolLogs = DB::table('patrol_logs')
            ->join('patrol_sessions', 'patrol_sessions.id', '=', 'patrol_logs.patrol_session_id')
            ->where('patrol_sessions.user_id', $userId)
            ->where('patrol_sessions.company_id', $companyId)
            ->orderBy('patrol_logs.created_at', 'desc')
            ->limit(50)
            ->get();

        $stats = [
            'total_sessions' => (clone $base)->count(),
            'completed_sessions' => (clone $base)->whereNotNull('patrol_sessions.ended_at')->count(),
            'active_sessions' => (clone $base)->whereNull('patrol_sessions.ended_at')->count(),
            'total_distance_km' => round(
                (clone $base)->whereNotNull('patrol_sessions.ended_at')->sum(DB::raw('COALESCE(patrol_sessions.distance,0)')) / 1000,
                2
            ),
            'total_patrol_hours' => round(
                (clone $base)->whereNotNull('patrol_sessions.ended_at')
                    ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) / 60 as total_hours')
                    ->value('total_hours') ?: 0,
                2
            ),
            'avg_session_duration' => round(
                (clone $base)->whereNotNull('patrol_sessions.ended_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) / 60 as avg_duration')
                    ->value('avg_duration') ?: 0,
                2
            ),
            'sites_covered' => (clone $base)->distinct('patrol_sessions.site_id')->count('patrol_sessions.site_id')
        ];

        return view('patrol.guard-details', array_merge(
            $this->filterData(),
            compact(
                'guard',
                'sessions',
                'assignedSites',
                'guardRegions',
                'patrolLogs',
                'stats'
            )
        ));
    }

    public function kmlView(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            $base = DB::table('patrol_sessions')
                ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                ->whereNotNull('patrol_sessions.path_geojson')
                ->whereNotNull('patrol_sessions.started_at')
                ->where('patrol_sessions.company_id', $companyId);

            $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

            $sessions = $base->select(
                'patrol_sessions.id as session_id',
                'patrol_sessions.user_id',
                'patrol_sessions.site_id',
                'users.name as user_name',
                'users.profile_pic as user_profile',
                'site_details.name as site_name',
                'site_details.client_name as range_name',
                'patrol_sessions.type',
                'patrol_sessions.session',
                'patrol_sessions.started_at',
                'patrol_sessions.ended_at',
                'patrol_sessions.start_lat',
                'patrol_sessions.start_lng',
                'patrol_sessions.end_lat',
                'patrol_sessions.end_lng',
                'patrol_sessions.path_geojson',
                'patrol_sessions.distance',
                DB::raw("CASE 
                    WHEN patrol_sessions.ended_at IS NULL THEN 'In Progress'
                    WHEN patrol_sessions.ended_at IS NOT NULL THEN 'Completed'
                    ELSE 'Unknown'
                END as status"),
                DB::raw("ROUND(COALESCE(patrol_sessions.distance, 0) / 1000, 2) as distance_km")
            )
                ->orderByDesc('patrol_sessions.started_at')
                ->paginate(50)
                ->withQueryString();

            $mapUsers = (clone $base)
                ->reorder()
                ->select('users.id', 'users.name')
                ->distinct()
                ->orderBy('users.name')
                ->get();

            $geofences = DB::table('site_geofences')
                ->where('site_geofences.company_id', $companyId)
                ->leftJoin('site_details', 'site_details.id', '=', 'site_geofences.site_id')
                ->whereNull('site_geofences.deleted_at')
                ->select(
                    'site_geofences.*',
                    'site_details.name as site_name',
                    'site_details.client_name as range_name'
                )
                ->get();

            $stats = [
                'total_sessions' => (clone $base)->count(),
                'completed_sessions' => (clone $base)->whereNotNull('patrol_sessions.ended_at')->count(),
                'active_sessions' => (clone $base)->whereNull('patrol_sessions.ended_at')->count(),
                'total_distance_km' => round(
                    (clone $base)->whereNotNull('patrol_sessions.ended_at')->sum(DB::raw('COALESCE(patrol_sessions.distance,0)')) / 1000,
                    2
                ),
                'unique_guards' => (clone $base)->distinct('patrol_sessions.user_id')->count('patrol_sessions.user_id'),
                'total_regions' => $geofences->count(),
                'active_regions' => $geofences->where('site_name', '!=', null)->count()
            ];
            
            $filterData = $this->filterData();

        } catch (\Exception $e) {
            $sessions = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
            $mapUsers = collect([]);
            $geofences = collect([]);
            $stats = ['total_sessions' => 0, 'completed_sessions' => 0, 'active_sessions' => 0, 'total_distance_km' => 0, 'unique_guards' => 0, 'total_regions' => 0, 'active_regions' => 0];
            $filterData = ['ranges' => [], 'beats' => [], 'users' => []];
        }

        return view('patrol.kml-view', array_merge(
            $filterData,
            compact('sessions', 'stats', 'geofences'),
            ['guardList' => $mapUsers]
        ));
    }

    public function maps(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            $base = DB::table('patrol_sessions')
                ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                ->whereNotNull('patrol_sessions.path_geojson')
                ->where('patrol_sessions.company_id', $companyId);

            if ($request->filled('user_id')) {
                $base->where('patrol_sessions.user_id', $request->user_id);
            }

            if ($request->filled('sort') && $request->sort === 'distance_desc') {
                $base->orderByDesc('patrol_sessions.distance');
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $base->whereBetween('patrol_sessions.started_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }

            $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

            $paths = (clone $base)
                ->select(
                    'patrol_sessions.user_id',
                    'patrol_sessions.session',
                    'patrol_sessions.path_geojson'
                )
                ->get()
                ->groupBy('user_id');

            $guards = (clone $base)
                ->groupBy('users.id', 'users.name', 'users.role_id')
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw("CASE WHEN users.role_id = 2 THEN 'Circle Incharge' ELSE 'Forest Guard' END AS designation")
                )
                ->orderBy('users.name')
                ->paginate(20)
                ->withQueryString();

            $stats = [
                'total_guards' => $guards->total(),
                'active_patrols' => (clone $base)->whereNull('patrol_sessions.ended_at')->count(),
                'completed_patrols' => (clone $base)->whereNotNull('patrol_sessions.ended_at')->count(),
                'total_distance_km' => round(
                    (clone $base)->whereNotNull('patrol_sessions.ended_at')->sum(DB::raw('COALESCE(patrol_sessions.distance,0)')) / 1000,
                    2
                )
            ];

            $geofences = DB::table('site_geofences')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->select('site_name', 'type', 'lat', 'lng', 'radius', 'poly_lat_lng')
                ->get();

            $users = (clone $base)
                ->reorder()
                ->select('users.id', 'users.name')
                ->distinct()
                ->orderBy('users.name')
                ->get();

        } catch (\Exception $e) {
             $paths = collect([]);
             $guards = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
             $stats = ['total_guards' => 0, 'active_patrols' => 0, 'completed_patrols' => 0, 'total_distance_km' => 0];
             $geofences = collect([]);
             $users = collect([]);
        }

        return view('patrol.maps', compact(
            'paths',
            'guards',
            'stats',
            'geofences',
            'users'
        ));
    }

    /**
     * API endpoint to get filtered stats and sessions for AJAX updates
     */
    public function getFilteredData(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            $base = DB::table('patrol_sessions')
                ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                ->whereNotNull('patrol_sessions.path_geojson')
                ->whereNotNull('patrol_sessions.started_at')
                ->where('patrol_sessions.company_id', $companyId);

            // Apply all canonical filters
            $this->applyCanonicalFilters($base, 'patrol_sessions.started_at');

            // Calculate stats
            $stats = [
                'total_sessions' => (clone $base)->count(),
                'completed_sessions' => (clone $base)->whereNotNull('patrol_sessions.ended_at')->count(),
                'active_sessions' => (clone $base)->whereNull('patrol_sessions.ended_at')->count(),
                'total_distance_km' => round(
                    (clone $base)->whereNotNull('patrol_sessions.ended_at')->sum(DB::raw('COALESCE(patrol_sessions.distance,0)')) / 1000,
                    2
                ),
            ];

            // Get sessions for the sidebar
            $sessions = $base->select(
                'patrol_sessions.id as session_id',
                'patrol_sessions.user_id',
                'patrol_sessions.site_id',
                'users.name as user_name',
                'users.profile_pic as user_profile',
                'site_details.name as site_name',
                'site_details.client_name as range_name',
                'patrol_sessions.type',
                'patrol_sessions.session',
                'patrol_sessions.started_at',
                'patrol_sessions.ended_at',
                'patrol_sessions.start_lat',
                'patrol_sessions.start_lng',
                'patrol_sessions.end_lat',
                'patrol_sessions.end_lng',
                'patrol_sessions.path_geojson',
                'patrol_sessions.distance',
                DB::raw("CASE 
                    WHEN patrol_sessions.ended_at IS NULL THEN 'In Progress'
                    WHEN patrol_sessions.ended_at IS NOT NULL THEN 'Completed'
                    ELSE 'Unknown'
                END as status"),
                DB::raw("ROUND(COALESCE(patrol_sessions.distance, 0) / 1000, 2) as distance_km")
            )
                ->orderByDesc('patrol_sessions.started_at')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'sessions' => $sessions
            ]);

        } catch (\Exception $e) {
            \Log::error('Get Filtered Data Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => [
                    'total_sessions' => 0,
                    'completed_sessions' => 0,
                    'active_sessions' => 0,
                    'total_distance_km' => 0
                ],
                'sessions' => []
            ], 500);
        }
    }

    public function getBeats($rangeId)
    {
        $beats = $this->getBeatsForRange((int)$rangeId);
        $formatted = [];
        foreach ($beats as $id => $name) {
            $formatted[] = ['id' => (int)$id, 'name' => (string)$name];
        }
        return response()->json($formatted);
    }

    public function getCompartments($beatId)
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        $compartments = DB::table('site_geofences')
            ->where('site_id', $beatId)
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($compartments);
    }
}
