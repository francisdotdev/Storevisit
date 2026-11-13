<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Store;
use App\Models\StoreVisitSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreVisitScheduleController extends Controller
{
    /**
     * Display a listing of store visit schedules.
     * Supports Month/Period Grid View and List View with filtering, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = StoreVisitSchedule::with([
            'store:id,store_code,store_name,area_id',
            'store.area:id,area_code,area_name',
            'supervisor:id,first_name,last_name,employee_id,role_id',
            'supervisor.role:id,name,display_name',
            'scheduler:id,first_name,last_name,employee_id',
        ]);

        // 1. Role-based department and assignment scoping per BRD
        if ($user->isSupervisor()) {
            // Field supervisors can strictly ONLY view their own assigned schedules
            $query->where('supervisor_id', $user->id)
                ->where('audit_type', $user->getAuditType());
        } elseif ($user->isAssistantManager()) {
            // Assistant Managers are scoped to their department (Treasury or Cashier)
            $query->where('audit_type', $user->getAuditType());
        } elseif ($request->filled('audit_type') && in_array($request->input('audit_type'), ['treasury', 'cashier'])) {
            // Admin and Manager can filter by audit type
            $query->where('audit_type', $request->input('audit_type'));
        }

        // 2. Date Filtering
        if ($request->filled('month')) {
            // Filter by specific YYYY-MM period (e.g. 2026-11)
            $monthInput = $request->input('month');
            try {
                $startOfMonth = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()->toDateString();
                $endOfMonth = Carbon::createFromFormat('Y-m', $monthInput)->endOfMonth()->toDateString();
                $query->whereBetween('scheduled_date', [$startOfMonth, $endOfMonth]);
            } catch (\Exception) {
                // If invalid month format, ignore
            }
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('scheduled_date', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        } elseif ($request->filled('date')) {
            $query->whereDate('scheduled_date', $request->input('date'));
        }

        // 3. Area Filter (via store.area_id)
        if ($request->filled('area_id')) {
            $areaId = $request->input('area_id');
            $query->whereHas('store', fn ($q) => $q->where('area_id', $areaId));
        }

        // 4. Store Filter
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        // 5. Supervisor Filter (For Manager/Admin/Schedulers)
        if ($request->filled('supervisor_id') && !$user->isSupervisor()) {
            $query->where('supervisor_id', $request->input('supervisor_id'));
        }

        // 6. Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // 7. Search keyword (Store Code, Store Name, Schedule Code, Supervisor Name)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('schedule_code', 'like', "%{$search}%")
                    ->orWhereHas('store', function ($sq) use ($search) {
                        $sq->where('store_name', 'like', "%{$search}%")
                            ->orWhere('store_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('supervisor', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%");
                    });
            });
        }

        // Calculate summary metrics for current scope
        $statQuery = clone $query;
        $stats = [
            'total_visits' => (clone $statQuery)->count(),
            'scheduled' => (clone $statQuery)->where('status', 'scheduled')->count(),
            'in_progress' => (clone $statQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $statQuery)->where('status', 'cancelled')->count(),
        ];

        // If requested for Calendar Grid View (returns all events in the month)
        if ($request->boolean('all')) {
            $events = $query->orderBy('scheduled_date', 'asc')->get();

            return response()->json([
                'events' => $events,
                'stats' => $stats,
            ]);
        }

        // Otherwise return paginated List View
        $sortBy = in_array($request->input('sort_by'), ['scheduled_date', 'status', 'created_at'])
            ? $request->input('sort_by')
            : 'scheduled_date';
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);
        $schedules = $query->paginate($perPage);

        return response()->json([
            'schedules' => $schedules,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created store visit schedule.
     * Enforces BRD scheduling permissions:
     * - Assistant Treasury Manager schedules for Area Treasury Supervisors.
     * - Assistant Cashier Manager schedules for Area Cashier Supervisors.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'supervisor_id' => ['required', 'exists:users,id'],
            'audit_type' => ['required', 'in:treasury,cashier'],
            'scheduled_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:scheduled_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $auditType = $validated['audit_type'];

        // 1. Permission Check
        if ($auditType === 'treasury' && !$user->hasPermission('schedules.manage_treasury') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to schedule Treasury visits.'], 403);
        }

        if ($auditType === 'cashier' && !$user->hasPermission('schedules.manage_cashier') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to schedule Cashier visits.'], 403);
        }

        // 2. Validate Supervisor Role
        $supervisor = User::with('role')->findOrFail($validated['supervisor_id']);
        $expectedRole = $auditType === 'treasury' ? 'area_treasury_supervisor' : 'area_cashier_supervisor';

        if ($supervisor->role?->name !== $expectedRole) {
            $expectedRoleName = $auditType === 'treasury' ? 'Area Treasury Supervisor' : 'Area Cashier Supervisor';
            return response()->json([
                'message' => "Selected user must have the '{$expectedRoleName}' role for {$auditType} visits.",
            ], 422);
        }

        // 3. Generate unique schedule code
        $prefix = $auditType === 'treasury' ? 'SCH-TR' : 'SCH-CS';
        $datePart = Carbon::parse($validated['scheduled_date'])->format('Ymd');
        $randomPart = strtoupper(Str::random(4));
        $scheduleCode = "{$prefix}-{$datePart}-{$randomPart}";

        $schedule = StoreVisitSchedule::create([
            'schedule_code' => $scheduleCode,
            'store_id' => $validated['store_id'],
            'supervisor_id' => $validated['supervisor_id'],
            'scheduled_by' => $user->id,
            'audit_type' => $auditType,
            'scheduled_date' => $validated['scheduled_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => 'scheduled',
            'notes' => isset($validated['notes']) && $validated['notes'] !== null ? trim($validated['notes']) : null,
        ]);

        return response()->json([
            'message' => 'Store visit scheduled successfully.',
            'schedule' => $schedule->load([
                'store:id,store_code,store_name,area_id',
                'store.area:id,area_code,area_name',
                'supervisor:id,first_name,last_name,employee_id',
                'scheduler:id,first_name,last_name,employee_id',
            ]),
        ], 201);
    }

    /**
     * Display the specified visit schedule details.
     */
    public function show(Request $request, StoreVisitSchedule $schedule): JsonResponse
    {
        $user = $request->user();

        // Enforce supervisor scoping
        if ($user->isSupervisor()) {
            if ($schedule->supervisor_id !== $user->id || $schedule->audit_type !== $user->getAuditType()) {
                return response()->json(['message' => 'Unauthorized. You cannot view schedules assigned to other supervisors.'], 403);
            }
        } elseif ($user->isAssistantManager()) {
            if ($schedule->audit_type !== $user->getAuditType()) {
                return response()->json(['message' => 'Unauthorized. You cannot view schedules of the other department.'], 403);
            }
        }

        return response()->json([
            'schedule' => $schedule->load([
                'store:id,store_code,store_name,area_id',
                'store.area:id,area_code,area_name',
                'supervisor:id,first_name,last_name,employee_id',
                'supervisor.role:id,name,display_name',
                'scheduler:id,first_name,last_name,employee_id',
            ]),
        ]);
    }

    /**
     * Update an existing visit schedule.
     */
    public function update(Request $request, StoreVisitSchedule $schedule): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if ($schedule->audit_type === 'treasury' && !$user->hasPermission('schedules.manage_treasury') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit Treasury visit schedules.'], 403);
        }

        if ($schedule->audit_type === 'cashier' && !$user->hasPermission('schedules.manage_cashier') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit Cashier visit schedules.'], 403);
        }

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'supervisor_id' => ['required', 'exists:users,id'],
            'scheduled_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:scheduled_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Validate supervisor role
        $supervisor = User::with('role')->findOrFail($validated['supervisor_id']);
        $expectedRole = $schedule->audit_type === 'treasury' ? 'area_treasury_supervisor' : 'area_cashier_supervisor';

        if ($supervisor->role?->name !== $expectedRole) {
            $expectedRoleName = $schedule->audit_type === 'treasury' ? 'Area Treasury Supervisor' : 'Area Cashier Supervisor';
            return response()->json([
                'message' => "Selected user must have the '{$expectedRoleName}' role for {$schedule->audit_type} visits.",
            ], 422);
        }

        $schedule->store_id = $validated['store_id'];
        $schedule->supervisor_id = $validated['supervisor_id'];
        $schedule->scheduled_date = $validated['scheduled_date'];
        $schedule->end_date = $validated['end_date'] ?? null;
        $schedule->notes = isset($validated['notes']) && $validated['notes'] !== null ? trim($validated['notes']) : null;
        $schedule->save();

        return response()->json([
            'message' => 'Store visit schedule updated successfully.',
            'schedule' => $schedule->fresh()->load([
                'store:id,store_code,store_name,area_id',
                'store.area:id,area_code,area_name',
                'supervisor:id,first_name,last_name,employee_id',
            ]),
        ]);
    }

    /**
     * Update visit execution status (Start Visit, Complete Visit, Cancel).
     * Enforces Time Started and Time End tracking.
     */
    public function updateStatus(Request $request, StoreVisitSchedule $schedule): JsonResponse
    {
        $user = $request->user();

        // Must be the assigned supervisor, or manager/admin
        $isAssignedSupervisor = $user->isSupervisor() && $schedule->supervisor_id === $user->id;
        $canManageThisSchedule = ($schedule->audit_type === 'treasury' && $user->hasPermission('schedules.manage_treasury'))
            || ($schedule->audit_type === 'cashier' && $user->hasPermission('schedules.manage_cashier'))
            || $user->isAdmin();

        if (!$isAssignedSupervisor && !$canManageThisSchedule) {
            return response()->json(['message' => 'Unauthorized. You cannot update the status of this visit.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $validated['status'];
        $schedule->status = $newStatus;

        if ($newStatus === 'in_progress' && !$schedule->started_at) {
            $schedule->started_at = $validated['started_at'] ?? now();
        } elseif (isset($validated['started_at'])) {
            $schedule->started_at = $validated['started_at'];
        }

        if ($newStatus === 'completed') {
            if (!$schedule->started_at) {
                $schedule->started_at = now()->subHours(2);
            }
            $schedule->completed_at = $validated['completed_at'] ?? now();
        } elseif (isset($validated['completed_at'])) {
            $schedule->completed_at = $validated['completed_at'];
        }

        if (isset($validated['notes'])) {
            $schedule->notes = trim($validated['notes']);
        }

        $schedule->save();

        return response()->json([
            'message' => "Visit status updated to '{$newStatus}'.",
            'schedule' => $schedule->fresh()->load([
                'store:id,store_code,store_name,area_id',
                'supervisor:id,first_name,last_name,employee_id',
            ]),
        ]);
    }

    /**
     * Delete or cancel a visit schedule.
     */
    public function destroy(Request $request, StoreVisitSchedule $schedule): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if ($schedule->audit_type === 'treasury' && !$user->hasPermission('schedules.manage_treasury') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized to cancel Treasury visits.'], 403);
        }

        if ($schedule->audit_type === 'cashier' && !$user->hasPermission('schedules.manage_cashier') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized to cancel Cashier visits.'], 403);
        }

        if ($schedule->status === 'completed') {
            return response()->json(['message' => 'Cannot delete a completed visit schedule.'], 422);
        }

        $schedule->delete();

        return response()->json([
            'message' => 'Visit schedule deleted successfully.',
        ]);
    }
}
