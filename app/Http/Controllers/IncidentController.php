<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Traits\FilterDataTrait;
use App\Services\RoleBasedFilterService; // ✅ Add role-based filtering

class IncidentController extends Controller
{
    use FilterDataTrait;

    /* ================= INCIDENT SUMMARY (Consolidated) ================= */
    public function summary(Request $request)
    {
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        // ✅ Get accessible users based on role
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        // Base Query - Using forest_reports as primary source
        $base = DB::table('forest_reports')
            ->leftJoin('patrol_sessions', 'patrol_sessions.id', '=', 'forest_reports.patrol_id')
            ->leftJoin('users', 'users.id', '=', 'forest_reports.user_id')
            ->where('forest_reports.company_id', $companyId)
            ->whereIn('forest_reports.user_id', $accessibleUserIds);

        $this->applyCanonicalFilters($base, 'forest_reports.created_at', null, 'forest_reports.user_id');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $base->whereBetween('forest_reports.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        /* --- GLOBAL GUARD SEARCH --- */
        if ($request->filled('guard_search')) {
            $base->where('users.name', 'like', '%' . $request->guard_search . '%');
        }

        /* 1. KPIs - Derived from forest_reports */
        $kpis = [
            'total_incidents' => (clone $base)->count(),
            'animal_sightings' => (clone $base)->where('forest_reports.report_type', 'sighting')->count(),
            'water_sources' => (clone $base)->where('forest_reports.report_type', 'water_status')->count(),
            'fire' => (clone $base)->where('forest_reports.report_type', 'fire')->count(),
            'birds' => (clone $base)->whereIn('forest_reports.report_type', ['bird_sighting', 'bird'])->count(),
            'insects_butterflies' => (clone $base)->whereIn('forest_reports.report_type', ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'])->count(),
        ];

        /* 2. Charts Data */
        $typeCase = '
            CASE 
                WHEN forest_reports.report_type = "sighting" THEN "Animal Sighting"
                WHEN forest_reports.report_type = "water_status" THEN "Water Source"
                WHEN forest_reports.report_type = "fire" THEN "Fire"
                WHEN forest_reports.report_type IN ("bird_sighting", "bird") THEN "Birds"
                WHEN forest_reports.report_type IN ("butterfly_sighting", "insect_sighting", "insect_butterfly") THEN "Insect/Butterfly"
            END';

        $typeStats = (clone $base)
            ->whereIn('forest_reports.report_type', [
                'sighting', 'water_status', 'fire', 'bird_sighting', 'bird', 
                'butterfly_sighting', 'insect_sighting', 'insect_butterfly'
            ])
            ->selectRaw($typeCase . ' as type, COUNT(*) as total')
            ->groupBy(DB::raw($typeCase))
            ->get();

        $sessionStats = (clone $base)
            ->selectRaw('patrol_sessions.session, COUNT(*) as total')
            ->groupBy('patrol_sessions.session')
            ->get();

        /* 5. Detailed Incidents List */
        $incidentsQuery = (clone $base)
            ->selectRaw('
                forest_reports.id,
                forest_reports.report_type as type,
                forest_reports.report_data as payload,
                forest_reports.created_at,
                users.id as guard_id,
                users.name as guard,
                forest_reports.range as range_name,
                forest_reports.beat as beat_name,
                patrol_sessions.session,
                CASE
                    WHEN forest_reports.report_type = "mortality" THEN 5
                    WHEN forest_reports.report_type IN ("felling", "encroachment", "poaching") THEN 4
                    WHEN forest_reports.report_type = "sighting" THEN 3
                    WHEN forest_reports.report_type = "water_status" THEN 2
                    ELSE 1
                END as severity
            ')
            ->orderByDesc('forest_reports.created_at')
            ->orderByDesc('forest_reports.id');

        if ($request->ajax()) {
            $incidents = $incidentsQuery->paginate(25);
            return view('incidents.partials.incident-table', compact('incidents'))->render();
        }

        $incidents = $incidentsQuery->paginate(25)->withQueryString();

        return view('incidents.summary', array_merge(
            $this->filterData(),
            compact('kpis', 'typeStats', 'sessionStats', 'incidents')
        ));
    }

    /* ================= INCIDENT NEARBY (for map clicks) ================= */
    public function nearby(Request $request)
    {
        if (!$request->filled('lat') || !$request->filled('lng')) {
            return response()->json(['error' => 'Location required'], 400);
        }

        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;

        // ✅ Get accessible users based on role
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->get('radius', 5); // km radius, default 5km

        $base = DB::table('patrol_logs')
            ->join('patrol_sessions', 'patrol_sessions.id', '=', 'patrol_logs.patrol_session_id')
            ->leftJoin('users', 'users.id', '=', 'patrol_sessions.user_id')
            ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
            ->leftJoin('client_details', 'client_details.id', '=', 'site_details.client_id')
            ->where(function ($q) {
                $q->whereIn('patrol_logs.type', [
                    'animal_sighting',
                    'water_source',
                    'human_impact',
                    'animal_mortality',
                    'fire',
                    'Fire'
                ])
                ->orWhere('patrol_logs.type', 'like', 'bird%')
                ->orWhere('patrol_logs.type', 'like', 'butterfly%')
                ->orWhere('patrol_logs.type', 'like', 'insect%')
                ->orWhere('patrol_logs.type', 'like', 'fire%');
            })
            ->where('patrol_sessions.company_id', $companyId)
            ->whereIn('patrol_sessions.user_id', $accessibleUserIds) // ✅ Role-based filter
            ->whereNotNull('patrol_logs.lat')
            ->whereNotNull('patrol_logs.lng');

        $this->applyCanonicalFilters($base, 'patrol_logs.created_at', 'patrol_sessions.site_id', 'patrol_sessions.user_id');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $base->whereBetween('patrol_logs.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Calculate distance using Haversine formula
        $incidents = $base
            ->selectRaw("
                patrol_logs.id,
                patrol_logs.type,
                patrol_logs.payload,
                patrol_logs.notes,
                patrol_logs.lat,
                patrol_logs.lng,
                patrol_logs.created_at,
                users.name as guard,
                users.contact as guard_contact,
                site_details.client_id as range_id,
                client_details.name as range_name,
                patrol_sessions.site_id as beat_id,
                site_details.name as beat_name,
                site_details.name as compartment,
                patrol_sessions.session,
                patrol_sessions.type as patrol_type,
                (6371 * acos(cos(radians(?)) 
                    * cos(radians(patrol_logs.lat)) 
                    * cos(radians(patrol_logs.lng) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(patrol_logs.lat)))) AS distance,
                CASE
                    WHEN patrol_logs.type = 'animal_mortality' THEN 5
                    WHEN patrol_logs.type = 'human_impact' THEN 4
                    WHEN patrol_logs.type = 'animal_sighting' THEN 3
                    WHEN patrol_logs.type = 'water_source' THEN 2
                    ELSE 1
                END as severity
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->orderByDesc('severity')
            ->orderByDesc('patrol_logs.created_at')
            ->limit(20)
            ->get();

        return view('incidents.nearby', compact('incidents', 'lat', 'lng', 'radius'));
    }

    /* ================= GET INCIDENT DETAILS (AJAX) ================= */
    public function getIncidentDetails($id, $seenIds = [])
    {
        // Prevent infinite recursion loops
        if (in_array($id, $seenIds)) {
             return response()->json(['error' => 'Circular reference detected in incident records.'], 500);
        }
        $seenIds[] = $id;

        // 1. Try finding in forest_reports first (The new primary source)
        $incident = DB::table('forest_reports')
            ->leftJoin('patrol_sessions', 'patrol_sessions.id', '=', 'forest_reports.patrol_id')
            ->leftJoin('users', 'users.id', '=', 'forest_reports.user_id')
            ->where('forest_reports.id', $id)
            ->select(
                'forest_reports.id',
                'forest_reports.report_type as type',
                'forest_reports.report_data',
                'forest_reports.photo',
                'forest_reports.latitude as lat',
                'forest_reports.longitude as lng',
                'forest_reports.created_at',
                'forest_reports.beat as beat_name',
                'forest_reports.range as range_name',
                'forest_reports.status',
                'users.name as guard_name',
                'users.contact as guard_contact',
                'patrol_sessions.session'
            )
            ->first();

        if ($incident) {
            // Map string status to numeric flag for frontend compatibility
            $statusMapping = [
                'Pending' => 0,
                'Resolved' => 1,
                'Ignored' => 2,
                'Escalated' => 3,
                'Critical' => 5,
                'Reverted' => 6
            ];
            $incident->statusFlag = $statusMapping[$incident->status] ?? 0;

            // Parse report_data (JSON) into notes/remark if available
            $payload = json_decode($incident->report_data, true);
            if (!is_array($payload)) { $payload = []; }
            
            $incident->notes = $payload['remark'] ?? $payload['notes'] ?? ($payload['observation'] ?? 'No specific notes recorded.');
            $incident->priority = $payload['priority'] ?? 'Medium';
            
            // Fallback for photo if forest_reports.photo is null but available in payload
            if (!$incident->photo) {
                $incident->photo = $payload['photo_evidence'] ?? ($payload['photo'] ?? null);
            }
            
            return response()->json([
                'incident' => $incident,
                'comments' => [] // forest_reports might not have comments yet
            ]);
        }

        // 2. Fallback to patrol_logs (for legacy data)
        $incident = DB::table('patrol_logs')
            ->leftJoin('patrol_sessions', 'patrol_sessions.id', '=', 'patrol_logs.patrol_session_id')
            ->leftJoin('users', 'users.id', '=', 'patrol_sessions.user_id')
            ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
            ->leftJoin('client_details', 'client_details.id', '=', 'site_details.client_id')
            ->leftJoin('incidence_details', 'incidence_details.inc_id', '=', 'patrol_logs.id')
            ->where('patrol_logs.id', $id)
            ->select(
                'patrol_logs.*',
                'users.name as guard_name',
                'users.contact as guard_contact',
                'site_details.name as beat_name',
                'client_details.name as range_name',
                'patrol_sessions.session',
                'incidence_details.id as real_incidence_id', // Link back to analytics record
                'incidence_details.photo',
                'incidence_details.remark as details_remark',
                'incidence_details.checkList',
                'incidence_details.priority',
                'incidence_details.statusFlag'
            )
            ->first();

        $incidenceIdForComments = $id;

        // 2. If not found, check if the ID refers to incidence_details table itself
        if (!$incident) {
            $altIncident = DB::table('incidence_details')
                ->leftJoin('users', 'users.id', '=', 'incidence_details.guard_id')
                ->leftJoin('site_details', 'site_details.id', '=', 'incidence_details.site_id')
                ->leftJoin('client_details', 'client_details.id', '=', 'site_details.client_id')
                ->where('incidence_details.id', $id)
                ->select(
                    'incidence_details.id',
                    'incidence_details.id as real_incidence_id',
                    'incidence_details.type',
                    'incidence_details.dateFormat as created_at',
                    'incidence_details.remark as notes',
                    'incidence_details.photo',
                    'incidence_details.priority',
                    'incidence_details.statusFlag',
                    'incidence_details.inc_id',
                    'users.name as guard_name',
                    'users.contact as guard_contact',
                    'site_details.name as beat_name',
                    'client_details.name as range_name'
                )
                ->first();

            if ($altIncident) {
                $incidenceIdForComments = $altIncident->id;

                // If this incidence entry has a link to patrol_logs, try to follow it once
                if (!empty($altIncident->inc_id) && $altIncident->inc_id != $id) {
                    return $this->getIncidentDetails($altIncident->inc_id, $seenIds);
                }
                $incident = $altIncident;
            }
        } else {
            // Found in patrol_logs, use the joined incidence_details ID for comments if available
            if (!empty($incident->real_incidence_id)) {
                $incidenceIdForComments = $incident->real_incidence_id;
            }
        }

        if (!$incident) {
            return response()->json(['error' => 'Incident record not found. It may have been archived or removed.'], 404);
        }

        // Standardize fields that might be missing in fallback
        $incident->type = $incident->type ?? 'Incident';
        $incident->created_at = $incident->created_at ?? now()->toDateTimeString();

        $comments = DB::table('incidence_comment')
            ->where('incidence_id', $incidenceIdForComments)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'incident' => $incident,
            'comments' => $comments
        ]);
    }

    /* ================= GET INCIDENTS LIST WITH FILTERS (AJAX) ================= */
    public function getIncidentsByType(Request $request, $type = 'all')
    {
        $type = $type ?: 'all';
        $user = session('user');
        $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        // Determine primary data source based on request
        $source = $request->get('source', 'incidence_details');

        // Use incidence_details as primary source ONLY if explicitly requested or for status filters
        // Summary page charts use patrol_logs, so we need to match that
        // Use forest_reports as primary source
        if ($source === 'forest_reports' || $source === 'patrol_logs') {
             $query = DB::table('forest_reports')
                ->leftJoin('patrol_sessions', 'patrol_sessions.id', '=', 'forest_reports.patrol_id')
                ->leftJoin('users', 'users.id', '=', 'forest_reports.user_id')
                ->where('forest_reports.company_id', $companyId)
                ->whereIn('forest_reports.user_id', $accessibleUserIds);

            // Handle status filtering for forest_reports
            if ($request->has('fetchByStatus')) {
                $query->where('forest_reports.status', 'like', $type . '%');
            } else if ($type !== 'total_incidents' && $type !== 'all' && $type !== 'undefined') {
                // Map frontend labels to DB report_type
                $mappedType = match(strtolower($type)) {
                    'animal sightings' => 'sighting',
                    'animal sighting' => 'sighting',
                    'human impact' => ['felling', 'encroachment', 'poaching', 'animal_damage'],
                    'water sources' => 'water_status',
                    'water source' => 'water_status',
                    'mortality' => 'mortality',
                    'animal mortality' => 'mortality',
                    'fire' => 'fire',
                    'birds' => ['bird_sighting', 'bird'],
                    'bird' => ['bird_sighting', 'bird'],
                    'butterflies' => ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'],
                    'butterfly' => ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'],
                    'insects' => ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'],
                    'insect' => ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'],
                    'insect/butterfly' => ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'],
                    'insect_butterfly' => ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'],
                    'insects_butterflies' => ['butterfly_sighting', 'insect_sighting', 'insect_butterfly'],
                    default => $type
                };

                if (is_array($mappedType)) {
                    $query->whereIn('forest_reports.report_type', $mappedType);
                } else {
                    $query->where('forest_reports.report_type', 'like', $mappedType . '%');
                }
            }

            $this->applyCanonicalFilters($query, 'forest_reports.created_at', null, 'forest_reports.user_id');

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('forest_reports.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }
            
            $incidents = $query->selectRaw('
                forest_reports.id,
                forest_reports.report_type as type,
                forest_reports.created_at,
                users.name as guard,
                forest_reports.beat as beat_name,
                forest_reports.range as range_name,
                CASE 
                    WHEN forest_reports.status = "Resolved" THEN 1
                    WHEN forest_reports.status = "Ignored" THEN 2
                    WHEN forest_reports.status = "Escalated" THEN 3
                    WHEN forest_reports.status = "Critical" THEN 5
                    WHEN forest_reports.status = "Reverted" THEN 6
                    ELSE 0 
                END as statusFlag
            ')
            ->orderByDesc('forest_reports.created_at')
            ->limit(100)
            ->get();

            return response()->json(['type' => $type, 'incidents' => $incidents]);
        }

        $query = DB::table('incidence_details')
            ->leftJoin('users', 'users.id', '=', 'incidence_details.guard_id')
            ->leftJoin('site_details', 'site_details.id', '=', 'incidence_details.site_id')
            ->leftJoin('client_details', 'client_details.id', '=', 'site_details.client_id')
            ->where('incidence_details.company_id', $companyId);

        // Role-based scope
        if (!empty($accessibleUserIds)) {
            $query->whereIn('incidence_details.guard_id', $accessibleUserIds);
        }

        // 1. Status Filter (from doughnut chart)
        if ($request->has('fetchByStatus')) {
            $statusMap = [
                'Resolved' => 1,
                'Pending' => 0,
                'Pending (Supervisor)' => 0,
                'Supervisor Pending' => 0,
                'Pending (Admin)' => 4,
                'Admin Pending' => 4,
                'Escalated (Admin)' => 3,
                'Escalated (Client)' => 5, // Status 5 is mapped to Client in some systems
                'Ignored' => 2,
                'Reverted' => 6,
                'Escalated' => 3,
                'Critical' => 5
            ];
            $statusFlag = $statusMap[$type] ?? null;
            if ($statusFlag !== null) {
                $query->where('incidence_details.statusFlag', $statusFlag);
            } else if (is_numeric($type)) {
                $query->where('incidence_details.statusFlag', $type);
            }
        } 
        // 2. Type Filter (from bar chart)
        else if ($type !== 'total_incidents' && $type !== 'all' && $type !== 'undefined') {
            // Flexible matching for types: handle spaces, casing, and common variations
            $cleanType = strtolower(trim($type));
            $query->where(function($q) use ($cleanType, $type) {
                $q->where('incidence_details.type', 'like', $type)
                  ->orWhere('incidence_details.type', 'like', $cleanType)
                  ->orWhere('incidence_details.type', 'like', str_replace(' ', '_', $cleanType))
                  ->orWhere('incidence_details.type', 'like', rtrim($cleanType, 's')); // Handle plurals
            });
        } else {
            // For 'all' or 'total_incidents', exclude 'Other'/empty types to match KPIs
            $query->whereNotNull('incidence_details.type')
                  ->whereNotIn('incidence_details.type', ['Other', 'other', '']);
        }

        // Optional Site Profile Filter (from Executive Dashboard table)
        if ($request->filled('site_name')) {
            $query->where('site_details.name', 'like', $request->site_name . '%');
        }

        // Apply shared filters
        $this->applyCanonicalFilters($query, 'incidence_details.dateFormat', 'incidence_details.site_id', 'incidence_details.guard_id');
        
        // Exact date matching
        if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('incidence_details.dateFormat', [
                    $request->start_date,
                    $request->end_date
                ]);
        }

        // Optional User Filter
        if ($request->filled('user')) {
            $query->where('incidence_details.guard_id', $request->user);
        }

        $incidents = $query->selectRaw('
                COALESCE(incidence_details.inc_id, incidence_details.id) as id,
                incidence_details.type,
                incidence_details.dateFormat as created_at,
                users.name as guard,
                site_details.name as beat_name,
                client_details.name as range_name
            ')
            ->orderByDesc('incidence_details.dateFormat')
            ->orderByDesc('incidence_details.id')
            ->limit(100)
            ->get();

        // Fallback to patrol_logs if nothing found (to handle older data if needed)
        if ($incidents->isEmpty() && !($request->has('fetchByStatus'))) {
             $logQuery = DB::table('patrol_logs')
                ->join('patrol_sessions', 'patrol_sessions.id', '=', 'patrol_logs.patrol_session_id')
                ->leftJoin('users', 'users.id', '=', 'patrol_sessions.user_id')
                ->leftJoin('site_details', 'site_details.id', '=', 'patrol_sessions.site_id')
                ->where('patrol_sessions.company_id', $companyId)
                ->whereIn('patrol_sessions.user_id', $accessibleUserIds);

            if ($type !== 'total_incidents' && $type !== 'all' && $type !== 'undefined') {
                $cleanType = strtolower(trim($type));
                $logQuery->where(function($q) use ($cleanType, $type) {
                    $q->where('patrol_logs.type', 'like', $type)
                      ->orWhere('patrol_logs.type', 'like', $cleanType)
                      ->orWhere('patrol_logs.type', 'like', str_replace(' ', '_', $cleanType))
                      ->orWhere('patrol_logs.type', 'like', rtrim($cleanType, 's'));
                });
            } else {
                // For 'all' or 'total_incidents', show only the standard categories to match KPIs
                $logQuery->where(function($q) {
                    $q->whereIn('patrol_logs.type', [
                        'animal_sighting',
                        'water_source',
                        'human_impact',
                        'animal_mortality',
                        'fire',
                        'Fire'
                    ])
                    ->orWhere('patrol_logs.type', 'like', 'bird%')
                    ->orWhere('patrol_logs.type', 'like', 'butterfly%')
                    ->orWhere('patrol_logs.type', 'like', 'insect%')
                    ->orWhere('patrol_logs.type', 'like', 'fire%');
                });
            }

            $this->applyCanonicalFilters($logQuery, 'patrol_logs.created_at', 'patrol_sessions.site_id', 'patrol_sessions.user_id');

            $incidents = $logQuery->selectRaw('
                patrol_logs.id,
                patrol_logs.type,
                patrol_logs.created_at,
                users.name as guard,
                site_details.name as beat_name
            ')
            ->limit(100)
            ->get();
        }

        return response()->json([
            'type' => $type,
            'incidents' => $incidents
        ]);
    }
}