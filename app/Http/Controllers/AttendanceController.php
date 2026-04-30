<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FilterDataTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\RoleBasedFilterService; // ✅ Add role-based filtering

class AttendanceController extends Controller
{
    use FilterDataTrait;

    protected $analyticsService;

    public function __construct(\App\Services\AnalyticsDataService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function summary(Request $request)
    {
        try {
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
            
            // ✅ Get accessible users based on role
            $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
            
            // Prioritize explicit start/end dates from filters
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
            } else {
                // Default to Last 30 Days to match Global Filters UI
                $startDate = now()->subDays(30)->startOfDay();
                $endDate = now()->endOfDay();
            }

            // Generate Dates Array for Column Headers
            $datePeriod = CarbonPeriod::create($startDate, $endDate);
            $dates = [];
            foreach ($datePeriod as $d) {
                $dates[] = $d->copy();
            }
            $totalDaysInRange = count($dates);

            /* ================= USERS + ASSIGN ================= */
            $siteIds = $this->resolveSiteIds();
            $userId = request('user');

            $usersQuery = DB::table('users')
                ->where('users.company_id', $companyId)
                ->where('users.isActive', 1)
                ->whereIn('users.id', $accessibleUserIds) // ✅ Role-based filter
                ->leftJoin('site_assign', 'users.id', '=', 'site_assign.user_id');

            if ($request->filled('range')) {
                $usersQuery->where('site_assign.client_id', $request->range);
            }

            if (!empty($siteIds)) {
                $usersQuery->where(function ($q) use ($siteIds) {
                    foreach ($siteIds as $sid) {
                        $q->orWhereRaw('FIND_IN_SET(?, site_assign.site_id)', [$sid]);
                    }
                });
            }

            if ($userId) {
                $usersQuery->where('users.id', $userId);
            }

            // Apply guard_search filter
            if ($request->filled('guard_search')) {
                $guardUserId = $this->resolveGuardUserIdFromSearch();
                if ($guardUserId) {
                    $usersQuery->where('users.id', $guardUserId);
                } else {
                    // If guard not found, return no results
                    $usersQuery->whereRaw('1 = 0');
                }
            }

            $users = $usersQuery
                ->select(
                    'users.id',
                    'users.name',
                    'users.profile_pic',
                    'site_assign.client_name as range',
                    'site_assign.site_id',
                    'site_assign.site_name'
                )
                ->groupBy('users.id', 'users.name', 'users.profile_pic', 'site_assign.client_name', 'site_assign.site_id', 'site_assign.site_name')
                ->get()
                ->keyBy('id');

            /* ================= BEAT MAP ================= */
            $userBeatMap = [];
            foreach ($users as $u) {
                $beatIds = array_filter(explode(',', $u->site_id));
                $userBeatMap[$u->id] = (int) ($request->beat ?? $beatIds[0] ?? null);
            }

            /* ================= COMPARTMENT MAP ================= */
            $compartmentMap = DB::table('site_geofences')
                ->where('company_id', $companyId)
                ->whereIn('site_id', array_values($userBeatMap))
                ->orderBy('id')
                ->get()
                ->groupBy('site_id')
                ->map(fn($rows) => $rows->first()->name);

            /* ================= ATTENDANCE for Grid ================= */
            // Filter attendance by selected users only
            $userIds = $users->keys()->toArray();
            
            $attendance = DB::table('attendance')
                ->where('company_id', $companyId)
                ->whereBetween('dateFormat', [
                    $startDate->format('Y-m-d'), 
                    $endDate->format('Y-m-d')
                ]);
            
            // Only get attendance for filtered users
            if (!empty($userIds)) {
                $attendance->whereIn('user_id', $userIds);
            } else {
                // If no users match filters, return empty result
                $attendance->whereRaw('1 = 0');
            }
            
            $attendance = $attendance
                ->selectRaw('user_id, dateFormat as date_str')
                ->distinct()
                ->get()
                ->groupBy(fn($r) => $r->user_id . '_' . substr($r->date_str, 0, 10));

            /* ================= GRID BUILD ================= */
            $grid = [];
            foreach ($users as $u) {
                $presentCount = 0;
                $dayData = [];

                foreach ($dates as $dt) {
                    $dateStr = $dt->toDateString();
                    $key = $u->id . '_' . $dateStr;
                    $present = isset($attendance[$key]);
                    if ($present) $presentCount++;
                    $dayData[$dateStr] = compact('present');
                }

                $beatId = $userBeatMap[$u->id];

                $grid[$u->id]['user'] = $u;
                $grid[$u->id]['meta'] = [
                    'range' => $u->range ?? 'NA',
                    'beat' => $u->site_name ?? 'NA',
                    'compartment' => $compartmentMap[$beatId] ?? 'NA',
                ];
                $grid[$u->id]['days'] = $dayData;
                $grid[$u->id]['summary'] = [
                    'present' => $presentCount,
                    'total' => $totalDaysInRange,
                ];
            }

            /* ================= KPIs (Summary Blade Style) ================= */
            $totalGuards = count($users);
            $totalPresentManDays = collect($grid)->sum(fn($g) => $g['summary']['present']);
            $totalPossibleManDays = $totalGuards * $totalDaysInRange;
            $totalAbsentManDays = $totalPossibleManDays - $totalPresentManDays;
            $presentPct = $totalPossibleManDays > 0 ? round(($totalPresentManDays / $totalPossibleManDays) * 100, 1) : 0;
            
            /* ================= DAILY TREND ================= */
            $dailyTrend = collect();
            foreach ($dates as $dt) {
                $dailyPresentList = [];
                $dailyAbsentList = [];
                $dStr = $dt->toDateString();
                foreach($grid as $uid => $data) {
                    $uObj = ['id' => $uid, 'name' => \App\Helpers\FormatHelper::formatName($data['user']->name)];
                    if(!empty($data['days'][$dStr]['present'])) $dailyPresentList[] = $uObj;
                    else $dailyAbsentList[] = $uObj;
                }
                $dailyTrend->push([
                    'date' => $dt->format('d M'),
                    'full_date' => $dStr,
                    'present' => count($dailyPresentList),
                    'absent' => count($dailyAbsentList),
                    'present_list' => $dailyPresentList,
                    'absent_list' => $dailyAbsentList
                ]);
            }

            /* ================= TOP 10 DEFAULTERS ================= */
            $defaulters = collect($grid)
                ->map(function($g) {
                     return [
                         'user_id' => $g['user']->id,
                         'name' => $g['user']->name,
                         'days_present' => $g['summary']['present'],
                         'days_absent' => $g['summary']['total'] - $g['summary']['present']
                     ];
                })
                ->unique('user_id')
                ->sortByDesc('days_absent')
                ->take(10)
                ->values();

            $filterData = $this->filterData();
            
        } catch (\Exception $e) {
            $grid = []; $dates = []; $dailyTrend = collect([]); $defaulters = collect([]); $presentPct = 0;
            $totalPresentManDays = 0; $totalAbsentManDays = 0; $totalGuards = 0;
            $startDate = now(); $endDate = now(); 
            $filterData = ['ranges' => [], 'beats' => [], 'users' => []];
        }

        return view('attendance.summary', array_merge(
            $filterData,
            compact('grid','dates','totalGuards','presentPct','totalPresentManDays','totalAbsentManDays','dailyTrend','defaulters','startDate','endDate')
        ));
    }

    public function requests(Request $request)
    {
        try {
            $user = session('user');
            $companyId = is_object($user) ? ($user->company_id ?? 56) : ($user['company_id'] ?? 56);

            $query = DB::table('attendance')
                ->where('attendance.company_id', $companyId)
                ->orderBy('attendance.id', 'desc');

            $this->applyCanonicalFilters($query, 'dateFormat');

            $limit = $request->get('limit', 100); // Default to 100
            $requests = $query->paginate($limit)->withQueryString();
            $filterData = $this->filterData();

            return view('attendance.requests', array_merge(
                $filterData,
                compact('requests', 'limit')
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Error loading attendance requests: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        try {
            DB::table('attendance')->where('id', $id)->update(['status' => 1]);
            return response()->json(['status' => 'success', 'message' => 'Attendance approved successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
