<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsDataService
{
    /**
     * Get patrol statistics for guards
     * Uses proven query from PatrolController
     */
    public function getGuardPatrolStats(Carbon $startDate, Carbon $endDate, $companyId, array $siteIds = [], $userId = null)
    {
        $query = DB::table('patrol_sessions')
            ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereBetween('patrol_sessions.started_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ])
            ->where('users.isActive', 1);

        if (!empty($siteIds)) {
            $query->whereIn('patrol_sessions.site_id', $siteIds);
        }

        if ($userId) {
            $query->where('patrol_sessions.user_id', $userId);
        }

        return $query
            ->selectRaw('
                users.id,
                users.name,
                COUNT(*) as total_sessions,
                SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed_sessions,
                SUM(CASE WHEN patrol_sessions.ended_at IS NULL THEN 1 ELSE 0 END) as ongoing_sessions,
                ROUND(SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN patrol_sessions.distance ELSE 0 END) / 1000, 2) as total_distance_km,
                ROUND(AVG(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN patrol_sessions.distance ELSE NULL END) / 1000, 2) as avg_distance_per_session,
                ROUND(AVG(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, patrol_sessions.started_at, patrol_sessions.ended_at) ELSE NULL END), 2) as avg_duration_hours
            ')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('id');
    }

    /**
     * Get attendance statistics for guards
     * Logic: If guard has record in attendance table for a date = Present
     *        If guard has NO record for a date = Absent
     * attendance_flag is NOT used
     * Uses exact query from AttendanceController (working)
     */
    public function getGuardAttendanceStats(Carbon $startDate, Carbon $endDate, $companyId, array $siteIds = [], $userId = null)
    {
        $query = DB::table('attendance')
            ->where('company_id', $companyId)
            ->whereBetween('dateFormat', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);

        if (!empty($siteIds)) {
            $query->whereIn('site_id', $siteIds);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Get attendance grouped by user
        $attendanceByUser = (clone $query)
            ->selectRaw('
                user_id,
                COUNT(DISTINCT dateFormat) as days_present,
                SUM(CASE WHEN lateTime > 0 THEN 1 ELSE 0 END) as late_days,
                AVG(CASE WHEN lateTime > 0 THEN lateTime ELSE NULL END) as avg_late_minutes
            ')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // Get user names
        $userIds = $attendanceByUser->keys()->toArray();
        if (empty($userIds)) {
            return collect();
        }

        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        // Combine with user names
        return $attendanceByUser->map(function($stats) use ($users) {
            $user = $users->get($stats->user_id);
            $stats->id = $stats->user_id;
            $stats->name = $user ? $user->name : 'Unknown';
            return $stats;
        })->keyBy('id');
    }

    /**
     * Get incident statistics for guards
     * Uses proven query from IncidentController
     */
    public function getGuardIncidentStats(Carbon $startDate, Carbon $endDate, $companyId, array $siteIds = [], $userId = null)
    {
        // 1. Get incidents from incidence_details (Primary Analytics Table)
        $incQuery = DB::table('incidence_details')
            ->join('users', 'incidence_details.guard_id', '=', 'users.id')
            ->where('incidence_details.company_id', $companyId)
            ->whereBetween('incidence_details.dateFormat', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->where('users.isActive', 1)
            ->whereNotNull('incidence_details.type')
            ->whereNotIn('incidence_details.type', ['Other', 'other', '']);

        if (!empty($siteIds)) {
            $incQuery->whereIn('incidence_details.site_id', $siteIds);
        }
        if ($userId) {
            $incQuery->where('incidence_details.guard_id', $userId);
        }

        $incStats = $incQuery
            ->selectRaw('
                users.id,
                COUNT(*) as total_incidents,
                SUM(CASE WHEN incidence_details.statusFlag = 1 THEN 1 ELSE 0 END) as resolved_incidents,
                SUM(CASE WHEN incidence_details.statusFlag IN (0, 3, 4, 5, 6) THEN 1 ELSE 0 END) as pending_incidents
            ')
            ->groupBy('users.id')
            ->get()
            ->keyBy('id');

        // 2. Get incidents from patrol_logs (Raw App Logs - fallback)
        $logQuery = DB::table('patrol_logs')
            ->join('patrol_sessions', 'patrol_logs.patrol_session_id', '=', 'patrol_sessions.id')
            ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereBetween('patrol_logs.created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ])
            ->where('users.isActive', 1)
            ->whereIn('patrol_logs.type', ['animal_sighting', 'water_source', 'human_impact', 'animal_mortality', 'fire', 'Fire']);

        if (!empty($siteIds)) {
            $logQuery->whereIn('patrol_sessions.site_id', $siteIds);
        }
        if ($userId) {
            $logQuery->where('patrol_sessions.user_id', $userId);
        }

        $logStats = $logQuery
            ->selectRaw('users.id, COUNT(*) as log_count')
            ->groupBy('users.id')
            ->get()
            ->keyBy('id');

        // 3. Merge Results - If a guard has 0 in incidence_details but >0 in patrol_logs, we use the latter
        // This matches the fallback logic in GuardDetailController
        $results = collect();
        $allUserIds = $incStats->keys()->merge($logStats->keys())->unique();

        foreach ($allUserIds as $id) {
            $inc = $incStats->get($id);
            $log = $logStats->get($id);
            
            // If we have incidence_details records, they take precedence for status tracking
            if ($inc && $inc->total_incidents > 0) {
                $results->put($id, (object)[
                    'id' => $id,
                    'total_incidents' => $inc->total_incidents,
                    'resolved_incidents' => $inc->resolved_incidents,
                    'pending_incidents' => $inc->pending_incidents,
                    'high_priority_incidents' => 0, // Placeholder for legacy compatibility
                ]);
            } else if ($log) {
                // Otherwise use patrol_logs but mark as pending (since they haven't been reviewed/synced yet)
                $results->put($id, (object)[
                    'id' => $id,
                    'total_incidents' => $log->log_count,
                    'resolved_incidents' => 0,
                    'pending_incidents' => $log->log_count,
                    'high_priority_incidents' => 0,
                ]);
            }
        }

        return $results;
    }

    /**
     * Get foot patrol statistics
     * Uses proven query from PatrolController footSummary
     */
    public function getFootPatrolStats(Carbon $startDate, Carbon $endDate, $companyId, array $siteIds = [], $userId = null)
    {
        $query = DB::table('patrol_sessions')
            ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
            ->where('patrol_sessions.company_id', $companyId)
            ->where('patrol_sessions.session', 'Foot')
            ->whereBetween('patrol_sessions.started_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ])
            ->where('users.isActive', 1);

        if (!empty($siteIds)) {
            $query->whereIn('patrol_sessions.site_id', $siteIds);
        }

        if ($userId) {
            $query->where('patrol_sessions.user_id', $userId);
        }

        return $query
            ->selectRaw('
                users.id,
                users.name,
                COUNT(*) as foot_patrol_count,
                SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed_foot_patrols,
                ROUND(SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN patrol_sessions.distance ELSE 0 END) / 1000, 2) as foot_patrol_distance_km
            ')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('id');
    }

    /**
     * Get night patrol statistics
     * Uses proven query from PatrolController nightSummary
     */
    public function getNightPatrolStats(Carbon $startDate, Carbon $endDate, $companyId, array $siteIds = [], $userId = null)
    {
        $query = DB::table('patrol_sessions')
            ->join('users', 'patrol_sessions.user_id', '=', 'users.id')
            ->where('patrol_sessions.company_id', $companyId)
            ->whereBetween('patrol_sessions.started_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ])
            ->where(function ($q) {
                $q->whereTime('patrol_sessions.started_at', '>=', '18:00:00')
                  ->orWhereTime('patrol_sessions.started_at', '<=', '06:00:00');
            })
            ->where('users.isActive', 1);

        if (!empty($siteIds)) {
            $query->whereIn('patrol_sessions.site_id', $siteIds);
        }

        if ($userId) {
            $query->where('patrol_sessions.user_id', $userId);
        }

        return $query
            ->selectRaw('
                users.id,
                users.name,
                COUNT(*) as night_patrol_count,
                SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN 1 ELSE 0 END) as completed_night_patrols,
                ROUND(SUM(CASE WHEN patrol_sessions.ended_at IS NOT NULL THEN patrol_sessions.distance ELSE 0 END) / 1000, 2) as night_patrol_distance_km
            ')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->keyBy('id');
    }

    /**
     * Get all active guards for a company.
     * SuperAdmins (role_id = 1) are never included — they do not patrol or appear in operational data.
     *
     * @param int $companyId
     * @param int|null $userId Optional single user filter (e.g. from request)
     * @param array|null $accessibleUserIds Optional list from RoleBasedFilterService (role-based scope)
     */
    public function getActiveGuards($companyId, $userId = null, ?array $accessibleUserIds = null, array $siteIds = [])
    {
        $query = DB::table('users')
            ->where('users.company_id', $companyId)
            ->where('users.isActive', 1)
            ->where('users.role_id', '!=', 1) // Never include SuperAdmins in guard/operational lists
            ->select('users.id', 'users.name');

        if ($userId) {
            $query->where('users.id', $userId);
        }

        // Apply site filters if provided
        if (!empty($siteIds)) {
            $query->where(function ($q) use ($siteIds, $companyId) {
                // 1. Guards assigned to these sites
                $q->whereIn('users.id', function ($sub) use ($siteIds, $companyId) {
                    $sub->select('user_id')
                        ->from('site_assign')
                        ->where('company_id', $companyId)
                        ->where(function ($s) use ($siteIds) {
                            foreach ($siteIds as $siteId) {
                                $s->orWhereRaw('JSON_CONTAINS(site_id, ?)', [json_encode((string)$siteId)])
                                  ->orWhereRaw('FIND_IN_SET(?, site_id)', [$siteId]);
                            }
                        });
                });

                // 2. OR guards who performed patrols in these sites during any period (for consistency)
                $q->orWhereIn('users.id', function ($sub) use ($siteIds, $companyId) {
                    $sub->select('user_id')
                        ->from('patrol_sessions')
                        ->where('company_id', $companyId)
                        ->whereIn('site_id', $siteIds);
                });
            });
        }

        // When role-scoped list is provided: empty = show no guards (e.g. supervisor with no assigned guards)
        if ($accessibleUserIds !== null) {
            if (!empty($accessibleUserIds)) {
                $query->whereIn('users.id', $accessibleUserIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->get();
    }

    /**
     * Optimized version of getActiveGuards that returns ONLY the count.
     * Use this for KPIs to avoid loading thousands of objects into memory.
     */
    public function getActiveGuardsCount($companyId, $userId = null, ?array $accessibleUserIds = null, array $siteIds = [])
    {
        $query = DB::table('users')
            ->where('users.company_id', $companyId)
            ->where('users.isActive', 1)
            ->where('users.role_id', '!=', 1);

        if ($userId) {
            $query->where('users.id', $userId);
        }

        if (!empty($siteIds)) {
            $query->where(function ($q) use ($siteIds, $companyId) {
                $q->whereIn('users.id', function ($sub) use ($siteIds, $companyId) {
                    $sub->select('user_id')->from('site_assign')->where('company_id', $companyId)
                        ->where(function ($s) use ($siteIds) {
                            foreach ($siteIds as $siteId) {
                                $s->orWhereRaw('JSON_CONTAINS(site_id, ?)', [json_encode((string)$siteId)])
                                  ->orWhereRaw('FIND_IN_SET(?, site_id)', [$siteId]);
                            }
                        });
                })->orWhereIn('users.id', function ($sub) use ($siteIds, $companyId) {
                    $sub->select('user_id')->from('patrol_sessions')->where('company_id', $companyId)->whereIn('site_id', $siteIds);
                });
            });
        }

        if ($accessibleUserIds !== null) {
            if (!empty($accessibleUserIds)) {
                $query->whereIn('users.id', $accessibleUserIds);
            } else {
                return 0;
            }
        }

        return $query->count();
    }

    /**
     * Get comprehensive guard performance data
     * Combines all statistics into one result set.
     * SuperAdmins (role_id = 1) are never included.
     *
     * @param array|null $accessibleUserIds From RoleBasedFilterService::getAccessibleUserIds() for role-based scope
     */
    public function getGuardPerformanceData(Carbon $startDate, Carbon $endDate, $companyId, array $siteIds = [], $userId = null, ?array $accessibleUserIds = null)
    {
        // Get all statistics (filtered by site/user as needed)
        $patrolStats = $this->getGuardPatrolStats($startDate, $endDate, $companyId, $siteIds, $userId);
        $attendanceStats = $this->getGuardAttendanceStats($startDate, $endDate, $companyId, $siteIds, $userId);
        $incidentStats = $this->getGuardIncidentStats($startDate, $endDate, $companyId, $siteIds, $userId);
        $footPatrolStats = $this->getFootPatrolStats($startDate, $endDate, $companyId, $siteIds, $userId);
        $nightPatrolStats = $this->getNightPatrolStats($startDate, $endDate, $companyId, $siteIds, $userId);

        // Get active guards (excludes role_id 1; optionally scoped by accessibleUserIds and siteIds)
        $guards = $this->getActiveGuards($companyId, $userId, $accessibleUserIds, $siteIds);

        // Calculate date range for attendance rate
        $daysInRange = $startDate->diffInDays($endDate) + 1;

        // Find max values for normalization (before the loop for efficiency)
        $maxDistance = $patrolStats->max('total_distance_km') ?? 1; // Avoid division by zero
        $maxIncidents = $incidentStats->max('total_incidents') ?? 1; // Avoid division by zero
        
        // If all guards have 0 distance or 0 incidents, use a reasonable default for normalization
        if ($maxDistance == 0) $maxDistance = 500; // Default target: 500km
        if ($maxIncidents == 0) $maxIncidents = 20; // Default target: 20 incidents

        // Combine data
        $performance = collect();
        foreach ($guards as $guard) {
            $patrol = $patrolStats->get($guard->id);
            $attendance = $attendanceStats->get($guard->id);
            $incidents = $incidentStats->get($guard->id);
            $footPatrol = $footPatrolStats->get($guard->id);
            $nightPatrol = $nightPatrolStats->get($guard->id);

            // Calculate performance score
            // Formula: Patrol Distance (50%) + Attendance (30%) + Incidents Reported (20%)
            
            // 1. Patrol Distance Component (50%): Normalize distance to 0-100 scale
            $guardDistance = $patrol ? $patrol->total_distance_km : 0;
            $distanceScore = min(100, ($guardDistance / $maxDistance) * 100);
            $distanceComponent = $distanceScore * 0.5; // 50% weight
            
            // 2. Attendance Component (30%): Use attendance rate (already 0-100)
            $attendanceRate = $attendance && $daysInRange > 0 
                ? round(($attendance->days_present / $daysInRange) * 100, 1) 
                : 0;
            $attendanceComponent = $attendanceRate * 0.3; // 30% weight
            
            // 3. Incidents Reported Component (20%): Normalize incidents to 0-100 scale
            // More incidents reported = better (shows vigilance and reporting)
            $guardIncidents = $incidents ? $incidents->total_incidents : 0;
            $incidentsScore = min(100, ($guardIncidents / $maxIncidents) * 100);
            $incidentsComponent = $incidentsScore * 0.2; // 20% weight
            
            // Final score: Sum of all weighted components
            // Max possible: 50 + 30 + 20 = 100
            $score = $distanceComponent + $attendanceComponent + $incidentsComponent;
            
            // Constraint: 0 <= score <= 100 (should already be within range, but ensure)
            $score = max(0, min(100, $score));

            $performance->push((object) [
                'id' => $guard->id,
                'name' => $guard->name,
                // Patrol stats
                'patrol_sessions' => $patrol ? $patrol->total_sessions : 0,
                'completed_sessions' => $patrol ? $patrol->completed_sessions : 0,
                'ongoing_sessions' => $patrol ? $patrol->ongoing_sessions : 0,
                'total_distance_km' => $patrol ? $patrol->total_distance_km : 0,
                'avg_distance_per_session' => $patrol ? $patrol->avg_distance_per_session : 0,
                'avg_duration_hours' => $patrol ? $patrol->avg_duration_hours : 0,
                // Attendance stats
                'days_present' => $attendance ? $attendance->days_present : 0,
                'late_days' => $attendance ? $attendance->late_days : 0,
                'avg_late_minutes' => $attendance ? $attendance->avg_late_minutes : 0,
                'attendance_rate' => $attendance && $daysInRange > 0 
                    ? round(($attendance->days_present / $daysInRange) * 100, 1) 
                    : 0,
                // Incident stats
                'incidents_reported' => $incidents ? $incidents->total_incidents : 0,
                'resolved_incidents' => $incidents ? $incidents->resolved_incidents : 0,
                'pending_incidents' => $incidents ? $incidents->pending_incidents : 0,
                'high_priority_incidents' => $incidents ? $incidents->high_priority_incidents : 0,
                // Specialized patrol stats
                'foot_patrol_count' => $footPatrol ? $footPatrol->foot_patrol_count : 0,
                'foot_patrol_distance_km' => $footPatrol ? $footPatrol->foot_patrol_distance_km : 0,
                'night_patrol_count' => $nightPatrol ? $nightPatrol->night_patrol_count : 0,
                'night_patrol_distance_km' => $nightPatrol ? $nightPatrol->night_patrol_distance_km : 0,
                // Performance score
                'performance_score' => round($score, 1),
            ]);
        }

        return $performance->sortByDesc('performance_score')->values();
    }
}
