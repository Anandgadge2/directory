<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FilterDataTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\RoleBasedFilterService; // ✅ Add role-based filtering

class PatrolAnalyticsController extends Controller
{
    use FilterDataTrait;

    public function patrolAnalytics(Request $request)
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        // ✅ Get accessible users based on role
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        /* ===============================
           BASE QUERY
        ================================ */
        $base = DB::table('patrol_sessions')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereIn('patrol_sessions.user_id', $accessibleUserIds) // ✅ Role-based filter
            ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
            ->where('patrol_sessions.session', 'Foot')
            ->where('users.isActive', 1);

        // Apply canonical filters (date, site, user) with non-strict mode and 30-day fallback
        // Non-strict mode prevents whereRaw('1 = 0') when filters don't match
        // defaultTo30Days ensures fallback when no dates are provided
        $this->applyCanonicalFilters(
            $base,
            'patrol_sessions.started_at', // Date column
            'patrol_sessions.site_id',
            'patrol_sessions.user_id',
            false, // skipDateFilter = false (let it handle dates)
            false, // strictMode = false (don't force zero results)
            true   // defaultTo30Days = true (fallback to 30 days)
        );

        /* ===============================
           PER GUARD STATS
        ================================ */
        $guards = (clone $base)
            ->selectRaw('
                users.name as guard,
                COUNT(patrol_sessions.id) as total_sessions,
                SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN patrol_sessions.ended_at IS NULL THEN 1 ELSE 0 END) as ongoing,
                ROUND(SUM(COALESCE(patrol_sessions.distance,0)),2) as total_distance,
                ROUND(AVG(COALESCE(patrol_sessions.distance,0)),2) as avg_distance
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_distance')
            ->get();

        /* ===============================
           STATUS COUNTS (PIE)
        ================================ */
        $status = (clone $base)->selectRaw('
            SUM(CASE WHEN ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN ended_at IS NULL THEN 1 ELSE 0 END) as ongoing,
            SUM(CASE WHEN ended_at IS NULL OR distance IS NULL OR distance = 0 THEN 1 ELSE 0 END) as incomplete
        ')->first();

        /* ===============================
           KPI STATS (for dashboard cards)
        ================================ */
        $statsQuery = (clone $base)->selectRaw('
            COUNT(*) as total_sessions,
            SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed_sessions,
            SUM(CASE WHEN patrol_sessions.ended_at IS NULL THEN 1 ELSE 0 END) as active_sessions,
            ROUND(SUM(COALESCE(patrol_sessions.distance, 0)) / 1000, 2) as total_distance_km
        ')->first();

        // Convert stats object to array for easier access
        $stats = [
            'total_sessions' => (int) ($statsQuery->total_sessions ?? 0),
            'completed_sessions' => (int) ($statsQuery->completed_sessions ?? 0),
            'active_sessions' => (int) ($statsQuery->active_sessions ?? 0),
            'total_distance_km' => (float) ($statsQuery->total_distance_km ?? 0.00)
        ];

        $filterData = $this->filterData();

        return view('patrol.analytics', array_merge(
            $filterData,
            compact('guards', 'status', 'stats')
        ));
    }
}
