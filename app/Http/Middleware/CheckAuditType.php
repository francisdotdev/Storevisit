<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuditType
{
    /**
     * Handle an incoming request ensuring user has access to the specified audit type (treasury or cashier).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $expectedType
     */
    public function handle(Request $request, Closure $next, ?string $expectedType = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Determine audit type from middleware argument or request parameter
        $auditType = $expectedType ?: $request->input('audit_type') ?: $request->route('audit_type');

        if ($auditType && !$user->canAccessAuditType($auditType)) {
            return response()->json([
                'message' => "Unauthorized. Your role ({$user->role?->display_name}) cannot access {$auditType} department data.",
                'required_audit_type' => $auditType,
                'user_audit_type' => $user->getAuditType(),
            ], 403);
        }

        return $next($request);
    }
}

