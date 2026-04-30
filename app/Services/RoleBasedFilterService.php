<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\SiteAssign;
use App\Models\SiteDetail;

class RoleBasedFilterService
{
    /**
     * Helper: Safely get user property (handles both object and array)
     */
    private static function getUserProperty($user, string $property)
    {
        if (is_object($user)) {
            return $user->$property ?? null;
        }
        if (is_array($user)) {
            return $user[$property] ?? null;
        }
        return null;
    }

    /**
     * Get accessible user IDs based on role
     * 
     * @return array
     */
    public static function getAccessibleUserIds(): array
    {
        $authUser = session('user');

        if (!$authUser) {
            $authUser = Auth::user();
        }

        if (!$authUser) {
            return [];
        }

        $roleId = self::getUserProperty($authUser, 'role_id');
        $userId = self::getUserProperty($authUser, 'id');
        $companyId = self::getUserProperty($authUser, 'company_id');

        /**
         * 🔥 SUPERADMIN (role_id = 1) - Can see role_id 2, 3, 7 only (never other SuperAdmins)
         */
        if ($roleId == 1) {
            return DB::table('users')
                ->where('company_id', $companyId)
                ->whereIn('role_id', [2, 3, 7])
                ->pluck('id')
                ->toArray();
        }

        /**
         * 🔥 ADMIN (role_id = 7) - Can see only assigned role_id 2 (supervisors) and role_id 3 (guards).
         * Must NOT see other admins (other users with role_id 7). Admin does not see themselves in operational lists.
         */
        if ($roleId == 7) {
            $siteAssigned = SiteAssign::where('user_id', $userId)->first();

            if ($siteAssigned) {
                // site_id contains client_ids in JSON format for Admin
                $clientIds = json_decode($siteAssigned->site_id, true);

                if (!is_array($clientIds)) {
                    $clientIds = [$clientIds];
                }

                // All sites under these clients
                $siteArray = SiteDetail::whereIn('client_id', $clientIds)
                    ->pluck('id')
                    ->toArray();

                // Assigned supervisors (role 2) having any of these sites
                $supervisorIds = [];
                if (!empty($siteArray)) {
                    $supervisorIds = SiteAssign::where('role_id', 2)
                        ->where(function ($query) use ($siteArray) {
                            foreach ($siteArray as $siteId) {
                                $query->orWhereRaw(
                                    'JSON_CONTAINS(site_id, ?)',
                                    [json_encode($siteId)]
                                );
                            }
                        })
                        ->pluck('user_id')
                        ->toArray();
                }

                // Assigned guards (role 3) under same clients & company
                $employeeIds = SiteAssign::where('role_id', 3)
                    ->whereIn('client_id', $clientIds)
                    ->where('company_id', $companyId)
                    ->pluck('user_id')
                    ->toArray();

                // Only assigned supervisors (2) + assigned guards (3); never other admins (7), never self in operational lists
                $userIdArray = array_unique(
                    array_merge($supervisorIds, $employeeIds)
                );

                return $userIdArray;
            }

            return [];
        }

        /**
         * 🔥 SUPERVISOR (role_id = 2) - Can see ONLY assigned guards (role 3). Must NOT see own details or other supervisors.
         */
        if ($roleId == 2) {
            $siteAssigned = SiteAssign::where('user_id', $userId)->first();

            if ($siteAssigned && $siteAssigned->site_id) {
                $siteIds = json_decode($siteAssigned->site_id, true);
                if (!is_array($siteIds)) {
                    // Support CSV format e.g. "1,2,3"
                    $siteIds = array_filter(array_map('trim', explode(',', (string) $siteAssigned->site_id)));
                }
                if (empty($siteIds)) {
                    $siteIds = [];
                }
            } else {
                $siteIds = [];
            }

            if (empty($siteIds)) {
                return [];
            }

            // Only guards (role 3) assigned to these sites; support both JSON and CSV site_id
            $employeeIds = DB::table('site_assign')
                ->join('users', 'users.id', '=', 'site_assign.user_id')
                ->where('users.role_id', 3)
                ->where('site_assign.company_id', $companyId)
                ->where(function ($query) use ($siteIds) {
                    foreach ($siteIds as $sid) {
                        $sid = is_string($sid) ? $sid : (string) $sid;
                        if ($sid === '') {
                            continue;
                        }
                        $encoded = json_encode($sid);
                        $query->orWhereRaw('(JSON_CONTAINS(site_assign.site_id, ?) OR FIND_IN_SET(?, site_assign.site_id) > 0)', [$encoded, $sid]);
                    }
                })
                ->pluck('users.id')
                ->unique()
                ->values()
                ->toArray();

            // Only assigned guards (role 3); supervisor does not see themselves in lists
            return array_unique($employeeIds);
        }

        // Default: only the user themselves
        return [$userId];
    }

