<?php

namespace App\Http\Controllers;

use App\Models\AuditObservation;
use App\Models\Store;
use App\Models\VisitAudit;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ObservationController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * List observations with filters and role-scoping.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = AuditObservation::withoutTrashed()->with(['store.area', 'supervisor', 'visitAudit', 'closedBy', 'reopenedBy']);

        // Scope by role
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            // Unrestricted
        } elseif ($user->hasRole('asst_treasury_manager')) {
            $query->where('audit_type', 'treasury');
        } elseif ($user->hasRole('asst_cashier_manager')) {
            $query->where('audit_type', 'cashier');
        } elseif ($user->hasRole('area_treasury_supervisor') || $user->hasRole('area_cashier_supervisor')) {
            $expected = $user->hasRole('area_treasury_supervisor') ? 'treasury' : 'cashier';
            $query->where('audit_type', $expected);

            $assignedStoreIds = DB::table('supervisor_store_assignments')
                ->where('supervisor_id', $user->id)
                ->where('is_active', true)
                ->pluck('store_id')
                ->toArray();

            $query->whereIn('store_id', $assignedStoreIds);
        } else {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Filters
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->query('store_id'));
        }
        if ($request->filled('audit_type')) {
            $query->where('audit_type', $request->query('audit_type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('visit_audit_id')) {
            $query->where('visit_audit_id', $request->query('visit_audit_id'));
        }

        $observations = $query->orderBy('observed_at', 'desc')->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $observations,
        ]);
    }

    /**
     * Create an observation.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Derive audit type from role
        $auditType = 'treasury';
        if ($user->hasRole('asst_cashier_manager') || $user->hasRole('area_cashier_supervisor')) {
            $auditType = 'cashier';
        } elseif ($user->hasRole('asst_treasury_manager') || $user->hasRole('area_treasury_supervisor')) {
            $auditType = 'treasury';
        } elseif ($user->hasRole('admin') || $user->hasRole('manager')) {
            $auditType = $request->input('audit_type', 'treasury');
        }

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'visit_audit_id' => ['nullable', 'exists:visit_audits,id'],
            'personnel_name' => ['required', 'string', 'max:150'],
            'issue_description' => ['required', 'string'],
            'action_required' => ['required', 'string'],
            'target_escalation_date' => ['required', 'date'],
            'areas_for_improvement' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        // Server-Side Idempotency Check (TR1001)
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $validated['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $cacheKey = "idempotency:observation:{$user->id}:{$idempotencyKey}";
            $cachedId = Cache::get($cacheKey);
            if ($cachedId) {
                $cachedObs = AuditObservation::find($cachedId);
                if ($cachedObs) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Observation logged successfully.',
                        'data' => $cachedObs->load(['store.area', 'supervisor']),
                    ], 200);
                }
            }
        }

        // Secondary debounce check (10 second window for exact duplicate content)
        $recentDuplicate = AuditObservation::where('store_id', $validated['store_id'])
            ->where('supervisor_id', $user->id)
            ->where('issue_description', $validated['issue_description'])
            ->where('created_at', '>=', now()->subSeconds(10))
            ->first();

        if ($recentDuplicate) {
            return response()->json([
                'status' => 'success',
                'message' => 'Observation logged successfully.',
                'data' => $recentDuplicate->load(['store.area', 'supervisor']),
            ], 200);
        }

        $observation = AuditObservation::create([
            'store_id' => $validated['store_id'],
            'visit_audit_id' => $validated['visit_audit_id'] ?? null,
            'audit_type' => $auditType,
            'supervisor_id' => $user->id,
            'observed_at' => now(),
            'personnel_name' => $validated['personnel_name'],
            'issue_description' => $validated['issue_description'],
            'action_required' => $validated['action_required'],
            'target_escalation_date' => $validated['target_escalation_date'],
            'areas_for_improvement' => $validated['areas_for_improvement'] ?? null,
            'status' => 'open',
        ]);

        if ($idempotencyKey) {
            Cache::put("idempotency:observation:{$user->id}:{$idempotencyKey}", $observation->id, 120);
        }

        $this->auditLogService->log($observation, 'created', null, $observation->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Observation logged successfully.',
            'data' => $observation->load(['store.area', 'supervisor']),
        ], 201);
    }

    /**
     * Show single observation.
     */
    public function show(AuditObservation $auditObservation): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $auditObservation->load(['store.area', 'supervisor', 'visitAudit', 'closedBy', 'reopenedBy']),
        ]);
    }

    /**
     * Update follow-up fields. Core fields (store_id, audit_type, observed_at) remain immutable.
     */
    public function update(Request $request, AuditObservation $auditObservation): JsonResponse
    {
        $user = Auth::user();

        // Only creator, manager or admin can update
        if (!$user->hasRole('admin') && !$user->hasRole('manager') && $auditObservation->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized to edit this observation.');
        }

        // Must not be closed
        if ($auditObservation->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot edit a closed observation. Reopen it first.',
            ], 422);
        }

        $validated = $request->validate([
            'personnel_name' => ['sometimes', 'required', 'string', 'max:150'],
            'issue_description' => ['sometimes', 'required', 'string'],
            'action_required' => ['sometimes', 'required', 'string'],
            'target_escalation_date' => ['sometimes', 'required', 'date'],
            'areas_for_improvement' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:open,escalated'],
        ]);

        $old = $auditObservation->toArray();
        $auditObservation->update($validated);

        $this->auditLogService->log($auditObservation, 'updated', $old, $auditObservation->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Observation updated.',
            'data' => $auditObservation->fresh()->load(['store.area', 'supervisor']),
        ]);
    }

    /**
     * Resolve an observation.
     */
    public function resolve(Request $request, AuditObservation $auditObservation): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'resolution_details' => ['required', 'string', 'min:5'],
            'date_resolved' => ['nullable', 'date'],
        ]);

        $old = $auditObservation->toArray();

        $auditObservation->update([
            'status' => 'resolved',
            'resolution_details' => $validated['resolution_details'],
            'date_resolved' => $validated['date_resolved'] ?? now()->toDateString(),
        ]);

        $this->auditLogService->log($auditObservation, 'resolved', $old, $auditObservation->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Observation resolved.',
            'data' => $auditObservation->fresh(),
        ]);
    }

    /**
     * Close an observation (Admins/Managers).
     */
    public function close(Request $request, AuditObservation $auditObservation): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasRole('admin') && !$user->hasRole('manager')) {
            abort(403, 'Only managers and admins can close observations.');
        }

        $old = $auditObservation->toArray();

        $auditObservation->update([
            'status' => 'closed',
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $this->auditLogService->log($auditObservation, 'closed', $old, $auditObservation->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Observation closed.',
            'data' => $auditObservation->fresh(),
        ]);
    }

    /**
     * Reopen an observation with a mandatory reason.
     */
    public function reopen(Request $request, AuditObservation $auditObservation): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'reopen_reason' => ['required', 'string', 'min:5'],
        ]);

        $old = $auditObservation->toArray();

        $auditObservation->update([
            'status' => 'open',
            'reopened_by' => $user->id,
            'reopened_at' => now(),
            'reopen_reason' => $validated['reopen_reason'],
        ]);

        $this->auditLogService->log($auditObservation, 'reopened', $old, $auditObservation->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Observation reopened.',
            'data' => $auditObservation->fresh(),
        ]);
    }

    /**
     * Soft delete observation.
     */
    public function destroy(AuditObservation $auditObservation): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasRole('admin') && $auditObservation->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $old = $auditObservation->toArray();
        $auditObservation->delete();

        $this->auditLogService->log($auditObservation, 'deleted', $old, null, $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Observation deleted.',
        ]);
    }
}

