<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Traits\FilterDataTrait;
use App\Helpers\FormatHelper;
use App\Services\AnalyticsDataService;
use App\Services\RoleBasedFilterService;

class ExecutiveAnalyticsController extends Controller
{
    use FilterDataTrait;

    protected $analyticsService;

    public function __construct(AnalyticsDataService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function executiveDashboard(Request $request)
    {
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)
                : Carbon::now()->subDays(30);

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)
                : Carbon::now();

            // Validate dates
            if ($startDate->isFuture()) {
                $startDate = Carbon::now()->subDays(30);
            }

            if ($endDate->lt($startDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            // Get filter data first (with error handling)
            try {
                $filterData = $this->filterData();
            } catch (\Exception $e) {
                Log::error('FilterData Error: ' . $e->getMessage());
                $filterData = ['ranges' => collect(), 'beats' => collect(), 'users' => collect()];
            }

            // Get KPIs with error handling
            try {
                $kpis = $this->getKPIs($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getKPIs Error: ' . $e->getMessage());
                $kpis = [
                    'activeGuards' => 0,
                    'totalPatrols' => 0,
                    'completedPatrols' => 0,
                    'ongoingPatrols' => 0,
                    'totalDistance' => 0,
                    'avgDistancePerGuard' => 0,
                    'attendanceRate' => 0,
                    'presentCount' => 0,
                    'totalIncidents' => 0,
                    'pendingIncidents' => 0,
                    'resolvedIncidents' => 0,
                    'resolutionRate' => 0,
                    'totalSites' => 0,
                    'siteCoverage' => 0,
                ];
            }

            // Get other data with individual error handling to prevent one failure from breaking everything
            try {
                $guardPerformance = $this->getGuardPerformanceRankings($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getGuardPerformanceRankings Error: ' . $e->getMessage());
                $guardPerformance = ['topPerformers' => collect(), 'fullPerformance' => collect()];
            }

            try {
                $incidentTracking = $this->getIncidentStatusTracking($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getIncidentStatusTracking Error: ' . $e->getMessage());
                $incidentTracking = [
                    'statusDistribution' => collect(),
                    'incidentTypes' => collect(),
                    'resolutionTime' => collect(),
                    'criticalIncidents' => collect(),
                    'incidentsBySite' => collect(),
                    'priorityDistribution' => collect(),
                ];
            }

            try {
                $patrolAnalytics = $this->getPatrolAnalytics($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getPatrolAnalytics Error: ' . $e->getMessage());
                $patrolAnalytics = [];
            }

            try {
                $attendanceAnalytics = $this->getAttendanceAnalytics($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getAttendanceAnalytics Error: ' . $e->getMessage());
                $attendanceAnalytics = [];
            }

            try {
                $timePatterns = $this->getTimeBasedPatterns($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getTimeBasedPatterns Error: ' . $e->getMessage());
                $timePatterns = [];
            }

            try {
                $riskZones = $this->getRiskZoneAnalysis($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getRiskZoneAnalysis Error: ' . $e->getMessage());
                $riskZones = [];
            }

            try {
                $coverageAnalysis = $this->getCoverageAnalysis($startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('getCoverageAnalysis Error: ' . $e->getMessage());
                $coverageAnalysis = [
                    'coveragePercentage' => 0,
                    'sitesWithPatrols' => 0,
                    'totalSites' => $kpis['totalSites'] ?? 0,
                    'coverageGaps' => collect()
                ];
            }

            $data = array_merge(
                $filterData,
                [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'kpis' => $kpis,
                    'guardPerformance' => $guardPerformance,
                    'incidentTracking' => $incidentTracking,
                    'patrolAnalytics' => $patrolAnalytics,
                    'attendanceAnalytics' => $attendanceAnalytics,
                    'timePatterns' => $timePatterns,
                    'riskZones' => $riskZones,
                    'coverageAnalysis' => $coverageAnalysis,
                ]
            );

            if ($request->ajax()) {
                $content = view('analytics.partials.executive-dashboard-content', $data)->render();
                // Wrap in #dashboardContent so the AJAX JS handler can find and update it properly
                return response('<div class="container-fluid" id="dashboardContent">' . $content . '</div>');
            }


            return view('analytics.executive-dashboard', $data);
        } catch (\Exception $e) {
            Log::error('Executive Dashboard Fatal Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Return view with empty data - don't call filterData() to avoid potential loops
            $emptyFilterData = [
                'ranges' => collect(),
                'beats' => collect(),
                'users' => collect()
            ];

            return view('analytics.executive-dashboard', array_merge(
                $emptyFilterData,
                [
                    'startDate' => Carbon::now()->subDays(30),
                    'endDate' => Carbon::now(),
                    'kpis' => [
                        'activeGuards' => 0,
                        'totalPatrols' => 0,
                        'completedPatrols' => 0,
                        'ongoingPatrols' => 0,
                        'totalDistance' => 0,
                        'avgDistancePerGuard' => 0,
                        'attendanceRate' => 0,
                        'presentCount' => 0,
                        'totalIncidents' => 0,
                        'pendingIncidents' => 0,
                        'resolvedIncidents' => 0,
                        'resolutionRate' => 0,
                        'totalSites' => 0,
                        'siteCoverage' => 0,
                    ],
                    'guardPerformance' => ['topPerformers' => collect(), 'fullPerformance' => collect()],
                    'incidentTracking' => [
                        'statusDistribution' => collect(),
                        'incidentTypes' => collect(),
                        'resolutionTime' => collect(),
                        'criticalIncidents' => collect(),
                        'incidentsBySite' => collect(),
                        'priorityDistribution' => collect(),
                    ],
                    'patrolAnalytics' => [],
                    'attendanceAnalytics' => [],
                    'timePatterns' => [],
                    'riskZones' => [],
                    'coverageAnalysis' => [
                        'coveragePercentage' => 0,
                        'sitesWithPatrols' => 0,
                        'totalSites' => 0,
                        'coverageGaps' => collect(),
                        'highIncidentZones' => collect(),
                        'mostPatrolled' => collect()
                    ],
                ]
            ))->with('error', 'Failed to load some data. Please check the logs for details.');
        }
    }

    /**
     * API endpoint to get filtered KPIs for AJAX updates
     */
    public function getKPIsApi(Request $request)
    {
        try {
            // Use same date validation logic as executiveDashboard
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)
                : Carbon::now()->subDays(30);

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)
                : Carbon::now();

            if ($startDate->isFuture()) {
                $startDate = Carbon::now()->subDays(30);
            }

            if ($endDate->lt($startDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            $kpis = $this->getKPIs($startDate, $endDate);

            return response()->json([
                'success' => true,
                'kpis' => $kpis
            ]);
        } catch (\Exception $e) {
            Log::error('Get KPIs API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'kpis' => [
                    'activeGuards' => 0,
                    'totalPatrols' => 0,
                    'totalDistance' => 0,
                    'attendanceRate' => 0,
                    'totalIncidents' => 0,
                    'resolutionRate' => 0,
                    'siteCoverage' => 0,
                    'totalSites' => 0,
                ]
            ], 500);
        }
    }

    private function getKPIs(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $user = session('user');

            // Fallback to company_id 56 for testing if session user not available
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            // Return empty data if no company ID available
            if (!$companyId) {
                Log::warning('No company ID available for KPIs');
                return [
                    'activeGuards' => 0,
                    'totalPatrols' => 0,
                    'completedPatrols' => 0,
                    'ongoingPatrols' => 0,
                    'totalDistance' => 0,
                    'avgDistancePerGuard' => 0,
                    'attendanceRate' => 0,
                    'presentCount' => 0,
                    'totalIncidents' => 0,
                    'pendingIncidents' => 0,
                    'resolvedIncidents' => 0,
                    'resolutionRate' => 0,
                    'totalSites' => 0,
                    'siteCoverage' => 0,
                ];
            }

            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('Database connection failed in getKPIs: ' . $e->getMessage());
                throw $e; // Re-throw to be caught by outer catch
            }

            $siteIds = $this->resolveSiteIds();
            Log::debug('Resolved site IDs', ['count' => count($siteIds), 'siteIds' => $siteIds]);

            // ✅ Get accessible users and sites based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
            $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();

            Log::debug('Role-based filtering', [
                'accessible_users_count' => count($accessibleUserIds),
                'accessible_sites_count' => count($accessibleSiteIds),
                'user_role_id' => session('user')->role_id ?? 'unknown'
            ]);

            // Active Guards - Use shared service logic for perfect consistency with tables/modals
            $guardSearch = $this->hasValidFilter('guard_search') ? $this->resolveGuardUserIdFromSearch() : null;
            $userFilter = request('user');

            $activeGuards = $this->analyticsService->getActiveGuardsCount(
                $companyId,
                $userFilter ?: $guardSearch,
                $accessibleUserIds,
                $siteIds
            );

            Log::debug('Active Guards Count (Service Unified)', ['count' => $activeGuards, 'companyId' => $companyId]);

            // Patrols - Use applyCanonicalFilters with proper date handling
            $patrolQuery = DB::table('patrol_sessions')
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds); // ✅ Role-based filter

            // Apply canonical filters with date handling
            // Using same parameters as PatrolAnalyticsController for consistency
            $this->applyCanonicalFilters(
                $patrolQuery,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                false, // skipDateFilter = false (let it handle dates from request)
                false, // strictMode = false (don't force zero if filter doesn't match)
                true   // defaultTo30Days = true (fallback to 30 days if no dates provided)
            );

            Log::debug('Patrol Query After Filters', [
                'sql' => $patrolQuery->toSql(),
                'bindings' => $patrolQuery->getBindings()
            ]);

            // Log the actual SQL query for debugging
            Log::debug('Patrol Query SQL', [
                'sql' => $patrolQuery->toSql(),
                'bindings' => $patrolQuery->getBindings()
            ]);

            // Combined aggregation for Patrols
            $patrolStats = (clone $patrolQuery)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN ended_at IS NOT NULL THEN distance ELSE 0 END) as total_distance_raw
                ')
                ->first();

            $totalPatrols = optional($patrolStats)->total ?? 0;
            $completedPatrols = optional($patrolStats)->completed ?? 0;
            $ongoingPatrols = $totalPatrols - $completedPatrols;
            $totalDistance = round((optional($patrolStats)->total_distance_raw ?? 0) / 1000, 2);
            $avgDistancePerGuard = $activeGuards > 0 ? round($totalDistance / $activeGuards, 2) : 0;

            // Attendance
            // Logic: Record in attendance table = Present
            // Total possible attendance = Total guards × Days in range
            // Attendance rate = (Total present records / Total possible) × 100

            // Get all guards that should be counted for attendance (use same logic as activeGuards)
            // Re-using the logic from getActiveGuards to get IDs for filtering
            $guardIdsQuery = $this->analyticsService->getActiveGuards(
                $companyId,
                $userFilter ?: $guardSearch,
                $accessibleUserIds,
                $siteIds
            );
            $guardIds = $guardIdsQuery->pluck('id');

            $totalGuardsForAttendance = $activeGuards; // Re-use the optimized count from above

            // Count present records - Apply canonical filters
            $attendanceQuery = DB::table('attendance')
                ->where('attendance.company_id', $companyId)
                ->whereIn('attendance.user_id', $accessibleUserIds); // ✅ Role-based filter

            // Apply canonical filters with proper date handling
            $this->applyCanonicalFilters(
                $attendanceQuery,
                'attendance.dateFormat',
                'attendance.site_id',
                'attendance.user_id',
                false, // skipDateFilter = false
                false, // strictMode = false
                true   // defaultTo30Days = true
            );

            Log::debug('Attendance Query After Filters', [
                'sql' => $attendanceQuery->toSql(),
                'bindings' => $attendanceQuery->getBindings()
            ]);

            // Filter attendance by the guards we identified (from activeGuardsQuery)
            // Industry standard: Only count attendance for guards that match filter criteria
            if ($guardIds->isNotEmpty()) {
                $attendanceQuery->whereIn('attendance.user_id', $guardIds);
                Log::debug('Attendance filtered by guard IDs', ['count' => $guardIds->count()]);
            } else {
                // Industry standard: If no guards match filter criteria, show zero
                $attendanceQuery->whereRaw('1 = 0');
                Log::debug('No guards match filter criteria for attendance - returning zero', [
                    'has_range' => $this->hasValidFilter('range'),
                    'has_beat' => $this->hasValidFilter('beat'),
                    'has_user' => $this->hasValidFilter('user'),
                    'has_guard_search' => $this->hasValidFilter('guard_search')
                ]);
            }

            // Log attendance query for debugging
            Log::debug('Attendance Query SQL', [
                'sql' => $attendanceQuery->toSql(),
                'bindings' => $attendanceQuery->getBindings(),
                'guardIds_count' => $guardIds->count()
            ]);

            // Total present man-days (each record = 1 present day)
            $presentCount = (clone $attendanceQuery)->count();
            Log::debug('Attendance Count', ['presentCount' => $presentCount]);

            // 🔥 NEW: Total present guards specifically TODAY
            // We clone the base query but override the date filter to today
            $presentTodayQuery = DB::table('attendance')
                ->where('attendance.company_id', $companyId)
                ->where('attendance.dateFormat', now()->format('Y-m-d'))
                ->whereIn('attendance.user_id', $accessibleUserIds);

            // Apply site filters (resolved from request)
            $siteIdsForToday = $this->resolveSiteIds();
            if (!empty($siteIdsForToday)) {
                $presentTodayQuery->whereIn('attendance.site_id', $siteIdsForToday);
            }

            // Sync with active guards identified for this request
            if ($guardIds->isNotEmpty()) {
                $presentTodayQuery->whereIn('attendance.user_id', $guardIds);
            } else {
                $presentTodayQuery->whereRaw('1 = 0');
            }

            $presentCountToday = $presentTodayQuery->count();

            // Calculate days in range - use the validated dates
            $daysInRange = $startDate->diffInDays($endDate) + 1;

            // Total possible man-days
            $totalPossibleManDays = $totalGuardsForAttendance * $daysInRange;

            $attendanceRate = $totalPossibleManDays > 0
                ? round(($presentCount / $totalPossibleManDays) * 100, 1)
                : 0;

            // Incidents - Match IncidentController@summary logic for perfect synchronization
            // Source of truth for incidents are patrol_logs with incident types
            $incidentQuery = DB::table('patrol_logs')
                ->join('patrol_sessions', 'patrol_sessions.id', '=', 'patrol_logs.patrol_session_id')
                ->leftJoin('incidence_details', 'incidence_details.inc_id', '=', 'patrol_logs.id')
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds)
                ->where(function ($q) {
                    $q->where('patrol_logs.type', 'like', 'animal_sighting%')
                        ->orWhere('patrol_logs.type', 'like', 'Animal Sighting%')
                        ->orWhere('patrol_logs.type', 'like', 'water_source%')
                        ->orWhere('patrol_logs.type', 'like', 'Water Source%')
                        ->orWhere('patrol_logs.type', 'like', 'human_impact%')
                        ->orWhere('patrol_logs.type', 'like', 'Human Impact%')
                        ->orWhere('patrol_logs.type', 'like', 'animal_mortality%')
                        ->orWhere('patrol_logs.type', 'like', 'Animal Mortality%')
                        ->orWhere('patrol_logs.type', 'like', 'fire%')
                        ->orWhere('patrol_logs.type', 'like', 'Fire%');
                });

            // Apply canonical filters using passed dates for strict consistency
            $this->applyCanonicalFilters(
                $incidentQuery,
                'patrol_logs.created_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                false,
                false,
                true,
                $startDate,
                $endDate
            );

            Log::debug('Incident Query', [
                'sql' => $incidentQuery->toSql(),
                'bindings' => $incidentQuery->getBindings()
            ]);

            // Combined aggregation for Incidents
            $incidentsStats = (clone $incidentQuery)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN incidence_details.statusFlag = 1 THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN (incidence_details.statusFlag IN (0, 3, 4, 5, 6) OR incidence_details.statusFlag IS NULL) THEN 1 ELSE 0 END) as pending
                ')
                ->first();

            $totalIncidents = optional($incidentsStats)->total ?? 0;
            $pendingIncidents = optional($incidentsStats)->pending ?? 0;
            $resolvedIncidents = optional($incidentsStats)->resolved ?? 0;
            $resolutionRate = $totalIncidents > 0 ? round(($resolvedIncidents / $totalIncidents) * 100, 1) : 0;

            // Sites - Apply role-based and range/beat filters
            $siteQuery = DB::table('site_details')
                ->where('site_details.company_id', $companyId)
                ->whereIn('site_details.id', $accessibleSiteIds); // ✅ Role-based filter

            // Apply range/beat filters
            if ($this->hasValidFilter('range') || $this->hasValidFilter('beat')) {
                if (!empty($siteIds)) {
                    $siteQuery->whereIn('id', $siteIds);
                } else {
                    $siteQuery->whereRaw('1 = 0');
                }
            }

            $totalSites = $siteQuery->count();
            Log::debug('Total Sites', [
                'count' => $totalSites,
                'has_range_filter' => $this->hasValidFilter('range'),
                'has_beat_filter' => $this->hasValidFilter('beat'),
                'siteIds_count' => count($siteIds)
            ]);

            // Site Coverage: Calculate how many sites have patrol activity
            $sitesWithPatrolsQuery = DB::table('patrol_sessions')
                ->where('patrol_sessions.company_id', $companyId)
                ->whereNotNull('patrol_sessions.ended_at')
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds); // ✅ Role-based filter

            // Apply same filters as patrol query (site, user, guard_search) but skip date filter
            $this->applyCanonicalFilters(
                $sitesWithPatrolsQuery,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                true, // Skip date filter from request (we'll apply validated dates)
                true, // strictMode = true (industry standard: show zero if filter doesn't match)
                false // defaultTo30Days = false (we handle dates manually for validation)
            );

            // Always apply date range filter using the validated dates
            $sitesWithPatrolsQuery->whereBetween('patrol_sessions.started_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ]);

            $sitesWithPatrols = $sitesWithPatrolsQuery
                ->distinct()
                ->count('patrol_sessions.site_id');
            $siteCoverage = $totalSites > 0 ? round(($sitesWithPatrols / $totalSites) * 100, 1) : 0;

            return [
                'activeGuards' => $activeGuards,
                'totalPatrols' => $totalPatrols,
                'completedPatrols' => $completedPatrols,
                'ongoingPatrols' => $ongoingPatrols,
                'totalDistance' => $totalDistance,
                'avgDistancePerGuard' => $avgDistancePerGuard,
                'attendanceRate' => $attendanceRate,
                'presentCount' => $presentCount,
                'presentCountToday' => $presentCountToday,
                'totalIncidents' => $totalIncidents,
                'pendingIncidents' => $pendingIncidents,
                'resolvedIncidents' => $resolvedIncidents,
                'resolutionRate' => $resolutionRate,
                'totalSites' => $totalSites,
                'siteCoverage' => $siteCoverage,
            ];
        } catch (\Exception $e) {
            Log::error('getKPIs Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty data on error to prevent page crash
            return [
                'activeGuards' => 0,
                'totalPatrols' => 0,
                'completedPatrols' => 0,
                'ongoingPatrols' => 0,
                'totalDistance' => 0,
                'avgDistancePerGuard' => 0,
                'attendanceRate' => 0,
                'presentCount' => 0,
                'totalIncidents' => 0,
                'pendingIncidents' => 0,
                'resolvedIncidents' => 0,
                'resolutionRate' => 0,
                'totalSites' => 0,
                'siteCoverage' => 0,
            ];
        }
    }

    private function getGuardPerformanceRankings(Carbon $startDate, Carbon $endDate): array
    {
        $user = session('user');

        // Fallback to company_id 56 for testing
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        if (!$companyId) {
            return [
                'topPerformers' => [],
                'fullPerformance' => [],
            ];
        }

        $siteIds = $this->resolveSiteIds();
        $userId = request('user');

        // Role-based: only show users the current role can see (excludes other SuperAdmins and role_id 1 from lists)
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        // Use the dedicated service to get comprehensive performance data (excludes SuperAdmins)
        $fullPerformance = $this->analyticsService->getGuardPerformanceData(
            $startDate,
            $endDate,
            $companyId,
            $siteIds,
            $userId,
            $accessibleUserIds
        );

        // Debug: Log what we're getting
        Log::info('Guard Performance Data Count: ' . $fullPerformance->count());
        if ($fullPerformance->count() > 0) {
            Log::info('First Guard Data: ' . json_encode($fullPerformance->first()));
        }

        // Format names
        $fullPerformance = $fullPerformance->map(function ($guard) {
            $guard->name = FormatHelper::formatName($guard->name);
            return $guard;
        });

        return [
            'topPerformers' => $fullPerformance->take(5)->values(),
            'fullPerformance' => $fullPerformance,
        ];
    }

    private function getIncidentStatusTracking(Carbon $startDate, Carbon $endDate): array
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
        if (!$companyId) {
            return [];
        }

        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        // Use 'incidence_details' joined with 'patrol_logs' to catch all reported incidents
        // This ensures the counts match the Incident Summary report perfectly
        $base = DB::table('patrol_logs')
            ->join('patrol_sessions', 'patrol_sessions.id', '=', 'patrol_logs.patrol_session_id')
            ->leftJoin('incidence_details', 'incidence_details.inc_id', '=', 'patrol_logs.id')
            ->leftJoin('users', 'patrol_sessions.user_id', '=', 'users.id')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereIn('patrol_sessions.user_id', $accessibleUserIds)
            ->where(function ($q) {
                $q->where('patrol_logs.type', 'like', 'animal_sighting%')
                    ->orWhere('patrol_logs.type', 'like', 'Animal Sighting%')
                    ->orWhere('patrol_logs.type', 'like', 'water_source%')
                    ->orWhere('patrol_logs.type', 'like', 'Water Source%')
                    ->orWhere('patrol_logs.type', 'like', 'human_impact%')
                    ->orWhere('patrol_logs.type', 'like', 'Human Impact%')
                    ->orWhere('patrol_logs.type', 'like', 'animal_mortality%')
                    ->orWhere('patrol_logs.type', 'like', 'Animal Mortality%')
                    ->orWhere('patrol_logs.type', 'like', 'fire%')
                    ->orWhere('patrol_logs.type', 'like', 'Fire%');
            });

        $this->applyCanonicalFilters(
            $base,
            'patrol_logs.created_at',
            'patrol_sessions.site_id',
            'patrol_sessions.user_id',
            false,
            false,
            true,
            $startDate,
            $endDate
        );

        // 1. Status Distribution
        // Return raw flags so view can map them (View expects 5=>Critical etc)
        // Treat NULL status as 0 (Pending Supervisor)
        $statusDistribution = (clone $base)
            ->selectRaw('COALESCE(incidence_details.statusFlag, 0) as statusFlag, COUNT(*) as count')
            ->groupBy('statusFlag')
            ->get()
            ->pluck('count', 'statusFlag');

        // 2. Incident Types
        $incidentTypes = (clone $base)
            ->selectRaw('patrol_logs.type, COUNT(*) as count')
            ->groupBy('patrol_logs.type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 2b. Priority Distribution
        $priorityDistribution = collect();

        // 3. Resolution Time (Days)
        $resolutionTime = collect();

        // 4. Recent Incidents (Matching Pending statuses)
        $criticalIncidents = (clone $base)
            ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
            ->where(function ($q) {
                $q->whereIn('incidence_details.statusFlag', [0, 3, 4, 5, 6])
                    ->orWhereNull('incidence_details.statusFlag');
            })
            ->select(
                'patrol_logs.id',
                'patrol_logs.type',
                'site_details.name as site_name',
                'users.name as guard_name',
                'patrol_sessions.user_id as guard_id',
                'patrol_logs.created_at as dateFormat',
                DB::raw('COALESCE(incidence_details.statusFlag, 0) as statusFlag')
            )
            ->orderByDesc('patrol_logs.created_at')
            ->limit(10)
            ->get();

        // 5. Per-Site Resolution Table
        $incidentsBySite = (clone $base)
            ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
            ->selectRaw('
                site_details.name as site_name,
                COUNT(*) as incident_count,
                SUM(CASE WHEN incidence_details.statusFlag = 1 THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN (incidence_details.statusFlag IN (0, 3, 4, 5, 6) OR incidence_details.statusFlag IS NULL) THEN 1 ELSE 0 END) as pending_count
            ')
            ->groupBy('site_details.id', 'site_details.name')
            ->orderByDesc('incident_count')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->resolution_percentage = $row->incident_count > 0
                    ? round(($row->resolved_count / $row->incident_count) * 100, 1)
                    : 0;
                return $row;
            });

        return [
            'statusDistribution' => $statusDistribution,
            'incidentTypes' => $incidentTypes,
            'resolutionTime' => $resolutionTime,
            'criticalIncidents' => $criticalIncidents,
            'incidentsBySite' => $incidentsBySite,
            'priorityDistribution' => $priorityDistribution,
        ];
    }

    private function getPatrolAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
        if (!$companyId) {
            return [];
        }

        $siteIds = $this->resolveSiteIds();
        $userId = request('user');

        // Base Query consistent with PatrolController
        $query = DB::table('patrol_sessions')
            ->leftJoin('users', 'patrol_sessions.user_id', '=', 'users.id')
            ->where('patrol_sessions.company_id', $companyId);

        // Apply canonical filters (same as other methods)
        $this->applyCanonicalFilters(
            $query,
            'patrol_sessions.started_at',
            'patrol_sessions.site_id',
            'patrol_sessions.user_id',
            false,
            false,
            true,
            $startDate,
            $endDate
        );

        Log::debug('Patrol Analytics Query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        // 1. Patrols by Type (Routine, Special etc.)
        $patrolByType = (clone $query)
            ->whereNotNull('patrol_sessions.ended_at')
            ->selectRaw('
                patrol_sessions.type,
                COUNT(*) as count,
                SUM(CAST(COALESCE(patrol_sessions.distance, 0) AS DECIMAL)) as total_distance_raw
            ')
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                // Return total_distance for backward compatibility if needed
                $item->total_distance_km = round($item->total_distance_raw / 1000, 2);
                return $item;
            });

        // 2. Patrols by Mode/Session (Foot, Vehicle etc)
        $patrolBySession = (clone $query)
            ->selectRaw('patrol_sessions.session, COUNT(*) as count')
            ->groupBy('session')
            ->get();

        // Combined KPI / Counts for optimization
        $patrolStats = (clone $query)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed
            ')
            ->first();

        $totalPatrols = optional($patrolStats)->total ?? 0;
        $completedPatrols = optional($patrolStats)->completed ?? 0;

        // Night Patrols: 6pm to 6am (18:00 to 06:00)
        $nightPatrols = (clone $query)
            ->where(function ($q) {
                $q->whereTime('patrol_sessions.started_at', '>=', '18:00:00')
                    ->orWhereTime('patrol_sessions.started_at', '<=', '06:00:00');
            })
            ->count();

        // Foot Patrols: Daytime patrols (6am to 6pm)
        $footPatrols = (clone $query)
            ->whereTime('patrol_sessions.started_at', '>', '06:00:00')
            ->whereTime('patrol_sessions.started_at', '<', '18:00:00')
            ->count();

        Log::debug('Patrol Counts', [
            'total_patrols' => $totalPatrols,
            'completed_patrols' => $completedPatrols,
            'night_patrols' => $nightPatrols,
            'foot_patrols' => $footPatrols
        ]);

        // 4. Daily Trend
        $dailyTrend = (clone $query)
            ->whereNotNull('patrol_sessions.ended_at')
            ->selectRaw('
                DATE(patrol_sessions.started_at) as date,
                COUNT(*) as patrol_count,
                ROUND(SUM(COALESCE(patrol_sessions.distance,0)) / 1000, 2) as distance_km
            ')
            ->groupBy(DB::raw('DATE(patrol_sessions.started_at)'))
            ->orderBy('date')
            ->get();

        // 5. Distance by Site
        $distanceBySite = (clone $query)
            ->join('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
            ->whereNotNull('patrol_sessions.ended_at')
            ->selectRaw('
                site_details.name as site_name,
                SUM(COALESCE(patrol_sessions.distance, 0)) as total_distance_raw
            ')
            ->groupBy('site_details.id', 'site_details.name')
            ->get()
            ->map(function ($item) {
                $item->total_distance_km = round($item->total_distance_raw / 1000, 2);
                return $item;
            })
            ->sortByDesc('total_distance_km')
            ->take(10);

        return [
            'patrolByType' => $patrolByType,
            'patrolBySession' => $patrolBySession,
            'footPatrols' => $footPatrols,
            'nightPatrols' => $nightPatrols,
            'dailyTrend' => $dailyTrend,
            'distanceBySite' => $distanceBySite,
            'total_patrols' => $totalPatrols, // Added for API consumption
            'completed_patrols' => $completedPatrols, // Added for API consumption
        ];
    }

    private function getAttendanceAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
        if (!$companyId) {
            return [];
        }

        $siteIds = $this->resolveSiteIds();
        $userId = request('user');

        $query = DB::table('attendance')
            ->where('attendance.company_id', $companyId);

        $this->applyCanonicalFilters(
            $query,
            'attendance.dateFormat',
            'attendance.site_id',
            'attendance.user_id',
            false,
            false,
            true,
            $startDate,
            $endDate
        );

        // 1. Daily Trend (Present vs Late etc)
        // Note: 'attendance' table usually logs PRESENT people. Absent might not be row?
        // IF 'attendance_flag' = 1 (Present), 0 (Absent)?
        // Schema checks: 'attendance' table has 'attendance', 'lateTime'.
        $dailyTrend = (clone $query)
            ->selectRaw('
                dateFormat as date,
                COUNT(DISTINCT user_id) as present,
                SUM(CASE WHEN lateTime > 0 THEN 1 ELSE 0 END) as late
            ')
            ->groupBy('dateFormat')
            ->orderBy('dateFormat')
            ->get();

        // 2. Late Attendance Leaders
        $lateAttendance = (clone $query)
            ->leftJoin('users', 'attendance.user_id', '=', 'users.id')
            ->where('lateTime', '>', 0)
            ->selectRaw('
                users.name,
                COUNT(*) as late_count,
                AVG(lateTime) as avg_late_minutes
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('late_count')
            ->limit(10)
            ->get();

        // 3. Attendance by Site
        $attendanceBySite = (clone $query)
            ->join('site_details', 'attendance.site_id', '=', 'site_details.id')
            ->selectRaw('
                site_details.name as site_name,
                COUNT(DISTINCT attendance.user_id) as present_count,
                COUNT(*) as total_records
            ')
            ->groupBy('site_details.id', 'site_details.name')
            ->orderByDesc('present_count')
            ->get();

        return [
            'dailyTrend' => $dailyTrend,
            'lateAttendance' => $lateAttendance,
            'attendanceBySite' => $attendanceBySite,
        ];
    }

    private function getTimeBasedPatterns(Carbon $startDate, Carbon $endDate): array
    {
        $user = session('user');

        // Return empty data if user is not available
        if (!$user || !$user->company_id) {
            return [];
        }

        $siteIds = $this->resolveSiteIds();
        $userId = request('user');

        $query = DB::table('patrol_sessions')
            ->where('patrol_sessions.company_id', $user->company_id);

        $this->applyCanonicalFilters(
            $query,
            'patrol_sessions.started_at',
            'patrol_sessions.site_id',
            'patrol_sessions.user_id',
            false,
            false,
            true,
            $startDate,
            $endDate
        );

        $hourlyDistribution = (clone $query)
            ->selectRaw('HOUR(started_at) as hour, COUNT(*) as count')
            ->groupBy(DB::raw('HOUR(started_at)'))
            ->orderBy('hour')
            ->get();

        $peakHours = (clone $query)
            ->selectRaw('HOUR(started_at) as hour, COUNT(*) as count')
            ->groupBy(DB::raw('HOUR(started_at)'))
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $dayOfWeek = (clone $query)
            ->selectRaw('DAYNAME(started_at) as day_name, DAYOFWEEK(started_at) as day_num, COUNT(*) as count')
            ->groupBy('day_name', 'day_num')
            ->orderBy('day_num')
            ->get();

        return [
            'hourlyDistribution' => $hourlyDistribution,
            'peakHours' => $peakHours,
            'dayOfWeek' => $dayOfWeek,
        ];
    }

    private function getRiskZoneAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
        if (!$companyId) {
            return [];
        }

        $siteIds = $this->resolveSiteIds();
        $userId = request('user');

        // 1. High Incident Zones (from incidence_details)
        $incidentQuery = DB::table('incidence_details')
            ->leftJoin('site_details', 'incidence_details.site_id', '=', 'site_details.id')
            ->where('incidence_details.company_id', $companyId);

        $this->applyCanonicalFilters(
            $incidentQuery,
            'incidence_details.dateFormat',
            'incidence_details.site_id',
            'incidence_details.guard_id',
            false,
            false,
            true,
            $startDate,
            $endDate
        );

        $highIncidentZones = (clone $incidentQuery)
            ->selectRaw('
                site_details.name as site_name,
                COUNT(*) as incident_count,
                SUM(CASE WHEN type LIKE "animal%" THEN 1 ELSE 0 END) as animal_related,
                SUM(CASE WHEN type LIKE "human%" THEN 1 ELSE 0 END) as human_related
            ')
            ->groupBy('site_details.id', 'site_details.name')
            ->havingRaw('COUNT(*) >= 1')
            ->orderByDesc('incident_count')
            ->limit(10)
            ->get();

        // 2. Coverage Gaps (Sites with NO patrols)
        $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();
        $allSites = DB::table('site_details')
            ->where('site_details.company_id', $companyId)
            ->whereIn('site_details.id', $accessibleSiteIds);

        if (!empty($siteIds)) {
            $allSites->whereIn('site_details.id', $siteIds);
        }
        $allSiteIds = $allSites->pluck('id')->toArray();
        $allSitesMap = $allSites->pluck('name', 'id');

        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
        $patrolledSitesQuery = DB::table('patrol_sessions')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereIn('patrol_sessions.user_id', $accessibleUserIds)
            ->whereBetween('patrol_sessions.started_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ])
            ->whereNotNull('patrol_sessions.site_id');

        // Apply site filters if any
        if (!empty($siteIds)) {
            $patrolledSitesQuery->whereIn('patrol_sessions.site_id', $siteIds);
        }
        if ($userId) {
            $patrolledSitesQuery->where('patrol_sessions.user_id', $userId);
        }

        $patrolledSiteIds = $patrolledSitesQuery->distinct()->pluck('site_id')->toArray();

        // Gaps = All Sites - Patrolled Sites
        $gapIds = array_diff($allSiteIds, $patrolledSiteIds);
        $coverageGaps = collect($gapIds)->map(function ($id) use ($allSitesMap) {
            return (object) ['site_name' => $allSitesMap[$id] ?? 'Unknown Site'];
        })->values()->take(20);

        // 3. Most Patrolled Sites (Efficiency check)
        $mostPatrolledQuery = DB::table('patrol_sessions')
            ->join('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereIn('patrol_sessions.user_id', $accessibleUserIds)
            ->whereBetween('patrol_sessions.started_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ]);

        if (!empty($siteIds)) {
            $mostPatrolledQuery->whereIn('patrol_sessions.site_id', $siteIds);
        }
        if ($userId) {
            $mostPatrolledQuery->where('patrol_sessions.user_id', $userId);
        }

        $mostPatrolled = $mostPatrolledQuery
            ->selectRaw('site_details.name as site_name, COUNT(*) as patrol_count')
            ->groupBy('site_details.id', 'site_details.name')
            ->orderByDesc('patrol_count')
            ->limit(10)
            ->get();

        return [
            'highIncidentZones' => $highIncidentZones,
            'coverageGaps' => $coverageGaps,
            'mostPatrolled' => $mostPatrolled,
        ];
    }

    private function getCoverageAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
        if (!$companyId) {
            return [];
        }

        $siteIds = $this->resolveSiteIds();
        $userId = request('user');

        $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();
        $allSitesQuery = DB::table('site_details')
            ->where('site_details.company_id', $companyId)
            ->whereIn('site_details.id', $accessibleSiteIds);

        if (!empty($siteIds)) {
            $allSitesQuery->whereIn('site_details.id', $siteIds);
        }
        $totalSites = $allSitesQuery->count();

        // Count Patrolled Sites - Use canonical filters
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
        $patrolledSitesQuery = DB::table('patrol_sessions')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereIn('patrol_sessions.user_id', $accessibleUserIds)
            ->whereNotNull('patrol_sessions.site_id');

        // Apply canonical filters (site, user, guard_search) but skip date filter
        $this->applyCanonicalFilters(
            $patrolledSitesQuery,
            'patrol_sessions.started_at',
            'patrol_sessions.site_id',
            'patrol_sessions.user_id',
            true, // Skip date filter from request (we'll apply validated dates)
            true, // strictMode = true (industry standard: show zero if filter doesn't match)
            false // defaultTo30Days = false (we handle dates manually for validation)
        );

        // Always apply date range filter using the validated dates
        $patrolledSitesQuery->whereBetween('patrol_sessions.started_at', [
            $startDate->format('Y-m-d 00:00:00'),
            $endDate->format('Y-m-d 23:59:59')
        ]);

        $sitesWithPatrols = $patrolledSitesQuery->distinct('site_id')->count('site_id');
        $coveragePercentage = $totalSites > 0 ? round(($sitesWithPatrols / $totalSites) * 100, 1) : 0;

        Log::info('Coverage Calculation', [
            'sitesWithPatrols' => $sitesWithPatrols,
            'totalSites' => $totalSites,
            'calculation' => $totalSites > 0 ? ($sitesWithPatrols / $totalSites) * 100 : 0,
            'coveragePercentage' => $coveragePercentage
        ]);

        // Most Patrolled (Redundant with RiskZone but okay for specific view)
        $sitesMostPatrolled = (clone $patrolledSitesQuery)
            ->join('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
            ->selectRaw('site_details.name as site_name, COUNT(*) as patrol_count')
            ->groupBy('site_details.id', 'site_details.name')
            ->orderByDesc('patrol_count')
            ->limit(10)
            ->get();

        // Least Patrolled (But Visited)
        // Finding strictly least visited among those visited. Unvisited are in "Coverage Gaps".
        $sitesLeastPatrolled = (clone $patrolledSitesQuery)
            ->join('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
            ->selectRaw('site_details.name as site_name, COUNT(*) as patrol_count')
            ->groupBy('site_details.id', 'site_details.name')
            ->orderBy('patrol_count', 'asc')
            ->limit(10)
            ->get();

        return [
            'totalSites' => $totalSites,
            'sitesWithPatrols' => $sitesWithPatrols,
            'coveragePercentage' => $coveragePercentage,
            'sitesMostPatrolled' => $sitesMostPatrolled,
            'sitesLeastPatrolled' => $sitesLeastPatrolled,
        ];
    }

    /**
     * API endpoint to get active guards list for modal
     * ✅ Now applies role-based filtering
     */
    public function getActiveGuards(Request $request)
    {
        try {
            $user = session('user') ?? auth()->user();
            $companyId = (is_object($user)) ? ($user->company_id ?? 56) : ($user['company_id'] ?? 56);

            // ✅ Get accessible user IDs based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
            $siteIds = $this->resolveSiteIds();
            $guardSearch = $this->hasValidFilter('guard_search') ? $this->resolveGuardUserIdFromSearch() : null;
            $userFilter = request('user');

            // ✅ Use shared service logic for consistency
            $guards = $this->analyticsService->getActiveGuards(
                $companyId,
                $userFilter ?: $guardSearch,
                $accessibleUserIds,
                $siteIds
            );

            // Map data to expected format for modal
            $formattedGuards = $guards->map(function ($g) {
                // Fetch full user details if needed, or rely on service providing them
                // getActiveGuards currently selects id, name. We need email and phone.
                $fullUser = DB::table('users')->where('id', $g->id)->first();
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'email' => optional($fullUser)->email ?? 'N/A',
                    'phone' => optional($fullUser)->contact ?? 'N/A'
                ];
            });

            return response()->json(['guards' => $formattedGuards]);
        } catch (\Exception $e) {
            Log::error('API Error in getActiveGuards: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to get beats details for modal
     * ✅ Now applies role-based filtering
     */
    /**
     * API endpoint to get detailed coverage analysis for modal
     */
    public function getCoverageAnalysisApi(Request $request)
    {
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)
                : Carbon::now()->subDays(30);

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)
                : Carbon::now();

            $coverage = $this->getCoverageAnalysis($startDate, $endDate);
            $riskZones = $this->getRiskZoneAnalysis($startDate, $endDate);

            return response()->json([
                'success' => true,
                'summary' => [
                    'totalSites' => $coverage['totalSites'],
                    'sitesWithPatrols' => $coverage['sitesWithPatrols'],
                    'coveragePercentage' => $coverage['coveragePercentage'],
                ],
                'gaps' => $riskZones['coverageGaps'],
                'mostPatrolled' => $coverage['sitesMostPatrolled'],
                'leastPatrolled' => $coverage['sitesLeastPatrolled']
            ]);
        } catch (\Exception $e) {
            Log::error('Coverage API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getPatrolAnalyticsApi(Request $request)
    {
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : Carbon::now()->subDays(30)->startOfDay();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : Carbon::now()->endOfDay();

            if ($endDate->lt($startDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            // Optimized: Use getPatrolAnalytics directly and derive summary from its combined queries
            $analytics = $this->getPatrolAnalytics($startDate, $endDate);

            $totalPatrols = $analytics['total_patrols'] ?? 0;
            $completedPatrols = $analytics['completed_patrols'] ?? 0;
            // Sum raw distance first then convert to avoid rounding errors during summation
            $totalDistanceRaw = $analytics['patrolByType']->sum('total_distance_raw');
            $totalDistance = round($totalDistanceRaw / 1000, 2);
            $ongoing = $totalPatrols - $completedPatrols;

            return response()->json([
                'success' => true,
                'summary' => [
                    'totalPatrols' => $totalPatrols,
                    'completedPatrols' => $completedPatrols,
                    'ongoingPatrols' => $ongoing,
                    'totalDistance' => $totalDistance,
                    'completionRate' => $totalPatrols > 0 ? round(($completedPatrols / $totalPatrols) * 100, 1) : 0
                ],
                'breakdown' => $analytics['patrolByType'],
                'sessions' => $analytics['patrolBySession'],
                'dayNight' => [
                    'day' => $analytics['footPatrols'],
                    'night' => $analytics['nightPatrols']
                ],
                'topSites' => $analytics['distanceBySite']
            ]);
        } catch (\Exception $e) {
            Log::error('Patrol Analytics API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to get detailed patrols list by type for modal
     */
    public function getPatrolDetailsByTypeApi(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
            $type = $request->get('type', 'all');

            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

            $query = DB::table('patrol_sessions')
                ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
                ->leftJoin('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
                ->leftJoin('client_details', 'site_details.client_id', '=', 'client_details.id')
                ->where('patrol_sessions.company_id', $companyId);

            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
            $query->whereIn('patrol_sessions.user_id', $accessibleUserIds);

            if ($type !== 'all' && $type !== 'undefined' && $type !== 'Total') {
                $query->where('patrol_sessions.type', 'like', $type . '%');
            }

            $this->applyCanonicalFilters(
                $query,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                false,
                false,
                true,
                $startDate,
                $endDate
            );

            $patrols = $query->select(
                'patrol_sessions.id',
                'patrol_sessions.type',
                'users.name as guard_name',
                'users.contact as phone',
                'users.profile_pic',
                'site_details.name as site_name',
                'client_details.name as range_name',
                'patrol_sessions.started_at',
                'patrol_sessions.ended_at',
                DB::raw('ROUND(COALESCE(patrol_sessions.distance, 0) / 1000, 2) as distance_km'),
                'patrol_sessions.session'
            )
                ->orderByDesc('patrol_sessions.started_at')
                ->limit(100)
                ->get()
                ->map(function ($patrol) {
                    $patrol->formatted_start = Carbon::parse($patrol->started_at)->format('d M, h:i A');
                    $patrol->duration = $patrol->ended_at
                        ? (int) Carbon::parse($patrol->started_at)->diffInMinutes(Carbon::parse($patrol->ended_at)) . ' mins'
                        : 'Ongoing';
                    return $patrol;
                });

            return response()->json([
                'success' => true,
                'type' => $type,
                'patrols' => $patrols
            ]);
        } catch (\Exception $e) {
            Log::error('Patrol Details By Type API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getIncidentsDetailsApi(Request $request)
    {
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : Carbon::now()->subDays(30)->startOfDay();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : Carbon::now()->endOfDay();

            if ($endDate->lt($startDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            $incidentsData = $this->getIncidentStatusTracking($startDate, $endDate);

            // Directly calculate summary from the data we already fetched
            // Avoid calling getKPIs() which is slow and redundant
            $total = $incidentsData['incidentTypes']->sum('count');
            $statusCounts = $incidentsData['statusDistribution'];

            $resolved = $statusCounts->get(1, 0);
            $pending = $total - $resolved;
            $rate = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'summary' => [
                    'total' => $total,
                    'pending' => $pending,
                    'resolved' => $resolved,
                    'rate' => $rate,
                ],
                'statusDistribution' => $incidentsData['statusDistribution'],
                'types' => $incidentsData['incidentTypes'],
                'recent' => $incidentsData['criticalIncidents'],
                'sites' => $incidentsData['incidentsBySite'],
            ]);
        } catch (\Exception $e) {
            Log::error('Incidents Details API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getBeatsDetails(Request $request)
    {
        try {
            $user = session('user') ?? auth()->user();
            $companyId = 56; // Default fallback

            if (is_object($user)) {
                $companyId = $user->company_id ?? 56;
            } elseif (is_array($user)) {
                $companyId = $user['company_id'] ?? 56;
            }

            // ✅ Get accessible site IDs and apply filters
            $siteIds = $this->resolveSiteIds();

            // If no accessible sites, return empty array
            if (empty($siteIds)) {
                return response()->json(['beats' => []]);
            }

            // Determine date range
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)
                : Carbon::now()->subDays(30);

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)
                : Carbon::now();

            $query = DB::table('site_details')
                ->leftJoin('client_details', 'site_details.client_id', '=', 'client_details.id')
                ->where('site_details.company_id', $companyId)
                ->whereIn('site_details.id', $siteIds); // ✅ Use resolved site IDs (includes role and filter)

            $beats = $query->select(
                'site_details.id',
                'site_details.name',
                'client_details.name as range_name'
            )
                ->orderBy('site_details.name')
                ->get();

            return response()->json(['beats' => $beats]);
        } catch (\Exception $e) {
            Log::error('API Error in getBeatsDetails: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to get comprehensive distance details (breakdown by guard)
     */
    public function getDistanceDetailsApi(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            // Date validation
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->subDays(30);
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now();

            if ($startDate->isFuture())
                $startDate = Carbon::now()->subDays(30);
            if ($endDate->lt($startDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            // Apply canonical filters
            $query = DB::table('patrol_sessions')
                ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
                ->leftJoin('site_details', 'patrol_sessions.site_id', '=', 'site_details.id')
                ->leftJoin('client_details', 'site_details.client_id', '=', 'client_details.id')
                ->where('patrol_sessions.company_id', $companyId)
                ->whereNotNull('patrol_sessions.ended_at');

            // ✅ Role-based filter
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
            $query->whereIn('patrol_sessions.user_id', $accessibleUserIds);

            // Apply range/site/user filters from request
            $this->applyCanonicalFilters(
                $query,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                false,
                false,
                true,
                $startDate,
                $endDate
            );

            // 1. Get detailed breakdown (Guard + Site)
            $breakdown = (clone $query)
                ->select(
                    'users.id',
                    'users.name as guard_name',
                    'users.contact as phone',
                    'site_details.name as site_name',
                    'client_details.name as range_name',
                    DB::raw('SUM(patrol_sessions.distance) / 1000 as total_distance_km')
                )
                ->groupBy('users.id', 'users.name', 'users.contact', 'site_details.name', 'client_details.name')
                ->orderByDesc('total_distance_km')
                ->limit(50)
                ->get();

            // 2. Get Summary for the period
            $summaryQuery = clone $query;
            $totalDistance = round($summaryQuery->sum('patrol_sessions.distance') / 1000, 2);
            $activeGuardsCount = $summaryQuery->distinct('patrol_sessions.user_id')->count('patrol_sessions.user_id');
            $avgDistance = $activeGuardsCount > 0 ? round($totalDistance / $activeGuardsCount, 2) : 0;

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_distance_km' => $totalDistance,
                    'avg_distance_km' => $avgDistance,
                    'active_guards' => $activeGuardsCount
                ],
                'breakdown' => $breakdown
            ]);

        } catch (\Exception $e) {
            Log::error('Distance Details API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint to get comprehensive attendance details for modal
     */
    public function getAttendanceDetailsApi(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->subDays(30);
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now();

            if ($startDate->isFuture())
                $startDate = Carbon::now()->subDays(30);
            if ($endDate->lt($startDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

            $query = DB::table('attendance')
                ->join('users', 'attendance.user_id', '=', 'users.id')
                ->leftJoin('site_details', 'attendance.site_id', '=', 'site_details.id')
                ->leftJoin('client_details', 'site_details.client_id', '=', 'client_details.id')
                ->where('attendance.company_id', $companyId)
                ->whereIn('attendance.user_id', $accessibleUserIds);

            $this->applyCanonicalFilters(
                $query,
                'attendance.dateFormat',
                'attendance.site_id',
                'attendance.user_id',
                false,
                false,
                true,
                $startDate,
                $endDate
            );

            $summaryQuery = clone $query;
            $presentCount = (clone $summaryQuery)->count();

            // 1. Resolve Site IDs from Range/Beat filters
            $siteIds = $this->resolveSiteIds();
            $guardSearch = $this->hasValidFilter('guard_search') ? $this->resolveGuardUserIdFromSearch() : null;
            $userFilter = request('user');

            // 2. Get Active Guards using unified service (matches KPI tile logic)
            $activeGuardsQuery = $this->analyticsService->getActiveGuards(
                $companyId,
                $userFilter ?: $guardSearch,
                $accessibleUserIds,
                $siteIds
            );
            $activeGuardsCount = $activeGuardsQuery->count();
            $guardIds = $activeGuardsQuery->pluck('id');

            // 3. Guards Present Today specifically (matches KPI tile subtext)
            $presentTodayQuery = DB::table('attendance')
                ->where('attendance.company_id', $companyId)
                ->where('attendance.dateFormat', now()->format('Y-m-d'))
                ->whereIn('attendance.user_id', $accessibleUserIds);

            // Apply same filtering as attendance main query
            if (!empty($siteIds)) {
                $presentTodayQuery->whereIn('attendance.site_id', $siteIds);
            }

            if ($guardIds->isNotEmpty()) {
                $presentTodayQuery->whereIn('attendance.user_id', $guardIds);
            } else {
                $presentTodayQuery->whereRaw('1 = 0');
            }

            $presentCountToday = $presentTodayQuery->count();

            // 4. Attendance Rate calculation
            $daysInRange = (int) ($startDate->diffInDays($endDate) + 1);
            $totalPossible = $activeGuardsCount * $daysInRange;
            $attendanceRate = $totalPossible > 0 ? round(($presentCount / $totalPossible) * 100, 1) : 0;

            $breakdown = (clone $query)
                ->select(
                    'users.id',
                    'users.name as guard_name',
                    'users.contact as phone',
                    'client_details.name as range_name',
                    'site_details.name as site_name',
                    DB::raw('COUNT(*) as present_days'),
                    DB::raw('SUM(CASE WHEN attendance.lateTime > 0 THEN 1 ELSE 0 END) as late_days'),
                    DB::raw('AVG(CASE WHEN attendance.lateTime > 0 THEN attendance.lateTime ELSE NULL END) as avg_late_mins')
                )
                ->groupBy('users.id', 'users.name', 'users.contact', 'client_details.name', 'site_details.name')
                ->orderByDesc('present_days')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'summary' => [
                    'present_count' => $presentCount,
                    'present_count_today' => $presentCountToday,
                    'attendance_rate' => $attendanceRate,
                    'active_guards' => $activeGuardsCount,
                    'days_in_range' => $daysInRange
                ],
                'breakdown' => $breakdown
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance Details API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