    /**
     * Get accessible site IDs based on role
     * 
     * @return array
     */
    public static function getAccessibleSiteIds(): array
    {
        $authUser = session('user');

        if (!$authUser) {
            $authUser = Auth::user();
        }

        if (!$authUser) {
            return [];
        }

        $roleId = self::getUserProperty($authUser, 'role_id');
        $userId = self::getUserProperty($authUser, 'id');

        /**
         * 🔥 SUPERADMIN (role_id = 1) - Can see all sites
         */
        if ($roleId == 1) {
            return DB::table('site_details')->pluck('id')->toArray();
        }

        /**
         * 🔥 ADMIN (role_id = 7) - Can see sites under their assigned clients
         */
        if ($roleId == 7) {
            $siteAssigned = SiteAssign::where('user_id', $userId)->first();

            if ($siteAssigned) {
                // site_id contains client_ids in JSON format
                $clientIds = json_decode($siteAssigned->site_id, true);

                if (!is_array($clientIds)) {
                    $clientIds = [$clientIds];
                }

                // All sites under these clients
                $siteArray = SiteDetail::whereIn('client_id', $clientIds)
                    ->pluck('id')
                    ->toArray();

                return $siteArray;
            }

            return [];
        }

        /**
         * 🔥 SUPERVISOR (role_id = 2) - Can see only their assigned sites
         */
        if ($roleId == 2) {
            $siteAssigned = SiteAssign::where('user_id', $userId)->first();

            if ($siteAssigned && $siteAssigned->site_id) {
                $siteIds = json_decode($siteAssigned->site_id, true);

                if (!is_array($siteIds)) {
                    $siteIds = [$siteIds];
                }

                return $siteIds;
            }

            return [];
        }

        // Default: no sites
        return [];
    }

    /**
     * Get accessible client IDs based on role
     * 
     * @return array
     */
    public static function getAccessibleClientIds(): array
    {
        $authUser = session('user');

        if (!$authUser) {
            $authUser = Auth::user();
        }

        if (!$authUser) {
            return [];
        }

        $roleId = self::getUserProperty($authUser, 'role_id');
        $userId = self::getUserProperty($authUser, 'id');
        $companyId = self::getUserProperty($authUser, 'company_id');

        /**
         * 🔥 SUPERADMIN (role_id = 1) - Can see all clients
         */
        if ($roleId == 1) {
            return DB::table('client_details')->where('company_id', $companyId)->pluck('id')->toArray();
        }

        /**
         * 🔥 ADMIN (role_id = 7) - Can see their assigned clients
         */
        if ($roleId == 7) {
            $siteAssigned = SiteAssign::where('user_id', $userId)->first();

            if ($siteAssigned) {
                // site_id contains client_ids in JSON format for Admin
                $clientIds = json_decode($siteAssigned->site_id, true);

                if (!is_array($clientIds)) {
                    $clientIds = [$clientIds];
                }

                return $clientIds;
            }

            return [];
        }

        /**
         * 🔥 SUPERVISOR (role_id = 2) - Can see clients from their assigned sites
         */
        if ($roleId == 2) {
            $siteIds = self::getAccessibleSiteIds();

            if (empty($siteIds)) {
                return [];
            }

            $clientIds = SiteDetail::whereIn('id', $siteIds)
                ->distinct()
                ->pluck('client_id')
                ->toArray();

            return $clientIds;
        }

        // Default: no clients
        return [];
    }

