<?php

namespace App\Services;

class AuthorizationService
{
    // The 6 Approved BRD Roles
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_ASST_TREASURY_MANAGER = 'asst_treasury_manager';
    public const ROLE_ASST_CASHIER_MANAGER = 'asst_cashier_manager';
    public const ROLE_AREA_TREASURY_SUPERVISOR = 'area_treasury_supervisor';
    public const ROLE_AREA_CASHIER_SUPERVISOR = 'area_cashier_supervisor';

    // Audit Types
    public const AUDIT_TYPE_TREASURY = 'treasury';
    public const AUDIT_TYPE_CASHIER = 'cashier';

    /**
     * Map of roles to their explicit permissions per the BRD and consolidation.
     */
    protected static array $rolePermissions = [
        self::ROLE_ADMIN => [
            // User Administration
            'users.view',
            'users.manage',
            'users.approve',
            'users.toggle_status', // Backward-compatibility alias
            // Location & Store Administration
            'areas.manage',
            'stores.manage',
            'stores.view',
            // Monitoring & Reporting & Scheduling
            'calendar.view',
            'schedules.view',
            'schedules.manage',
            'schedules.manage_treasury',
            'schedules.manage_cashier',
            'checklist.manage.treasury',
            'checklist.manage.cashier',
            'checklists.manage_treasury',
            'checklists.manage_cashier',
            'observation.view',
            'observation.manage_status',
            'observations.view_all',
            'rating.view_monitoring',
            'rating.view',
            'ratings.view_all',
            'reports.view',
            'reports.export',
        ],

        self::ROLE_MANAGER => [
            // High-Level Monitoring & Data Review (Cross-Department)
            'calendar.view',
            'schedules.view',
            'stores.view',
            'observation.view',
            'observations.view_all',
            'rating.view_monitoring',
            'rating.view',
            'ratings.view_all',
            'reports.view',
            'reports.export',
        ],

        self::ROLE_ASST_TREASURY_MANAGER => [
            // Treasury Back-end & Administrative Setup
            'areas.manage',
            'stores.manage',
            'store_assignments.treasury',
            'stores.view',
            'calendar.view',
            'schedules.view',
            'schedules.manage',
            'schedules.manage_treasury',
            'checklist.manage.treasury',
            'checklists.manage_treasury',
            'observation.view',
            'observation.manage_status',
            'observations.view_all',
            'rating.view_monitoring',
            'rating.view',
            'ratings.view_all',
            'reports.view',
            'reports.export',
        ],

        self::ROLE_ASST_CASHIER_MANAGER => [
            // Cashier Back-end & Administrative Setup
            'areas.manage',
            'stores.manage',
            'store_assignments.cashier',
            'stores.view',
            'calendar.view',
            'schedules.view',
            'schedules.manage',
            'schedules.manage_cashier',
            'checklist.manage.cashier',
            'checklists.manage_cashier',
            'observation.view',
            'observation.manage_status',
            'observations.view_all',
            'rating.view_monitoring',
            'rating.view',
            'ratings.view_all',
            'reports.view',
            'reports.export',
        ],

        self::ROLE_AREA_TREASURY_SUPERVISOR => [
            // Field Execution - Treasury Audits (Store Scoped)
            'stores.view_assigned',
            'calendar.view_own',
            'schedules.view',
            'audit.conduct',
            'checklists.execute_treasury',
            'observation.create',
            'observation.update_own',
            'observation.view',
            'observations.log_treasury',
            'rating.view',
            'ratings.view_own',
            'reports.export_own', // TODO: confirm with requestor
        ],

        self::ROLE_AREA_CASHIER_SUPERVISOR => [
            // Field Execution - Cashier Audits (Store Scoped)
            'stores.view_assigned',
            'calendar.view_own',
            'schedules.view',
            'audit.conduct',
            'checklists.execute_cashier',
            'observation.create',
            'observation.update_own',
            'observation.view',
            'observations.log_cashier',
            'rating.view',
            'ratings.view_own',
            'reports.export_own', // TODO: confirm with requestor
        ],
    ];

    /**
     * Role details with department scope and descriptions.
     */
    protected static array $roleMetadata = [
        self::ROLE_ADMIN => [
            'display_name' => 'Administrator',
            'department' => 'System Administration',
            'scope' => 'system',
            'audit_type' => null,
            'description' => 'System access control, user approval, and high-level reporting.',
        ],
        self::ROLE_MANAGER => [
            'display_name' => 'Manager',
            'department' => 'Operations Management',
            'scope' => 'cross_department',
            'audit_type' => null,
            'description' => 'High-level monitoring, data review, variable filtering, and report exports.',
        ],
        self::ROLE_ASST_TREASURY_MANAGER => [
            'display_name' => 'Assistant Treasury Manager',
            'department' => 'Treasury',
            'scope' => 'department_admin',
            'audit_type' => self::AUDIT_TYPE_TREASURY,
            'description' => 'Area location setup, store assignments, scheduling, and checklists for Treasury.',
        ],
        self::ROLE_ASST_CASHIER_MANAGER => [
            'display_name' => 'Assistant Cashier Manager',
            'department' => 'Cashiering',
            'scope' => 'department_admin',
            'audit_type' => self::AUDIT_TYPE_CASHIER,
            'description' => 'Area location setup, store assignments, scheduling, and checklists for Cashier.',
        ],
        self::ROLE_AREA_TREASURY_SUPERVISOR => [
            'display_name' => 'Area Treasury Supervisor',
            'department' => 'Treasury',
            'scope' => 'field_auditor',
            'audit_type' => self::AUDIT_TYPE_TREASURY,
            'description' => 'Executes Treasury store checklists and logs Treasury observations for assigned stores.',
        ],
        self::ROLE_AREA_CASHIER_SUPERVISOR => [
            'display_name' => 'Area Cashier Supervisor',
            'department' => 'Cashiering',
            'scope' => 'field_auditor',
            'audit_type' => self::AUDIT_TYPE_CASHIER,
            'description' => 'Executes Cashier store checklists and logs Cashier observations for assigned stores.',
        ],
    ];

    /**
     * Get all permissions assigned to a role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return self::$rolePermissions[$role] ?? [];
    }

    /**
     * Check if a role possesses a specific permission.
     */
    public static function roleHasPermission(string $role, string $permission): bool
    {
        $perms = self::getPermissionsForRole($role);
        return in_array($permission, $perms, true);
    }

    /**
     * Get the audit type department scope for a role ('treasury', 'cashier', or null for cross-dept).
     */
    public static function getAuditTypeForRole(string $role): ?string
    {
        return self::$roleMetadata[$role]['audit_type'] ?? null;
    }

    /**
     * Get metadata for a role.
     */
    public static function getRoleMetadata(string $role): ?array
    {
        return self::$roleMetadata[$role] ?? null;
    }

    /**
     * Get the entire matrix with all permissions and descriptions.
     */
    public static function getFullMatrix(): array
    {
        $matrix = [];
        foreach (self::$roleMetadata as $roleKey => $meta) {
            $matrix[$roleKey] = array_merge($meta, [
                'role' => $roleKey,
                'permissions' => self::$rolePermissions[$roleKey] ?? [],
            ]);
        }
        return $matrix;
    }

    /**
     * List of all defined permissions.
     */
    public static function getAllPermissions(): array
    {
        $all = [];
        foreach (self::$rolePermissions as $perms) {
            $all = array_merge($all, $perms);
        }
        return array_values(array_unique($all));
    }
}
