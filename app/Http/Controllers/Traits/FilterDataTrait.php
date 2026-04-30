<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use App\Services\RoleBasedFilterService;

trait FilterDataTrait
{
    /**
     * Resolve Range → Beat → User → SITE IDs
     * (Kept for compatibility, though site filtering might not depend on user selection directly
     * unless we want to show sites assigned to that user.
     * For now, we filter DATA by user_id separately.)
     */
    protected function resolveSiteIds(): array
    {
        $q = DB::table('site_details')->select('site_details.id');

        // 🔥 Role-based: only show sites the current user is authorized to see
        $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();
        if (!empty($accessibleSiteIds)) {
            $q->whereIn('site_details.id', $accessibleSiteIds);
        } else {
            // No accessible sites - return empty
            return [];
        }

        // Range → client_details.id
        if (request()->filled('range') && strtolower(request('range')) !== 'all') {
            $q->where('site_details.client_id', request('range'));
        }

        // Beat → site_details.id
        if (request()->filled('beat') && strtolower(request('beat')) !== 'all') {
            $q->where('site_details.id', request('beat'));
        }

        return $q->pluck('id')->toArray();
    }

    /**
     * Apply filters safely to ANY query that has site_id and user_id
     */
    protected function applyCanonicalFilters(
        $query,
        string $dateColumn = null,
        string $siteColumn = 'site_id',
        string $userColumn = 'user_id',
        bool $skipDateFilter = false,
        bool $strictMode = false,
        bool $defaultTo30Days = true,
        $passedStartDate = null,
        $passedEndDate = null
    ) {
        // 1. Date Filter
        if ($dateColumn && !$skipDateFilter) {
            $startDate = $passedStartDate ?: request('start_date');
            $endDate = $passedEndDate ?: request('end_date');

            if (empty($startDate) && empty($endDate) && $defaultTo30Days) {
                $startDate = now()->subDays(30)->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
            }

            if (!empty($startDate)) {
                $query->whereDate($dateColumn, '>=', $startDate);
            }
            if (!empty($endDate)) {
                $query->whereDate($dateColumn, '<=', $endDate);
            }
        }

        // 2. Site filter (Range/Beat OR Role-based mapping)
        $siteIds = $this->resolveSiteIds();

        if (empty($siteIds)) {
            if ($strictMode) {
                $query->whereRaw('1 = 0');
            }
        } else {
            $query->whereIn($siteColumn, $siteIds);
        }

        // 3. User / Guard Filter
        // Priority 1: Direct user selection from dropdown
        if (request()->filled('user')) {
            $query->where($userColumn, request('user'));
        }
        // Priority 2: Guard Search string
        elseif (request()->filled('guard_search')) {
            $resolvedUserId = $this->resolveGuardUserIdFromSearch();
            if ($resolvedUserId) {
                $query->where($userColumn, $resolvedUserId);
            } else {
                // Search was entered but no guard found -> show nothing
                $query->whereRaw('1 = 0');
            }
        }

        // 4. Role-based: only data for accessible users (SuperAdmins/role_id 1 never appear in operational data)
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();
        if (!empty($accessibleUserIds)) {
            $query->whereIn($userColumn, $accessibleUserIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Data for global filter dropdowns
     * ✅ Now applies role-based filtering
     */
    public function filterData(): array
    {
        $user = session('user');

        // Fallback to company_id 56 for testing if session user not available
        $companyId = is_object($user) ? ($user->company_id ?? 56) : ($user['company_id'] ?? 56);
        // Get accessible IDs based on role
        $accessibleClientIds = RoleBasedFilterService::getAccessibleClientIds();
        $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        // 1. Ranges (Clients) - Apply role-based filtering
        $rangesQuery = DB::table('client_details')
            ->where('isActive', 1)
            ->orderBy('name');

        if (!empty($accessibleClientIds)) {
            $rangesQuery->whereIn('id', $accessibleClientIds);
        } else {
            // No accessible clients - return empty
            $rangesQuery->whereRaw('1 = 0');
        }

        $ranges = $rangesQuery->pluck('name', 'id');

        // 2. Beats (Sites) - Depend on Range AND apply role-based filtering
        // Load beats if range is selected OR if beat is already selected (to preserve after reload)
        $beats = collect();
        $rangeId = request('range');
        
        // If beat is selected but range is not, try to find the range from the beat
        if (!$rangeId && request()->filled('beat')) {
            $beatSite = DB::table('site_details')
                ->where('id', request('beat'))
                ->first();
            if ($beatSite) {
                $rangeId = $beatSite->client_id;
            }
        }
        
        if ($rangeId) {
            $beatsQuery = DB::table('site_details')
                ->where('client_id', $rangeId)
                ->orderBy('name');

            if (!empty($accessibleSiteIds)) {
                $beatsQuery->whereIn('id', $accessibleSiteIds);
            } else {
                $beatsQuery->whereRaw('1 = 0');
            }

            $beats = $beatsQuery->pluck('name', 'id');
        }

        // 3. Users - Depend on Beat OR Range AND apply role-based filtering
        // Load users if beat, range, or user is already selected (to preserve after reload)
        $users = collect();
        $beatId = request('beat');
        $rangeIdForUsers = request('range') ?: $rangeId; // Use the rangeId we resolved earlier
        
        // If user is selected but beat/range is not, try to find them from user's assignments
        if (!$beatId && !$rangeIdForUsers && request()->filled('user')) {
            $userAssignment = DB::table('site_assign')
                ->where('user_id', request('user'))
                ->first();
            if ($userAssignment) {
                // Try to get beat from site_id (it might be JSON array)
                $siteIds = json_decode($userAssignment->site_id, true);
                if (is_array($siteIds) && !empty($siteIds)) {
                    $beatId = $siteIds[0]; // Use first site as beat
                } else {
                    $beatId = $userAssignment->site_id;
                }
                $rangeIdForUsers = $userAssignment->client_id;
            }
        }

        if ($beatId) {
            // Users in this specific Beat (handle both JSON and comma-separated)
            $usersQuery = DB::table('site_assign')
                ->join('users', 'users.id', '=', 'site_assign.user_id')
                ->where(function ($q) use ($beatId) {
                    $q->whereRaw('JSON_CONTAINS(site_assign.site_id, ?)', [json_encode((string) $beatId)])
                      ->orWhereRaw('FIND_IN_SET(?, site_assign.site_id)', [$beatId])
                      ->orWhere('site_assign.site_id', $beatId);
                })
                ->where('users.isActive', 1)
                ->orderBy('users.name')
                ->distinct();

            if (!empty($accessibleUserIds)) {
                $usersQuery->whereIn('users.id', $accessibleUserIds);
            } else {
                $usersQuery->whereRaw('1 = 0');
            }

            $users = $usersQuery->pluck('users.name', 'users.id');

        } elseif ($rangeIdForUsers) {
            // Users in this Range (Client) - regardless of beat
            $usersQuery = DB::table('site_assign')
                ->join('users', 'users.id', '=', 'site_assign.user_id')
                ->where('site_assign.client_id', $rangeIdForUsers)
                ->where('users.isActive', 1)
                ->orderBy('users.name')
                ->distinct();

            if (!empty($accessibleUserIds)) {
                $usersQuery->whereIn('users.id', $accessibleUserIds);
            } else {
                $usersQuery->whereRaw('1 = 0');
            }

            $users = $usersQuery->pluck('users.name', 'users.id');
        }

        return compact('ranges', 'beats', 'users');
    }

    /**
     * Check if a filter parameter is valid (not empty and not "all")
     */
    protected function hasValidFilter(string $key): bool
    {
        $value = request($key);

        if (empty($value)) {
            return false;
        }

        // Check if value is "all" or "All" (case-insensitive)
        if (is_string($value) && strtolower($value) === 'all') {
            return false;
        }

        return true;
    }

    /**
     * Resolve guard user ID from search term (name or email)
     * ✅ Now applies role-based filtering
     */
    protected function resolveGuardUserIdFromSearch(): ?int
    {
        $searchTerm = request('guard_search');

        if (empty($searchTerm)) {
            return null;
        }

        $user = session('user');
        $companyId = is_object($user) ? ($user->company_id ?? 56) : ($user['company_id'] ?? 56);

        // Get accessible user IDs based on role
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        // Search by name or email (partial match) within accessible users
        $guard = DB::table('users')
            ->where('company_id', $companyId)
            ->where('isActive', 1)
            ->whereIn('id', $accessibleUserIds)
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
            })
            ->first();

        return $guard ? $guard->id : null;
    }

    /**
     * Get beats for a specific range
     * ✅ Now applies role-based filtering
     */
    public function getBeatsForRange(int $rangeId): array
    {
        $user = session('user');
        $companyId = is_object($user) ? ($user->company_id ?? 56) : ($user['company_id'] ?? 56);

        // Get accessible site IDs based on role
        $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();

        $query = DB::table('site_details')
            ->where('client_id', $rangeId)
            ->where('company_id', $companyId)
            ->orderBy('name');

        if (!empty($accessibleSiteIds)) {
            $query->whereIn('id', $accessibleSiteIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * Get users filtered by range and/or beat
     * ✅ Now applies role-based filtering
     */
    public function getUsersForFilters(?int $rangeId = null, ?int $beatId = null): array
    {
        $user = session('user');
        $companyId = is_object($user) ? ($user->company_id ?? 56) : ($user['company_id'] ?? 56);

        // Get accessible user IDs based on role
        $accessibleUserIds = RoleBasedFilterService::getAccessibleUserIds();

        $query = DB::table('users')
            ->where('users.company_id', $companyId)
            ->where('users.isActive', 1)
            ->where('users.role_id', '!=', 1); // Never show SuperAdmins in user/guard filters

        // Apply role-based filtering
        if (!empty($accessibleUserIds)) {
            $query->whereIn('users.id', $accessibleUserIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        // If beat is specified, filter by beat (handle both JSON and comma-separated)
        if ($beatId) {
            $query->join('site_assign', 'users.id', '=', 'site_assign.user_id')
                ->where(function ($q) use ($beatId) {
                    $q->whereRaw('JSON_CONTAINS(site_assign.site_id, ?)', [json_encode((string) $beatId)])
                      ->orWhereRaw('FIND_IN_SET(?, site_assign.site_id)', [$beatId])
                      ->orWhere('site_assign.site_id', $beatId);
                })
                ->distinct();
        }
        // If only range is specified, filter by range
        elseif ($rangeId) {
            $query->join('site_assign', 'users.id', '=', 'site_assign.user_id')
                ->where('site_assign.client_id', $rangeId)
                ->distinct();
        }

        return $query->orderBy('users.name')
            ->pluck('users.name', 'users.id')
            ->toArray();
    }
}