    /**
     * Get accessible guard IDs only (role_id = 3)
     * 
     * @return array
     */
    public static function getAccessibleGuardIds(): array
    {
        $allUserIds = self::getAccessibleUserIds();

        // Filter only guards (role_id = 3)
        return DB::table('users')
            ->whereIn('id', $allUserIds)
            ->where('role_id', 3)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get accessible supervisor IDs only (role_id = 2)
     * 
     * @return array
     */
    public static function getAccessibleSupervisorIds(): array
    {
        $allUserIds = self::getAccessibleUserIds();

        // Filter only supervisors (role_id = 2)
        return DB::table('users')
            ->whereIn('id', $allUserIds)
            ->where('role_id', 2)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Apply role-based filters to a query
     * 
     * @param Builder $query
     * @param string $userIdColumn
     * @return Builder
     */
    public static function applyFilters(Builder $query, string $userIdColumn = 'user_id'): Builder
    {
        $accessibleUserIds = self::getAccessibleUserIds();

        if (empty($accessibleUserIds)) {
            // No accessible users - return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($userIdColumn, $accessibleUserIds);
    }

    /**
     * Apply site-based filters to a query
     * 
     * @param Builder $query
     * @param string $siteIdColumn
     * @return Builder
     */
    public static function applySiteFilters(Builder $query, string $siteIdColumn = 'site_id'): Builder
    {
        $accessibleSiteIds = self::getAccessibleSiteIds();

        if (empty($accessibleSiteIds)) {
            // No accessible sites - return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($siteIdColumn, $accessibleSiteIds);
    }

    /**
     * Apply client-based filters to a query
     * 
     * @param Builder $query
     * @param string $clientIdColumn
     * @return Builder
     */
    public static function applyClientFilters(Builder $query, string $clientIdColumn = 'client_id'): Builder
    {
        $accessibleClientIds = self::getAccessibleClientIds();

        if (empty($accessibleClientIds)) {
            // No accessible clients - return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($clientIdColumn, $accessibleClientIds);
    }

    /**
     * Check if user can access a specific guard
     * 
     * @param int $guardId
     * @return bool
     */
    public static function canAccessGuard(int $guardId): bool
    {
        $accessibleUserIds = self::getAccessibleUserIds();
        return in_array($guardId, $accessibleUserIds);
    }

    /**
     * Check if user can access a specific site
     * 
     * @param int $siteId
     * @return bool
     */
    public static function canAccessSite(int $siteId): bool
    {
        $accessibleSiteIds = self::getAccessibleSiteIds();
        return in_array($siteId, $accessibleSiteIds);
    }

    /**
     * Check if user can access a specific client
     * 
     * @param int $clientId
     * @return bool
     */
    public static function canAccessClient(int $clientId): bool
    {
        $accessibleClientIds = self::getAccessibleClientIds();
        return in_array($clientId, $accessibleClientIds);
    }

    /**
     * Get accessible site details
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function getAccessibleSiteDetails()
    {
        $accessibleSiteIds = self::getAccessibleSiteIds();

        if (empty($accessibleSiteIds)) {
            return collect([]);
        }

        return DB::table('site_details')
            ->whereIn('id', $accessibleSiteIds)
            ->get();
    }

    /**
     * Get dashboard counts based on role
     * 
     * @return array
     */
    public static function getDashboardCounts(): array
    {
        $authUser = session('user');

        if (!$authUser) {
            $authUser = Auth::user();
        }

        if (!$authUser) {
            return [
                'total_guards' => 0,
                'total_supervisors' => 0,
                'total_sites' => 0,
                'active_users' => 0,
            ];
        }

        $accessibleUserIds = self::getAccessibleUserIds();
        $accessibleSiteIds = self::getAccessibleSiteIds();

        $totalGuards = DB::table('users')
            ->whereIn('id', $accessibleUserIds)
            ->where('role_id', 3)
            ->where('isActive', 1)
            ->count();

        $totalSupervisors = DB::table('users')
            ->whereIn('id', $accessibleUserIds)
            ->where('role_id', 2)
            ->where('isActive', 1)
            ->count();

        $totalSites = count($accessibleSiteIds);

        $activeUsers = DB::table('users')
            ->whereIn('id', $accessibleUserIds)
            ->where('isActive', 1)
            ->count();

        return [
            'total_guards' => $totalGuards,
            'total_supervisors' => $totalSupervisors,
            'total_sites' => $totalSites,
            'active_users' => $activeUsers,
        ];
    }
}
