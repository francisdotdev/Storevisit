<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users with filters, search, and summary stats.
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        // Enforce admin-only access
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Administrator access required.'], 403);
        }

        $query = User::with(['role', 'approver:id,first_name,last_name,employee_id']);

        // Filter by status if provided (active, pending, disabled, or all)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter by role if provided
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        // Search by keyword (employee ID, name, email, branch code)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('branch_code', 'like', "%{$search}%");
            });
        }

        // Default sort by created_at desc
        $sortBy = in_array($request->input('sort_by'), ['id', 'employee_id', 'first_name', 'last_name', 'created_at', 'status'])
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);
        $users = $query->paginate($perPage);

        // Calculate summary counts for cards
        $stats = [
            'total' => User::count(),
            'pending' => User::where('status', 'pending')->count(),
            'active' => User::where('status', 'active')->count(),
            'disabled' => User::where('status', 'disabled')->count(),
        ];

        return response()->json([
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created user account by Administrator.
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Administrator access required.'], 403);
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', 'unique:users,employee_id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_code' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'pending', 'disabled'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $isActive = $validated['status'] === 'active';

        $user = User::create([
            'employee_id' => trim($validated['employee_id']),
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
            'email' => strtolower(trim($validated['email'])),
            'role_id' => $validated['role_id'],
            'branch_code' => $validated['branch_code'] ? trim($validated['branch_code']) : null,
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
            'approved_by' => $isActive ? $currentUser->id : null,
            'approved_at' => $isActive ? now() : null,
        ]);

        return response()->json([
            'message' => 'User account created successfully.',
            'user' => $user->load(['role', 'approver:id,first_name,last_name,employee_id']),
        ], 201);
    }

    /**
     * Display the specified user details.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Administrator access required.'], 403);
        }

        return response()->json([
            'user' => $user->load(['role', 'approver:id,first_name,last_name,employee_id']),
        ]);
    }

    /**
     * Update an existing user account.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Administrator access required.'], 403);
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($user->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_code' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'pending', 'disabled'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Prevent self-lockout or self-deactivation
        if (isset($validated['status']) && $user->id === $currentUser->id && $validated['status'] !== 'active') {
            return response()->json([
                'message' => 'You cannot deactivate or demote your own active administrator account.',
            ], 422);
        }

        $user->employee_id = trim($validated['employee_id']);
        $user->first_name = trim($validated['first_name']);
        $user->last_name = trim($validated['last_name']);
        $user->email = strtolower(trim($validated['email']));
        $user->role_id = $validated['role_id'];
        $user->branch_code = $validated['branch_code'] ? trim($validated['branch_code']) : null;

        if (isset($validated['status'])) {
            $user->status = $validated['status'];
            if ($validated['status'] === 'active' && !$user->approved_at) {
                $user->approved_by = $currentUser->id;
                $user->approved_at = now();
            }
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'User account updated successfully.',
            'user' => $user->fresh()->load(['role', 'approver:id,first_name,last_name,employee_id']),
        ]);
    }

    /**
     * Approve a pending user account.
     */
    public function approve(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Administrator access required.'], 403);
        }

        if ($user->status === 'active') {
            return response()->json([
                'message' => 'User account is already active.',
                'user' => $user->load('role'),
            ]);
        }

        $user->update([
            'status' => 'active',
            'approved_by' => $currentUser->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => "User account for {$user->full_name} has been approved and activated.",
            'user' => $user->fresh()->load(['role', 'approver:id,first_name,last_name,employee_id']),
        ]);
    }

    /**
     * Toggle user status between active and disabled.
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Administrator access required.'], 403);
        }

        if ($user->id === $currentUser->id) {
            return response()->json([
                'message' => 'You cannot deactivate your own administrator account.',
            ], 422);
        }

        $newStatus = $user->status === 'active' ? 'disabled' : 'active';

        $data = ['status' => $newStatus];
        if ($newStatus === 'active' && !$user->approved_at) {
            $data['approved_by'] = $currentUser->id;
            $data['approved_at'] = now();
        }

        $user->update($data);

        $actionText = $newStatus === 'active' ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "User account for {$user->full_name} has been {$actionText}.",
            'user' => $user->fresh()->load(['role', 'approver:id,first_name,last_name,employee_id']),
        ]);
    }
}

