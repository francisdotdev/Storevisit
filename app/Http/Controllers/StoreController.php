<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Role;
use App\Models\Store;
use App\Models\SupervisorStoreAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    /**
     * Display a listing of stores with area relation, search, filtering, and pagination.
     * Enforces BRD scoping: Area Supervisors can only view their authorized assigned stores.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // If requested for quick dropdown selection
        if ($request->boolean('all')) {
            $dropdownQuery = Store::where('is_active', true);

            if ($user->isSupervisor()) {
                $userAuditType = $user->getAuditType();
                $dropdownQuery->whereHas('supervisorAssignments', function ($q) use ($user, $userAuditType) {
                    $q->where('supervisor_id', $user->id)
                        ->where('audit_type', $userAuditType);
                });
            }

            $stores = $dropdownQuery->orderBy('store_name')->get(['id', 'store_code', 'store_name', 'area_id']);
            return response()->json($stores);
        }

        $query = Store::with([
            'area:id,area_code,area_name',
            'creator:id,first_name,last_name,employee_id',
            'supervisorAssignments.supervisor:id,first_name,last_name,employee_id,role_id',
            'supervisorAssignments.supervisor.role:id,name,display_name',
        ]);

        // BRD Scoping: Field supervisors can ONLY view stores assigned to them
        if ($user->isSupervisor()) {
            $userAuditType = $user->getAuditType();
            $query->whereHas('supervisorAssignments', function ($q) use ($user, $userAuditType) {
                $q->where('supervisor_id', $user->id)
                    ->where('audit_type', $userAuditType);
            });
        }

        // Filter by Area
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        // Filter by Status (active, inactive, all)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        // Search by Store Code or Store Name
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('store_code', 'like', "%{$search}%")
                    ->orWhere('store_name', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array($request->input('sort_by'), ['id', 'store_code', 'store_name', 'area_id', 'created_at'])
            ? $request->input('sort_by')
            : 'store_name';
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);
        $stores = $query->paginate($perPage);

        // Stats calculation
        $baseStatQuery = Store::query();
        if ($user->isSupervisor()) {
            $userAuditType = $user->getAuditType();
            $baseStatQuery->whereHas('supervisorAssignments', function ($q) use ($user, $userAuditType) {
                $q->where('supervisor_id', $user->id)
                    ->where('audit_type', $userAuditType);
            });
        }

        $stats = [
            'total' => (clone $baseStatQuery)->count(),
            'active' => (clone $baseStatQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseStatQuery)->where('is_active', false)->count(),
            'total_areas' => Area::where('is_active', true)->count(),
        ];

        return response()->json([
            'stores' => $stores,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created store.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPermission('stores.manage')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to add stores.'], 403);
        }

        $validated = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'store_code' => ['required', 'string', 'max:50', 'unique:stores,store_code'],
            'store_name' => ['required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $store = Store::create([
            'area_id' => $validated['area_id'],
            'store_code' => strtoupper(trim($validated['store_code'])),
            'store_name' => trim($validated['store_name']),
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Store created successfully.',
            'store' => $store->load(['area:id,area_code,area_name', 'creator:id,first_name,last_name,employee_id']),
        ], 201);
    }

    /**
     * Display the specified store details with area and assigned supervisors.
     */
    public function show(Request $request, Store $store): JsonResponse
    {
        $user = $request->user();

        if (!$user->canAccessStore($store)) {
            return response()->json(['message' => 'Unauthorized. You do not have access to view this store.'], 403);
        }

        return response()->json([
            'store' => $store->load([
                'area:id,area_code,area_name',
                'creator:id,first_name,last_name,employee_id',
                'supervisorAssignments.supervisor:id,first_name,last_name,employee_id,role_id',
                'supervisorAssignments.supervisor.role:id,name,display_name',
                'supervisorAssignments.assigner:id,first_name,last_name,employee_id',
            ]),
        ]);
    }

    /**
     * Update the specified store.
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPermission('stores.manage')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit stores.'], 403);
        }

        $validated = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'store_code' => ['required', 'string', 'max:50', Rule::unique('stores', 'store_code')->ignore($store->id)],
            'store_name' => ['required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $store->area_id = $validated['area_id'];
        $store->store_code = strtoupper(trim($validated['store_code']));
        $store->store_name = trim($validated['store_name']);

        if (isset($validated['is_active'])) {
            $store->is_active = $validated['is_active'];
        }

        $store->save();

        return response()->json([
            'message' => 'Store updated successfully.',
            'store' => $store->fresh()->load([
                'area:id,area_code,area_name',
                'creator:id,first_name,last_name,employee_id',
            ]),
        ]);
    }

    /**
     * Toggle the active status of a store.
     */
    public function toggleStatus(Request $request, Store $store): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPermission('stores.manage')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to change store status.'], 403);
        }

        $store->is_active = !$store->is_active;
        $store->save();

        $statusText = $store->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "Store {$store->store_name} ({$store->store_code}) has been {$statusText}.",
            'store' => $store->fresh()->load('area:id,area_code,area_name'),
        ]);
    }

    /**
     * Assign an Area Supervisor to this store.
     * Enforces BRD responsibility:
     * - Assistant Treasury Manager assigns specifically to Area Treasury Supervisors.
     * - Assistant Cashier Manager assigns specifically to Area Cashier Supervisors.
     */
    public function assignSupervisor(Request $request, Store $store): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'supervisor_id' => ['required', 'exists:users,id'],
            'audit_type' => ['required', 'in:treasury,cashier'],
        ]);

        $auditType = $validated['audit_type'];

        // Enforce department authorization
        if ($auditType === 'treasury' && !$user->hasPermission('store_assignments.treasury') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to assign Treasury supervisors.'], 403);
        }

        if ($auditType === 'cashier' && !$user->hasPermission('store_assignments.cashier') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to assign Cashier supervisors.'], 403);
        }

        $supervisor = User::with('role')->findOrFail($validated['supervisor_id']);

        // Verify supervisor's role matches the assigned audit type
        $expectedRole = $auditType === 'treasury' ? 'area_treasury_supervisor' : 'area_cashier_supervisor';
        if ($supervisor->role?->name !== $expectedRole) {
            $expectedRoleName = $auditType === 'treasury' ? 'Area Treasury Supervisor' : 'Area Cashier Supervisor';
            return response()->json([
                'message' => "Selected user must have the '{$expectedRoleName}' role for {$auditType} assignments.",
            ], 422);
        }

        // Create or update assignment
        $assignment = SupervisorStoreAssignment::updateOrCreate(
            [
                'supervisor_id' => $supervisor->id,
                'store_id' => $store->id,
                'audit_type' => $auditType,
            ],
            [
                'assigned_by' => $user->id,
            ]
        );

        return response()->json([
            'message' => "Supervisor {$supervisor->full_name} assigned to {$store->store_name} for {$auditType} audits.",
            'assignment' => $assignment->load(['supervisor:id,first_name,last_name,employee_id,role_id', 'supervisor.role']),
        ]);
    }

    /**
     * Remove a supervisor assignment from a store.
     */
    public function removeSupervisorAssignment(Request $request, Store $store, SupervisorStoreAssignment $assignment): JsonResponse
    {
        $user = $request->user();

        if ($assignment->store_id !== $store->id) {
            return response()->json(['message' => 'Assignment does not belong to this store.'], 422);
        }

        if ($assignment->audit_type === 'treasury' && !$user->hasPermission('store_assignments.treasury') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You cannot remove Treasury supervisor assignments.'], 403);
        }

        if ($assignment->audit_type === 'cashier' && !$user->hasPermission('store_assignments.cashier') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You cannot remove Cashier supervisor assignments.'], 403);
        }

        $assignment->delete();

        return response()->json([
            'message' => 'Supervisor assignment removed successfully.',
        ]);
    }

    /**
     * Helper to get eligible active supervisors for assignment modals.
     */
    public function getEligibleSupervisors(Request $request): JsonResponse
    {
        $query = User::with('role')->where('status', 'active');

        if ($request->filled('audit_type')) {
            $roleName = $request->input('audit_type') === 'treasury'
                ? 'area_treasury_supervisor'
                : 'area_cashier_supervisor';

            $query->whereHas('role', fn ($q) => $q->where('name', $roleName));
        } else {
            $query->whereHas('role', fn ($q) => $q->whereIn('name', [
                'area_treasury_supervisor',
                'area_cashier_supervisor',
            ]));
        }

        $supervisors = $query->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_id', 'role_id']);

        return response()->json($supervisors);
    }
}

