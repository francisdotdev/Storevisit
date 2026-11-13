<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Support both comma-separated and argument-separated roles
        $parsedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $sub) {
                $trimmed = trim($sub);
                if ($trimmed !== '') {
                    $parsedRoles[] = $trimmed;
                }
            }
        }

        if (!$user->hasRole($parsedRoles)) {
            return response()->json([
                'message' => 'Unauthorized. Your current role does not have access to this resource.',
                'required_roles' => $parsedRoles,
                'user_role' => $user->role?->name,
            ], 403);
        }

        return $next($request);
    }
}

