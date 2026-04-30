<?php

namespace App\Http\Controllers;

use App\Models\BeatKmlFeature;
use App\Models\SiteAssign;
use App\Models\ClientDetail;
use App\Models\SiteDetail;
use App\Services\RoleBasedFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BeatMapController extends Controller
{
    public function forestIndex(Request $request)
    {
        $authUser = Session::get('user');
        if (!$authUser) {
            return redirect('/login');
        }

        $rangeId = $request->range_id;
        $siteId = $request->site_id;
        $year = $request->year;

        $availableRanges = [];
        $availableBeats = [];

        $availableYears = BeatKmlFeature::where('company_id', $authUser->company_id)
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'DESC')
            ->pluck('year');

        // Accessible Client IDs (Ranges)
        $accessibleClientIds = RoleBasedFilterService::getAccessibleClientIds();
        $availableRanges = ClientDetail::where('company_id', $authUser->company_id)
            ->whereIn('id', $accessibleClientIds)
            ->get(['id', 'name']);

        // Accessible Site IDs (Beats)
        $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();

        if ($rangeId) {
            $availableBeats = SiteDetail::where('company_id', $authUser->company_id)
                ->where('client_id', $rangeId)
                ->whereIn('id', $accessibleSiteIds)
                ->get(['id', 'name']);
        } elseif (count($availableRanges) == 1) {
            $availableBeats = SiteDetail::where('company_id', $authUser->company_id)
                ->where('client_id', $availableRanges[0]->id)
                ->whereIn('id', $accessibleSiteIds)
                ->get(['id', 'name']);
        }

        // Dynamic Layers based on database
        $availableLayers = BeatKmlFeature::where('company_id', $authUser->company_id)
            ->distinct()
            ->pluck('layer_type')
            ->toArray();

        return view('forest.know-your-area', [
            'availableRanges' => $availableRanges,
            'availableBeats' => $availableBeats,
            'availableYears' => $availableYears,
            'availableLayers' => $availableLayers,
            'selectedRange' => $rangeId,
            'selectedBeat' => $siteId,
            'selectedYear' => $year,
            'userRole' => $authUser->role_id,
            'hideGlobalFilters' => true,
        ]);
    }

    public function getMapData(Request $request)
    {
        $authUser = Session::get('user');
        if (!$authUser) {
            return response()->json(['status' => 'FAILURE', 'message' => 'Unauthorized'], 401);
        }

        $rangeId = $request->range_id;
        $siteId = $request->site_id;
        $geofenceId = $request->geofence_id;
        $year = $request->year;
        $onlyCounts = $request->boolean('only_counts');
        $layerTypesRequested = $request->layer_types; // Array of layers if provided

        $query = BeatKmlFeature::where('beat_features.company_id', $authUser->company_id);

        // Role-based scoping for features
        $accessibleSiteIds = RoleBasedFilterService::getAccessibleSiteIds();
        $query->whereIn('site_id', $accessibleSiteIds);

        if ($siteId) {
            $query->where('site_id', $siteId);
        } elseif ($rangeId) {
            $query->where('range_id', $rangeId);
        }

        if ($geofenceId) {
            $query->where('geofence_id', $geofenceId);
        }

        if ($year) {
            $query->where(function ($q) use ($year) {
                $q->where('year', $year)->orWhereNull('year');
            });
        }

        if ($layerTypesRequested && is_array($layerTypesRequested)) {
            $query->whereIn('layer_type', $layerTypesRequested);
        }

        // Get Geofences - Optimized SQL uniqueness to avoid large collection processing
        $geofences = DB::table('site_geofences')
            ->select('name', 'poly_lat_lng', 'type', 'radius', 'lat', 'lng', 'site_name')
            ->whereIn('id', function ($query) use ($authUser, $accessibleSiteIds, $siteId, $rangeId) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('site_geofences')
                    ->whereNull('deleted_at')
                    ->where('company_id', $authUser->company_id)
                    ->whereIn('site_id', $accessibleSiteIds);

                if ($siteId) {
                    $query->where('site_id', $siteId);
                } elseif ($rangeId) {
                    $query->where('client_id', $rangeId);
                }

                $query->groupBy('name');
            })
            ->get();

        if ($onlyCounts) {
            $counts = $query->select('layer_type', DB::raw('count(*) as aggregate'))
                ->groupBy('layer_type')
                ->get()
                ->pluck('aggregate', 'layer_type');

            return response()->json($this->cleanUtf8([
                'status' => 'SUCCESS',
                'counts' => $counts,
                'geofences' => $geofences,
            ]));
        }

        $features = $query->get(['id', 'layer_type', 'name', 'geometry_type', 'coordinates', 'attributes', 'year']);

        $data = [];
        foreach ($features as $feature) {
            $normalizedCoords = $this->normalizeCoordinates($feature->geometry_type, $feature->coordinates);

            if (empty($normalizedCoords))
                continue;

            $attrs = $feature->attributes;
            if (is_string($attrs)) {
                $attrs = json_decode($attrs, true) ?: [];
            } elseif (is_object($attrs)) {
                $attrs = (array) $attrs;
            }

            $data[$feature->layer_type][] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => $feature->geometry_type,
                    'coordinates' => $normalizedCoords,
                ],
                'properties' => array_merge($attrs ?? [], [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'layer_type' => $feature->layer_type,
                    'year' => $feature->year
                ])
            ];
        }

        return response()->json($this->cleanUtf8([
            'status' => 'SUCCESS',
            'data' => $data,
            'geofences' => $geofences,
        ]));
    }

    private function cleanUtf8($data)
    {
        if (is_string($data)) {
            // Force UTF-8 encoding and replace invalid sequences
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        } elseif (is_array($data)) {
            // Optimization: Skip recursive cleaning for coordinate arrays (nested numeric arrays)
            if (!empty($data) && isset($data[0]) && (is_numeric($data[0]) || (is_array($data[0]) && isset($data[0][0]) && is_numeric($data[0][0])))) {
                return $data;
            }
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleaned[$key] = $this->cleanUtf8($value);
            }
            return $cleaned;
        } elseif (is_object($data)) {
            // Handle stdClass/Collection objects
            if ($data instanceof \Illuminate\Support\Collection) {
                return $data->map(fn($item) => $this->cleanUtf8($item));
            }
            $cleaned = new \stdClass();
            foreach (get_object_vars($data) as $key => $value) {
                $cleaned->$key = $this->cleanUtf8($value);
            }
            return $cleaned;
        }
        return $data;
    }

    protected function normalizeCoordinates($type, $coords)
    {
        if (empty($coords))
            return [];

        if (is_string($coords)) {
            $coords = json_decode($coords, true);
        }

        if (!is_array($coords))
            return [];

        // For Leaflet, we keep [lat, lng] as they are in the database
        // and handle [lng, lat] conversion if needed.
        // The swapLatLog in the original controller suggests coordinates might be [lat, lng].

        if ($type === 'Point') {
            if (count($coords) > 0 && is_array($coords[0])) {
                $coords = $coords[0];
            }
            return $this->ensureLngLat($coords);
        }

        if ($type === 'LineString' || $type === 'MultiPoint') {
            return array_map([$this, 'ensureLngLat'], $coords);
        }

        if ($type === 'Polygon' || $type === 'MultiLineString') {
            if (count($coords) > 0 && is_array($coords[0]) && !is_array($coords[0][0])) {
                $coords = [$coords];
            }
            return array_map(function ($ring) {
                return array_map([$this, 'ensureLngLat'], $ring);
            }, $coords);
        }

        if ($type === 'MultiPolygon') {
            if (count($coords) > 0 && is_array($coords[0]) && is_array($coords[0][0]) && !is_array($coords[0][0][0])) {
                $coords = [$coords];
            }
            return array_map(function ($polygon) {
                return array_map(function ($ring) {
                    return array_map([$this, 'ensureLngLat'], $ring);
                }, $polygon);
            }, $coords);
        }

        return $coords;
    }

    /**
     * GeoJSON expects [lng, lat].
     * Our database heuristic: if val1 < 45 and val2 > 60, it's [lat, lng].
     */
    protected function ensureLngLat($pair)
    {
        if (!is_array($pair) || count($pair) < 2)
            return $pair;

        $val1 = floatval($pair[0]);
        $val2 = floatval($pair[1]);

        if ($val1 < 45 && $val2 > 60) {
            return [$val2, $val1];
        }

        return [$val1, $val2];
    }
}
