<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RbacController extends Controller
{
    /**
     * Get the complete BRD Role-Permission Matrix.
     */
    public function matrix(): JsonResponse
    {
        return response()->json([
            'matrix' => AuthorizationService::getFullMatrix(),
            'all_permissions' => AuthorizationService::getAllPermissions(),
        ]);
    }

    /**
     * Get the current authenticated user's permissions and department profile.
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return response()->json([
            'user' => $user,
            'role' => $user->role?->name,
            'role_display' => $user->role?->display_name,
            'audit_type' => $user->getAuditType(),
            'permissions' => $user->getPermissions(),
            'is_admin' => $user->isAdmin(),
            'is_manager' => $user->isManager(),
            'is_asst_manager' => $user->isAssistantManager(),
            'is_supervisor' => $user->isSupervisor(),
        ]);
    }

    /**
     * Verify whether the current user is authorized to access a given store and audit type.
     */
    public function verifyStoreAccess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'audit_type' => ['nullable', 'string', 'in:treasury,cashier'],
        ]);

        $user = $request->user();
        $storeId = (int) $validated['store_id'];
        $auditType = $validated['audit_type'] ?? null;

        $hasAccess = $user->canAccessStore($storeId, $auditType);

        return response()->json([
            'store_id' => $storeId,
            'audit_type' => $auditType,
            'user_id' => $user->id,
            'user_role' => $user->role?->name,
            'authorized' => $hasAccess,
            'message' => $hasAccess
                ? 'Store access authorized for this user.'
                : 'Store access denied. The user is not assigned to this store for the specified audit type.',
        ], $hasAccess ? 200 : 403);
    }

    /**
     * Test endpoint for Manager/Admin report access.
     */
    public function testReportsView(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Authorized. You have permission to view audit reports.',
            'user' => $request->user()->fullName,
        ]);
    }

    /**
     * Test endpoint for Manager report export.
     */
    public function testReportsExport(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Authorized. You have permission to export reports to Excel/PDF.',
            'user' => $request->user()->fullName,
        ]);
    }

    /**
     * Test endpoint for Treasury scheduling.
     */
    public function testTreasurySchedule(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Authorized. You have permission to manage Treasury calendar visits.',
            'user' => $request->user()->fullName,
        ]);
    }

    /**
     * Test endpoint for Cashier scheduling.
     */
    public function testCashierSchedule(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Authorized. You have permission to manage Cashier calendar visits.',
            'user' => $request->user()->fullName,
        ]);
    }

    /**
     * Test endpoint for Treasury checklist execution.
     */
    public function testTreasuryChecklist(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Authorized. You have permission to execute Treasury audit checklists.',
            'user' => $request->user()->fullName,
        ]);
    }

    /**
     * Test endpoint for Cashier checklist execution.
     */
    public function testCashierChecklist(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Authorized. You have permission to execute Cashier audit checklists.',
            'user' => $request->user()->fullName,
        ]);
    }
}

