<?php

namespace App\Http\Controllers;

use App\Models\AuditChecklist;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditChecklistController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * List checklist templates.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = AuditChecklist::query()->orderBy('category')->orderBy('sort_order');

        $auditType = $request->query('audit_type');

        if ($user->hasRole('asst_treasury_manager') || $user->hasRole('area_treasury_supervisor')) {
            $query->where('audit_type', 'treasury');
        } elseif ($user->hasRole('asst_cashier_manager') || $user->hasRole('area_cashier_supervisor')) {
            $query->where('audit_type', 'cashier');
        } elseif ($auditType) {
            $query->where('audit_type', $auditType);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $items = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    /**
     * Store new checklist template item.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeTemplateManagement($user, $request->input('audit_type'));

        $validated = $request->validate([
            'audit_type' => ['required', 'in:treasury,cashier'],
            'category' => ['required', 'string', 'max:150'],
            'title' => ['nullable', 'string', 'max:255'],
            'activity' => ['required', 'string', 'max:255'],
            'sub_activity' => ['nullable', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $item = AuditChecklist::create([
            'audit_type' => $validated['audit_type'],
            'category' => $validated['category'],
            'title' => $validated['title'] ?? $validated['category'],
            'activity' => $validated['activity'],
            'sub_activity' => $validated['sub_activity'] ?? null,
            'has_sub_activity' => !empty($validated['sub_activity']),
            'weight' => $validated['weight'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->auditLogService->log($item, 'created', null, $item->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Checklist template item created successfully.',
            'data' => $item,
        ], 201);
    }

    /**
     * Update existing checklist template item.
     */
    public function update(Request $request, AuditChecklist $auditChecklist): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeTemplateManagement($user, $auditChecklist->audit_type);

        $validated = $request->validate([
            'category' => ['sometimes', 'required', 'string', 'max:150'],
            'title' => ['nullable', 'string', 'max:255'],
            'activity' => ['sometimes', 'required', 'string', 'max:255'],
            'sub_activity' => ['nullable', 'string', 'max:255'],
            'weight' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $auditChecklist->toArray();

        $auditChecklist->update(array_merge($validated, [
            'updated_by' => $user->id,
            'has_sub_activity' => isset($validated['sub_activity']) ? !empty($validated['sub_activity']) : $auditChecklist->has_sub_activity,
        ]));

        $this->auditLogService->log($auditChecklist, 'updated', $old, $auditChecklist->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Checklist template item updated.',
            'data' => $auditChecklist,
        ]);
    }

    /**
     * Soft delete checklist item.
     */
    public function destroy(AuditChecklist $auditChecklist): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeTemplateManagement($user, $auditChecklist->audit_type);

        $old = $auditChecklist->toArray();
        $auditChecklist->delete();

        $this->auditLogService->log($auditChecklist, 'deleted', $old, null, $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Checklist item deleted.',
        ]);
    }

    /**
     * Helper to verify role permission for template management.
     */
    protected function authorizeTemplateManagement($user, string $auditType): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        if ($auditType === 'treasury' && $user->hasRole('asst_treasury_manager')) {
            return;
        }

        if ($auditType === 'cashier' && $user->hasRole('asst_cashier_manager')) {
            return;
        }

        abort(403, 'Unauthorized to manage checklist templates for this audit type.');
    }
}

