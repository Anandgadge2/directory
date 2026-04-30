<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MonthlyPatrolExport;
use App\Http\Controllers\Traits\FilterDataTrait;
use App\Services\RoleBasedFilterService;

class ReportController extends Controller
{
    use FilterDataTrait;

    /* ================= MONTHLY REPORT ================= */
    /* ================= MONTHLY REPORT (Unified Hub) ================= */
    public function monthly(Request $request)
    {
        $reportType = $request->input('report_type');
        $export = $request->input('export');
        $data = null;
        $title = '';
        $summary = null;
        $kpis = [];

        // Date Range handling
        $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date) : \Carbon\Carbon::now()->subDays(30);
        $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date) : \Carbon\Carbon::now();
        $daysInRange = (int)($startDate->diffInDays($endDate) + 1);

        // Fetch Filter Data
        $ranges = DB::table('client_details')->orderBy('name')->pluck('name', 'id');
        $beats  = DB::table('site_details')->orderBy('name')->pluck('name', 'id');
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
        $users  = empty($accessibleUserIds) ? collect() : DB::table('users')->where('isActive', 1)->where('role_id', '!=', 1)->whereIn('id', $accessibleUserIds)->orderBy('name')->pluck('name', 'id');

        if ($reportType) {
            $companyId = session('user')->company_id ?? 56;

            if ($reportType === 'attendance') {
                $title = 'Guard Attendance Analysis';
                
                $baseQuery = DB::table('attendance')
                    ->join('users', 'attendance.user_id', '=', 'users.id')
                    ->leftJoin('site_details', 'attendance.site_id', '=', 'site_details.id')
                    ->where('attendance.company_id', $companyId);
                
                $this->applyCanonicalFilters($baseQuery, 'attendance.dateFormat', 'attendance.site_id', 'attendance.user_id');

                $data = (clone $baseQuery)
                    ->select(
                        'users.name as guard_name',
                        'users.id as guard_id',
                        'users.contact as guard_mobile',
                        'site_details.name as site_name',
                        'attendance.dateFormat as date',
                        'attendance.lateTime as late_minutes',
                        'attendance.attendance_flag as status',
                        'attendance.inOutStatus',
                        'attendance.location',
                        'attendance.entry_time',
                        'attendance.exit_time',
                        'attendance.date as date_display'
                    )
                    ->orderByDesc('attendance.dateFormat')
                    ->limit(500)
                    ->get();

                $summary = (clone $baseQuery)
                    ->select(
                        'users.id as guard_id',
                        'users.name as guard_name',
                        DB::raw('COUNT(*) as present_days'),
                        DB::raw('SUM(CASE WHEN attendance.lateTime > 0 THEN 1 ELSE 0 END) as late_count')
                    )
                    ->groupBy('users.id', 'users.name')
                    ->get()
                    ->map(function($s) use ($daysInRange) {
                        $s->total_days = (int)$daysInRange;
                        $s->attendance_rate = $daysInRange > 0 ? (int)round(($s->present_days / $daysInRange) * 100) : 0;
                        $s->absent_days = (int)max(0, $daysInRange - $s->present_days);
                        return $s;
                    })->sortByDesc('attendance_rate');

                $kpis = [
                    'Avg Attendance' => round($summary->avg('attendance_rate') ?? 0) . '%',
                    'Total Present' => $data->count(),
                    'Late Occurrences' => $summary->sum('late_count')
                ];

                // Fetch geofences for map view if single day
                $geofences = [];
                if ($daysInRange === 1) {
                    $geofences = DB::table('site_geofences')
                        ->leftJoin('site_details', 'site_geofences.site_id', '=', 'site_details.id')
                        ->leftJoin('client_details', 'site_details.client_id', '=', 'client_details.id')
                        ->where('site_geofences.company_id', $companyId)
                        ->whereNull('site_geofences.deleted_at')
                        ->select('site_geofences.*', 'site_details.name as site_name', 'client_details.name as range_name')
                        ->get();
                }
                view()->share('geofences', $geofences);
                view()->share('daysInRange', $daysInRange);

            } elseif ($reportType === 'patrol' || $reportType === 'night_patrol') {
                $title = ($reportType === 'patrol') ? 'Patrol Performance Report' : 'Night Patrol Operations';
                
                $baseQuery = DB::table('patrol_sessions')
                    ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                    ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                    ->where('patrol_sessions.company_id', $companyId);

                if ($reportType === 'night_patrol') {
                    $baseQuery->where(function ($q) {
                        $q->whereTime('patrol_sessions.started_at', '>=', '18:00:00')
                          ->orWhereTime('patrol_sessions.started_at', '<', '06:00:00');
                    });
                }

                $this->applyCanonicalFilters($baseQuery, 'patrol_sessions.started_at', 'patrol_sessions.site_id', 'patrol_sessions.user_id');
                
                $data = $baseQuery->select(
                    'patrol_sessions.id',
                    'users.name as guard_name',
                    'users.id as guard_id',
                    'site_details.name as site_name',
                    'patrol_sessions.session as mode',
                    'patrol_sessions.started_at',
                    'patrol_sessions.ended_at',
                    'patrol_sessions.distance',
                    DB::raw('TIMESTAMPDIFF(MINUTE, patrol_sessions.started_at, patrol_sessions.ended_at) as duration_mins')
                )->orderByDesc('patrol_sessions.started_at')->limit(500)->get()->map(function($row) {
                    $distKm = ($row->distance ?? 0) / 1000;
                    $hours = ($row->duration_mins ?? 0) / 60;
                    $row->distance_km = round($distKm, 2);
                    $row->duration_formatted = $row->duration_mins ? floor($row->duration_mins/60).'h '.($row->duration_mins%60).'m' : 'Ongoing';
                    $row->avg_speed = ($hours > 0) ? round($distKm / $hours, 2) : 0;
                    return $row;
                });

                $summary = $data->groupBy('guard_id')->map(function($logs, $id) {
                    return (object)[
                        'guard_name' => $logs->first()->guard_name,
                        'total_sessions' => $logs->count(),
                        'total_dist' => round($logs->sum('distance_km'), 2),
                        'avg_speed' => round($logs->avg('avg_speed'), 2),
                        'total_time' => round($logs->sum('duration_mins') / 60, 1)
                    ];
                })->sortByDesc('total_dist');

                $kpis = [
                    'Total Distance' => $data->sum('distance_km') . ' km',
                    'Total Hours' => round($data->sum('duration_mins') / 60, 1),
                    'Avg Speed' => round($data->avg('avg_speed'), 2) . ' km/h'
                ];

            } elseif ($reportType === 'incident') {
                $title = 'Security & Wildlife Incident Analysis';
                $baseQuery = DB::table('patrol_logs')
                    ->join('patrol_sessions', 'patrol_sessions.id', '=', 'patrol_logs.patrol_session_id')
                    ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
                    ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                    ->leftJoin('client_details', 'site_details.client_id', '=', 'client_details.id')
                    ->where('patrol_sessions.company_id', $companyId);

                $this->applyCanonicalFilters($baseQuery, 'patrol_logs.created_at', 'patrol_sessions.site_id', 'patrol_sessions.user_id');

                $data = $baseQuery->select(
                    'patrol_logs.id',
                    'patrol_logs.type',
                    'patrol_logs.notes',
                    'patrol_logs.created_at',
                    'users.name as guard_name',
                    'site_details.name as site_name',
                    'client_details.name as range_name'
                )->orderByDesc('patrol_logs.created_at')->limit(500)->get();

                $summary = $data; // Show detailed list instead of grouped summary

                $kpis = [
                    'Total Incidents' => $data->count(),
                    'Most Frequent' => $summary->first()->type ?? 'N/A',
                    'Affected Sites' => $data->pluck('site_name')->unique()->count()
                ];
            }
        }

        if ($export === 'pdf' && $data) {
            $user = session('user');
            $companyId = $user->company_id ?? 56;
            
            // Try to get company from session first, then database
            $company = session('company');
            $companyDetails = [
                'name' => $company->name ?? 'AI Patrolling System',
                'address' => $company->address ?? 'Forest Department',
                'contact' => $company->phone ?? $company->contact ?? '',
                'email' => $company->email ?? ''
            ];
            
            // If not in session, try database (with error handling)
            if (empty($companyDetails['name']) || $companyDetails['name'] == 'AI Patrolling System') {
                try {
                    if (DB::getSchemaBuilder()->hasTable('companies')) {
                        $company = DB::table('companies')->where('id', $companyId)->first();
                        if ($company) {
                            $companyDetails = [
                                'name' => $company->name ?? 'AI Patrolling System',
                                'address' => $company->address ?? 'Forest Department',
                                'contact' => $company->phone ?? $company->contact ?? '',
                                'email' => $company->email ?? ''
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Table doesn't exist, use defaults
                    \Log::warning('Companies table not found, using defaults: ' . $e->getMessage());
                }
            }
            
            // Get range and beat names if selected
            $rangeName = 'All Ranges';
            if ($request->filled('range')) {
                try {
                    $rangeName = DB::table('client_details')->where('id', $request->range)->value('name') ?? 'Selected Range';
                } catch (\Exception $e) {
                    $rangeName = 'Selected Range';
                }
            }
            
            $beatName = 'All Beats';
            if ($request->filled('beat')) {
                try {
                    $beatName = DB::table('site_details')->where('id', $request->beat)->value('name') ?? 'Selected Beat';
                } catch (\Exception $e) {
                    $beatName = 'Selected Beat';
                }
            }
            
            // Build View Map URL for single-day attendance reports
            $mapViewUrl = null;
            if ($reportType === 'attendance' && $daysInRange === 1) {
                $mapParams = [
                    'report_type' => 'attendance',
                    'start_date' => $request->start_date ?? $startDate->toDateString(),
                    'end_date' => $request->end_date ?? $endDate->toDateString(),
                    'open_map' => '1'
                ];
                if ($request->filled('range')) $mapParams['range'] = $request->range;
                if ($request->filled('beat')) $mapParams['beat'] = $request->beat;
                if ($request->filled('user')) $mapParams['user'] = $request->user;
                $mapViewUrl = url('/reports/monthly') . '?' . http_build_query($mapParams);
            }

            $pdf = Pdf::loadView('reports.pdf_export', [
                'data' => $data,
                'summary' => $summary,
                'title' => $title,
                'type' => $reportType,
                'company' => $companyDetails,
                'kpis' => $kpis,
                'mapViewUrl' => $mapViewUrl,
                'filters' => [
                    'Date Range' => ($request->start_date ?? $startDate->toDateString()) . ' to ' . ($request->end_date ?? $endDate->toDateString()),
                    'Range' => $rangeName,
                    'Beat' => $beatName,
                    'Generated On' => now()->format('d M Y h:i A'),
                    'Total Records' => count($data)
                ]
            ]);
            return $pdf->download(\Illuminate\Support\Str::slug($title) . '_' . now()->format('Ymd') . '.pdf');
        }

        return view('reports.monthly', compact('data', 'reportType', 'title', 'summary', 'ranges', 'beats', 'users', 'kpis', 'daysInRange'))
            ->with('manualFilterApply', true);
    }

    /* ================= CAMERA TRACKING ================= */
    public function cameraTracking(Request $request)
    {
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
        $base = DB::table('patrol_sessions')
            ->join('users', 'users.id', '=', 'patrol_sessions.user_id')
            ->where('users.role_id', '!=', 1); // Never include SuperAdmins in reports

        if (!empty($accessibleUserIds)) {
            $base->whereIn('patrol_sessions.user_id', $accessibleUserIds);
        } else {
            $base->whereRaw('1 = 0');
        }

        $stats = [
            'total_guards' => (clone $base)->select(DB::raw('COUNT(DISTINCT users.id) as c'))->value('c') ?? 0,
            'active_patrols' => (clone $base)->whereNull('ended_at')->count(),
            'completed_patrols' => (clone $base)->whereNotNull('ended_at')->count(),
            'total_distance_km' => round(
                (clone $base)->whereNotNull('ended_at')->sum('distance') / 1000,
                2
            )
        ];

        $guards = (clone $base)
            ->groupBy('users.id', 'users.name', 'users.role_id')
            ->select(
                'users.name',
                DB::raw("
                    CASE 
                        WHEN users.role_id = 2 THEN 'Circle Incharge'
                        ELSE 'Forest Guard'
                    END as designation
                ")
            )
            ->orderBy('users.name')
            ->get();

        return view('reports.camera-tracking', compact('stats', 'guards'));
    }

}
