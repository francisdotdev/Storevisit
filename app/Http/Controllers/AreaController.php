<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    /**
     * Display a listing of areas with search, filtering, and summary statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // If requested for dropdown selection, return simple active list
        if ($request->boolean('all')) {
            $areas = Area::where('is_active', true)
                ->orderBy('area_name')
                ->get(['id', 'area_code', 'area_name']);

            return response()->json($areas);
        }

        $query = Area::withCount('stores')
            ->with('creator:id,first_name,last_name,employee_id');

        // Filter by status if provided (active, inactive, all)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        // Search by area code or area name
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('area_code', 'like', "%{$search}%")
                    ->orWhere('area_name', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array($request->input('sort_by'), ['id', 'area_code', 'area_name', 'created_at', 'stores_count'])
            ? $request->input('sort_by')
            : 'area_name';
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);
        $areas = $query->paginate($perPage);

        // Calculate summary counts
        $stats = [
            'total' => Area::count(),
            'active' => Area::where('is_active', true)->count(),
            'inactive' => Area::where('is_active', false)->count(),
            'total_stores' => Store::count(),
        ];

        return response()->json([
            'areas' => $areas,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created area location.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPermission('areas.manage')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to add areas.'], 403);
        }

        $validated = $request->validate([
            'area_code' => ['required', 'string', 'max:50', 'unique:areas,area_code'],
            'area_name' => ['required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $area = Area::create([
            'area_code' => strtoupper(trim($validated['area_code'])),
            'area_name' => trim($validated['area_name']),
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Area location created successfully.',
            'area' => $area->load('creator:id,first_name,last_name,employee_id'),
        ], 201);
    }

    /**
     * Display the specified area with associated stores.
     */
    public function show(Area $area): JsonResponse
    {
        return response()->json([
            'area' => $area->load(['stores', 'creator:id,first_name,last_name,employee_id']),
        ]);
    }

    /**
     * Update the specified area location.
     */
    public function update(Request $request, Area $area): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPermission('areas.manage')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit areas.'], 403);
        }

        $validated = $request->validate([
            'area_code' => ['required', 'string', 'max:50', Rule::unique('areas', 'area_code')->ignore($area->id)],
            'area_name' => ['required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $area->area_code = strtoupper(trim($validated['area_code']));
        $area->area_name = trim($validated['area_name']);

        if (isset($validated['is_active'])) {
            $area->is_active = $validated['is_active'];
        }

        $area->save();

        return response()->json([
            'message' => 'Area location updated successfully.',
            'area' => $area->fresh()->load('creator:id,first_name,last_name,employee_id'),
        ]);
    }

    /**
     * Toggle the active status of an area.
     */
    public function toggleStatus(Request $request, Area $area): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPermission('areas.manage')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to update area status.'], 403);
        }

        $area->is_active = !$area->is_active;
        $area->save();

        $statusText = $area->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "Area {$area->area_name} ({$area->area_code}) has been {$statusText}.",
            'area' => $area->fresh()->load('creator:id,first_name,last_name,employee_id'),
        ]);
    }
}

