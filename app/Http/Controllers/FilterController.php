<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\FilterDataTrait;

class FilterController extends Controller
{
    use FilterDataTrait;

    /**
     * Get beats for a specific range
     */
    public function beats($rangeId)
    {
        try {
            \Log::info('Beats endpoint called with rangeId: ' . $rangeId);
            
            if (empty($rangeId)) {
                return response()->json(['beats' => []]);
            }

            $beats = $this->getBeatsForRange((int)$rangeId);
            
            \Log::info('Beats loaded successfully: ' . count($beats) . ' beats found');
            
            return response()->json(['beats' => $beats]);
        } catch (\Exception $e) {
            \Log::error('Error loading beats: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'beats' => [], 
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Get users filtered by range and/or beat
     */
    public function users(Request $request)
    {
        try {
            $rangeId = $request->input('range') ? (int)$request->input('range') : null;
            $beatId = $request->input('beat') ? (int)$request->input('beat') : null;
            
            $users = $this->getUsersForFilters($rangeId, $beatId);
            
            return response()->json(['users' => $users]);
        } catch (\Exception $e) {
            \Log::error('Error loading users: ' . $e->getMessage());
            return response()->json(['users' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get guard autocomplete suggestions
     */
    public function guardAutocomplete(Request $request)
    {
        try {
            $searchTerm = $request->input('q', '');
            
            // Require at least 2 characters
            if (strlen($searchTerm) < 2) {
                return response()->json(['suggestions' => []]);
            }
            
            $user = session('user');
            $companyId = ($user && isset($user->company_id)) ? $user->company_id : 56;
            
            // Get range and beat filters if provided
            $rangeId = $request->input('range') ? (int)$request->input('range') : null;
            $beatId = $request->input('beat') ? (int)$request->input('beat') : null;
            
            $query = DB::table('users')
                ->where('users.company_id', $companyId)
                ->where('users.isActive', 1)
                ->where(function($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
                });
            
            // Apply range/beat filters if provided
            if ($beatId) {
                $query->join('site_assign', 'users.id', '=', 'site_assign.user_id')
                      ->whereRaw('FIND_IN_SET(?, site_assign.site_id)', [$beatId])
                      ->distinct();
            } elseif ($rangeId) {
                $query->join('site_assign', 'users.id', '=', 'site_assign.user_id')
                      ->where('site_assign.client_id', $rangeId)
                      ->distinct();
            }
            
            $suggestions = $query->select('users.id', 'users.name', 'users.email')
                                 ->orderBy('users.name')
                                 ->limit(10)
                                 ->get()
                                 ->map(function($user) {
                                     return [
                                         'id' => $user->id,
                                         'name' => $user->name,
                                         'email' => $user->email,
                                         'label' => $user->name . ($user->email ? ' (' . $user->email . ')' : '')
                                     ];
                                 });
            
            return response()->json(['suggestions' => $suggestions]);
        } catch (\Exception $e) {
            \Log::error('Error in guard autocomplete: ' . $e->getMessage());
            return response()->json(['suggestions' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Legacy method - kept for compatibility
     */
    public function compartments($beatId)
    {
        return response()->json(['compartments' => []]);
    }
}