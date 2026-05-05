<?php
 
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\FormatHelper;
use App\Http\Controllers\Traits\FilterDataTrait;
 
class GuardDetailController extends Controller
{
    use FilterDataTrait;
 
    public function getGuardDetails($guardId, Request $request)
    {
        try {
 
            /* ================= BASIC GUARD ================= */
            $guard = DB::table('users')
                ->where('id', $guardId)
                ->where('isActive', 1)
                ->first();
 
            if (!$guard) {
                return response()->json(['success' => false], 404);
            }
 
            /* ================= ASSIGNMENT (RANGE / SITE / COMPARTMENT) ================= */
            $assignment = DB::table('site_assign')
                ->where('user_id', $guardId)
                ->first();
 
            $rangeName = $assignment->client_name ?? null;
            $siteName  = $assignment->site_name  ?? null; // Beat
            $compartmentName = null;
 
            if (!empty($assignment->site_id)) {
                $compartment = DB::table('site_geofences')
                    ->where('site_id', $assignment->site_id)
                    ->orderBy('id')
                    ->first();
                $compartmentName = $compartment->name ?? null;
            }
 
            /* ================= DATE RANGE ================= */
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = $request->start_date;
                $endDate   = $request->end_date;
            } elseif ($request->filled('start_date')) {
                $startDate = $request->start_date;
                $endDate   = Carbon::now()->toDateString();
            } elseif ($request->filled('end_date')) {
                $startDate = Carbon::parse($request->end_date)->subDays(30)->toDateString();
                $endDate   = $request->end_date;
            } else {
                $startDate = Carbon::now()->subDays(30)->toDateString();
                $endDate   = Carbon::now()->toDateString();
            }
 
            $companyId = session('user')->company_id ?? 56;
 
            /* ================= ATTENDANCE ================= */
            $attendanceBase = DB::table('attendance')
                ->where('user_id', $guardId)
                ->whereBetween('dateFormat', [$startDate, $endDate]);
 
            $presentDays = (clone $attendanceBase)
                ->select('dateFormat')
                ->distinct()
                ->count('dateFormat');
            $totalDays = $presentDays;
 
            $daysInRange = (int)(Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);
            $absentDays  = max($daysInRange - $presentDays, 0);
 
            $lateDays = (clone $attendanceBase)
                ->whereNotNull('lateTime')
                ->whereRaw('CAST(lateTime AS UNSIGNED) > 0')
                ->distinct('dateFormat')
                ->count('dateFormat');
 
            $attendanceRate = $daysInRange > 0
                ? round(($presentDays / $daysInRange) * 100, 1)
                : 0;
 
            /* ================= PATROL STATS ================= */
            $patrolBase = DB::table('patrol_sessions')
                ->where('user_id', $guardId)
                ->whereBetween('started_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
 
            $this->applyCanonicalFilters(
                $patrolBase,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                true // Skip date filter since we already applied it
            );
 
            $totalSessions     = (clone $patrolBase)->count();
            $completedSessions = (clone $patrolBase)->whereNotNull('ended_at')->count();
            $ongoingSessions   = $totalSessions - $completedSessions;
 
            $totalDistanceKm = round(
                (clone $patrolBase)->whereNotNull('ended_at')->sum('distance') / 1000,
                2
            );
 
            $avgDistanceKm = $completedSessions > 0
                ? round(
                    (clone $patrolBase)->whereNotNull('ended_at')->avg('distance') / 1000,
                    2
                )
                : 0;
 
            /* ================= INCIDENTS ================= */
            $incidentsQuery = DB::table('forest_reports')
                ->where('forest_reports.company_id', $companyId)
                ->where('forest_reports.user_id', $guardId)
                ->whereBetween('forest_reports.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);

            $totalIncidents = (clone $incidentsQuery)->count();

            $incidents = $incidentsQuery->orderByDesc('forest_reports.created_at')
                ->limit(10)
                ->select([
                    'forest_reports.id',
                    'forest_reports.report_type as type',
                    'forest_reports.created_at as date',
                    'forest_reports.range as site_name',
                    'forest_reports.report_data'
                ])
                ->get()
                ->map(function ($i) {
                    $payload = json_decode($i->report_data, true);
                    return [
                        'id'        => $i->id,
                        'type'      => ucwords(str_replace(['_', 'sighting', 'status'], [' ', 'Sighting', 'Status'], $i->type)),
                        'priority'  => $payload['priority'] ?? 'Normal',
                        'status'    => $payload['status'] ?? 'Logged',
                        'site_name' => $i->site_name ?? 'NA',
                        'remark'    => $payload['remark'] ?? ($payload['notes'] ?? 'No notes'),
                        'date'      => Carbon::parse($i->date)->format('Y-m-d'),
                        'time'      => Carbon::parse($i->date)->format('H:i:s'),
                    ];
                });
 
            /* ================= PATROL PATHS ================= */
            $patrolSessionsBase = DB::table('patrol_sessions')
                ->where('user_id', $guardId)
                ->whereBetween('started_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
 
            // Use the SAME simple applyCanonicalFilters call as File 2 (no extra params)
            $this->applyCanonicalFilters(
                $patrolSessionsBase,
                'patrol_sessions.started_at',
                'patrol_sessions.site_id',
                'patrol_sessions.user_id',
                true // Skip date filter since we already applied it
            );
 
            // Always re-force this specific guard
            $patrolSessionsBase->where('patrol_sessions.user_id', $guardId);
 
            $patrolSessions = $patrolSessionsBase
                ->orderByDesc('started_at')
                ->get();
 
            $patrolPaths = $patrolSessions->map(function ($p) {
 
                $path = null;
 
                /* 1. USE path_geojson IF PRESENT */
                if (!empty($p->path_geojson)) {
                    $path = $p->path_geojson;
                }
                /* 2. BUILD FROM patrol_logs */
                else {
                    $logs = DB::table('patrol_logs')
                        ->where('patrol_session_id', $p->id)
                        ->whereNotNull('lat')
                        ->whereNotNull('lng')
                        ->orderBy('created_at')
                        ->get(['lat', 'lng']);
 
                    if ($logs->count() >= 2) {
                        $path = json_encode([
                            'type'        => 'LineString',
                            'coordinates' => $logs->map(fn($l) => [
                                (float) $l->lng,
                                (float) $l->lat
                            ])->toArray()
                        ]);
                    }
                }
 
                /* 3. FALLBACK: START → END */
                if (!$path && $p->start_lat && $p->start_lng && $p->end_lat && $p->end_lng) {
                    $path = json_encode([
                        'type'        => 'LineString',
                        'coordinates' => [
                            [(float) $p->start_lng, (float) $p->start_lat],
                            [(float) $p->end_lng,   (float) $p->end_lat],
                        ]
                    ]);
                }
 
                if (!$path) return null;
 
                return [
                    'id'           => $p->id,
                    'path_geojson' => $path,
                    'started_at'   => $p->started_at ? Carbon::parse($p->started_at)->toDateTimeString() : null,
                    'ended_at'     => $p->ended_at   ? Carbon::parse($p->ended_at)->toDateTimeString()   : null,
                    'start_lat'    => $p->start_lat,
                    'start_lng'    => $p->start_lng,
                    'end_lat'      => $p->end_lat,
                    'end_lng'      => $p->end_lng,
                    'distance'     => (float) ($p->distance ?? 0),
                    'session'      => $p->session,
                    'type'         => $p->type,
                ];
            })
            ->filter()
            ->values();
 
            /* ================= GEOFENCES (COMPARTMENTS) ================= */
            // Collect site IDs from assignment + patrol sessions
            $assignedSiteIds = DB::table('site_assign')
                ->where('user_id', $guardId)
                ->pluck('site_id')
                ->filter()
                ->toArray();
 
            $patrolledSiteIds = DB::table('patrol_sessions')
                ->where('user_id', $guardId)
                ->whereBetween('started_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->distinct()
                ->pluck('site_id')
                ->filter()
                ->toArray();
 
            $allSiteIds = array_unique(array_merge($assignedSiteIds, $patrolledSiteIds));
 
            $geofences = DB::table('site_geofences')
                ->whereIn('site_id', !empty($allSiteIds) ? $allSiteIds : [0])
                ->where('company_id', $companyId)
                ->get()
                ->map(function ($g) {
                    return [
                        'id'           => $g->id,
                        'name'         => $g->name,
                        'lat'          => $g->lat,
                        'lng'          => $g->lng,
                        'radius'       => $g->radius,
                        'poly_lat_lng' => $g->poly_lat_lng,
                        'type'         => $g->type,
                        'site_name'    => DB::table('site_details')->where('id', $g->site_id)->value('name'),
                    ];
                });
 
            /* ================= RESPONSE ================= */
            $responseData = [
                'success' => true,
                'guard'   => [
                    'id'           => $guard->id,
                    'name'         => FormatHelper::formatName($guard->name),
                    'gen_id'       => $guard->gen_id,
                    'designation'  => $guard->designation,
                    'contact'      => $guard->contact,
                    'email'        => $guard->email,
                    'company_name' => $guard->company_name,
                    'range'        => $rangeName,
                    'site'         => $siteName,
                    'compartment'  => $compartmentName,
 
                    'attendance_stats' => [
                        'month'           => Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('M d, Y'),
                        'total_days'      => $totalDays,
                        'present_days'    => $presentDays,
                        'absent_days'     => $absentDays,
                        'late_days'       => $lateDays,
                        'attendance_rate' => $attendanceRate,
                    ],
 
                    'patrol_stats' => [
                        'total_sessions'     => $totalSessions,
                        'completed_sessions' => $completedSessions,
                        'ongoing_sessions'   => $ongoingSessions,
                        'total_distance_km'  => $totalDistanceKm,
                        'avg_distance_km'    => $avgDistanceKm,
                    ],
 
                    'incident_stats' => [
                        'total_incidents' => $totalIncidents,
                        'latest'          => $incidents,
                    ],
 
                    'patrol_paths' => $patrolPaths,
                ],
                // Geofences at root level so frontend can access as res.geofences
                'geofences' => $geofences,
            ];
 
            return response()->json($this->cleanUtf8($responseData));
 
        } catch (\Throwable $e) {
            Log::error('Guard Detail Error', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
 
    /**
     * Recursively clean malformed UTF-8 characters from data.
     */
    private function cleanUtf8($data)
    {
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
 
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8'], $data);
        }
 
        if ($data instanceof \Illuminate\Support\Collection) {
            return $data->map(function ($item) {
                return $this->cleanUtf8($item);
            });
        }
 
        if (is_object($data)) {
            foreach (get_object_vars($data) as $key => $value) {
                $data->$key = $this->cleanUtf8($value);
            }
            return $data;
        }
 
        return $data;
    }
}