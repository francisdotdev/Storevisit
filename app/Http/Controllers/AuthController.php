<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Get list of roles for registration dropdown.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::orderBy('id')->get(['id', 'name', 'display_name', 'description']);

        return response()->json($roles);
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', 'unique:users,employee_id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'branch_code' => ['nullable', 'string', 'max:50'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'employee_id' => trim($validated['employee_id']),
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
            'email' => strtolower(trim($validated['email'])),
            'branch_code' => $validated['branch_code'] ? trim($validated['branch_code']) : null,
            'role_id' => $validated['role_id'],
            'password' => Hash::make($validated['password']),
            'status' => 'pending', // Per BRD: New registrations must be approved by Admin
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return response()->json([
            'message' => 'Registration submitted successfully. Your account is pending Administrator approval before you can log in.',
            'user' => $user->load('role'),
        ], 201);
    }

    /**
     * Handle user login with status validation.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'], // Supports employee_id or email
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $loginInput = trim($validated['login']);

        // Check by employee_id or email
        $user = User::where('employee_id', $loginInput)
            ->orWhere('email', strtolower($loginInput))
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials do not match our records.'],
            ]);
        }

        // BRD Rule: Account status enforcement
        if ($user->status === 'pending') {
            return response()->json([
                'message' => 'Your account registration is pending approval by the Administrator. Please wait for activation.',
                'status' => 'pending',
            ], 403);
        }

        if ($user->status === 'disabled') {
            return response()->json([
                'message' => 'Your account has been disabled. System access has been revoked. Please contact your Administrator.',
                'status' => 'disabled',
            ], 403);
        }

        // Log the user into session
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->load('role'),
            'permissions' => $user->getPermissions(),
            'audit_type' => $user->getAuditType(),
        ]);
    }

    /**
     * Get the current authenticated user context.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()?->load('role');

        return response()->json([
            'user' => $user,
            'permissions' => $user ? $user->getPermissions() : [],
            'audit_type' => $user ? $user->getAuditType() : null,
        ]);
    }

    /**
     * Log out current user session.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}

