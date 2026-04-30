<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Traits\FilterDataTrait;
use App\Services\RoleBasedFilterService; // ✅ Add this

class DashboardController extends Controller
{
    use FilterDataTrait;

    public function index(Request $request)
    {
        try {
            $user = session('user');

            // ✅ Get accessible user IDs based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
            $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();

            // Guards KPI - Apply role-based filtering
            $guardsBase = DB::table('users')
                ->where('isActive', 1)
                ->where('company_id', $user->company_id)
                ->whereIn('id', $accessibleUserIds); // ✅ Role-based filter

            // Apply guard_search filter to guards
            if ($request->filled('guard_search')) {
                $guardUserId = $this->resolveGuardUserIdFromSearch();
                if ($guardUserId) {
                    $guardsBase->where('id', $guardUserId);
                } else {
                    $guardsBase->whereRaw('1 = 0');
                }
            }

            // Sites KPI - Apply role-based filtering
            $sitesBase = DB::table('site_details')
                ->where('company_id', $user->company_id)
                ->where('isActive', 1)
                ->whereIn('id', $accessibleSiteIds); // ✅ Role-based filter

            // Apply Range/Beat filters to sites
            if ($this->hasValidFilter('range') || $this->hasValidFilter('beat')) {
                $siteIds = $this->resolveSiteIds();
                if (!empty($siteIds)) {
                    $sitesBase->whereIn('id', $siteIds);
                }
            }

            // Apply guard_search filter to sites
            if ($request->filled('guard_search')) {
                $guardUserId = $this->resolveGuardUserIdFromSearch();
                if ($guardUserId) {
                    $assignedSiteIds = DB::table('site_assign')
                        ->where('user_id', $guardUserId)
                        ->where('company_id', $user->company_id)
                        ->whereNotNull('site_id')
                        ->pluck('site_id')
                        ->flatMap(function($siteId) {
                            return array_filter(explode(',', (string) $siteId));
                        })
                        ->map(function($id) {
                            return (int) trim($id);
                        })
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();

                    if (!empty($assignedSiteIds)) {
                        $sitesBase->whereIn('site_details.id', $assignedSiteIds);
                    } else {
                        $sitesBase->whereRaw('1 = 0');
                    }
                } else {
                    $sitesBase->whereRaw('1 = 0');
                }
            }

            // Patrols KPI - Apply role-based filtering
            $patrolsBase = DB::table('patrol_sessions')
                ->where('company_id', $user->company_id)
                ->whereIn('user_id', $accessibleUserIds); // ✅ Role-based filter
            
            $this->applyCanonicalFilters(
                $patrolsBase,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                false,
                false,
                true
            );

            // Distance KPI - Apply role-based filtering
            $distanceBase = DB::table('patrol_sessions')
                ->where('company_id', $user->company_id)
                ->whereIn('user_id', $accessibleUserIds); // ✅ Role-based filter
            
            $this->applyCanonicalFilters(
                $distanceBase,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                false,
                false,
                true
            );

            $kpis = [
                'guards' => $guardsBase->count(),
                'sites' => $sitesBase->count(),
                'patrols' => $patrolsBase->count(),
                'distance' => round($distanceBase->sum('distance'), 2),
            ];

            // Attendance Chart - Apply role-based filtering
            $attendanceBase = DB::table('attendance')
                ->where('company_id', $user->company_id)
                ->whereIn('user_id', $accessibleUserIds); // ✅ Role-based filter
            
            $this->applyCanonicalFilters(
                $attendanceBase,
                'attendance.dateFormat',
                'attendance.site_id',
                'attendance.user_id',
                false,
                false,
                true
            );

            $totalPresent = $attendanceBase->count();
            
            $attendanceChart = collect([
                'present' => $totalPresent,
                'late' => (clone $attendanceBase)->where('lateTime', '>', 0)->count(),
            ]);

            // Monthly Distance - Apply role-based filtering
            $monthlyDistanceBase = DB::table('patrol_sessions')
                ->where('company_id', $user->company_id)
                ->whereIn('user_id', $accessibleUserIds); // ✅ Role-based filter
            
            $this->applyCanonicalFilters(
                $monthlyDistanceBase,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                false,
                false,
                true
            );

            $monthlyDistance = $monthlyDistanceBase
                ->selectRaw('MONTH(started_at) as month, SUM(distance) as total')
                ->groupByRaw('MONTH(started_at)')
                ->orderBy('month')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            $kpis = [
                'guards' => 0,
                'sites' => 0,
                'patrols' => 0,
                'distance' => 0,
            ];
            $attendanceChart = collect([]);
            $monthlyDistance = collect([]);
            
            $filterData = [
                'ranges' => collect(),
                'beats' => collect(),
                'users' => collect(),
            ];
            
            return view('analytics.dashboard', array_merge(
                $filterData,
                [
                    'hideFilters' => false,
                    'kpis' => $kpis,
                    'attendanceChart' => $attendanceChart,
                    'monthlyDistance' => $monthlyDistance,
                ]
            ))->with('error', 'Failed to load dashboard data. Please check your database connection.');
        }

        $filterData = $this->filterData();

        return view('analytics.dashboard', array_merge(
            $filterData,
            [
                'hideFilters' => false,
                'kpis' => $kpis,
                'attendanceChart' => $attendanceChart,
                'monthlyDistance' => $monthlyDistance,
            ]
        ));
    }
}
