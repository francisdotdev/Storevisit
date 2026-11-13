<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Role;
use App\Models\Store;
use App\Models\SupervisorStoreAssignment;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $managerRole;
    protected Role $asstTreasuryMgrRole;
    protected Role $asstCashierMgrRole;
    protected Role $treasurySupRole;
    protected Role $cashierSupRole;

    protected User $adminUser;
    protected User $managerUser;
    protected User $asstTreasuryMgrUser;
    protected User $asstCashierMgrUser;
    protected User $treasurySupUser;
    protected User $cashierSupUser;

    protected Area $area;
    protected Store $storeA;
    protected Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create the 6 BRD Roles
        $this->adminRole = Role::firstOrCreate(
            ['name' => AuthorizationService::ROLE_ADMIN],
            ['display_name' => 'Administrator', 'description' => 'System Administrator']
        );

        $this->managerRole = Role::firstOrCreate(
            ['name' => AuthorizationService::ROLE_MANAGER],
            ['display_name' => 'Manager', 'description' => 'Operations Manager']
        );

        $this->asstTreasuryMgrRole = Role::firstOrCreate(
            ['name' => AuthorizationService::ROLE_ASST_TREASURY_MANAGER],
            ['display_name' => 'Assistant Treasury Manager', 'description' => 'Treasury Back-end Setup']
        );

        $this->asstCashierMgrRole = Role::firstOrCreate(
            ['name' => AuthorizationService::ROLE_ASST_CASHIER_MANAGER],
            ['display_name' => 'Assistant Cashier Manager', 'description' => 'Cashier Back-end Setup']
        );

        $this->treasurySupRole = Role::firstOrCreate(
            ['name' => AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR],
            ['display_name' => 'Area Treasury Supervisor', 'description' => 'Field Treasury Auditor']
        );

        $this->cashierSupRole = Role::firstOrCreate(
            ['name' => AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR],
            ['display_name' => 'Area Cashier Supervisor', 'description' => 'Field Cashier Auditor']
        );

        // 2. Create Users for each Role
        $this->adminUser = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'email' => 'admin@lcc.com.ph',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->managerUser = User::factory()->create([
            'role_id' => $this->managerRole->id,
            'email' => 'manager@lcc.com.ph',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->asstTreasuryMgrUser = User::factory()->create([
            'role_id' => $this->asstTreasuryMgrRole->id,
            'email' => 'asst_treasury@lcc.com.ph',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->asstCashierMgrUser = User::factory()->create([
            'role_id' => $this->asstCashierMgrRole->id,
            'email' => 'asst_cashier@lcc.com.ph',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->treasurySupUser = User::factory()->create([
            'role_id' => $this->treasurySupRole->id,
            'email' => 'sup_treasury@lcc.com.ph',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->cashierSupUser = User::factory()->create([
            'role_id' => $this->cashierSupRole->id,
            'email' => 'sup_cashier@lcc.com.ph',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        // 3. Create Sample Area & Stores
        $this->area = Area::create([
            'area_name' => 'Albay Operations Area',
            'area_code' => 'AREA-ALB-01',
            'is_active' => true,
        ]);

        $this->storeA = Store::create([
            'store_code' => 'STR-LEG-01',
            'store_name' => 'LCC Legazpi Central',
            'area_id' => $this->area->id,
            'is_active' => true,
        ]);

        $this->storeB = Store::create([
            'store_code' => 'STR-DAR-01',
            'store_name' => 'LCC Daraga Express',
            'area_id' => $this->area->id,
            'is_active' => true,
        ]);
    }

    /**
     * RBAC Matrix and probe routes must NOT be registered in non-local environments (e.g. testing or production).
     */
    public function test_rbac_matrix_and_probe_endpoints_are_not_registered_in_non_local_environment(): void
    {
        $this->actingAs($this->adminUser)
            ->getJson('/api/rbac/matrix')
            ->assertStatus(404);

        $this->actingAs($this->adminUser)
            ->getJson('/api/rbac/test/reports-view')
            ->assertStatus(404);
    }

    public function test_admin_has_user_management_access_and_cannot_access_supervisor_field_actions(): void
    {
        // Admin CAN access user management
        $this->actingAs($this->adminUser)
            ->getJson('/api/admin/users')
            ->assertStatus(200);

        // Admin CAN view reports
        $this->actingAs($this->adminUser)
            ->getJson('/api/reports/calendar-visits')
            ->assertStatus(200);

        // Admin does not have supervisor field execution permission in authorization service
        $this->assertTrue($this->adminUser->hasPermission('users.manage'));
        $this->assertTrue($this->adminUser->hasPermission('reports.view'));
        $this->assertFalse($this->adminUser->hasPermission('audit.conduct'));
    }

    public function test_manager_can_view_and_export_reports_but_cannot_access_user_management(): void
    {
        // Manager CAN view reports
        $this->actingAs($this->managerUser)
            ->getJson('/api/reports/calendar-visits')
            ->assertStatus(200);

        // Manager CAN export reports
        $this->actingAs($this->managerUser)
            ->getJson('/api/reports/calendar-visits/export/csv')
            ->assertStatus(200);

        // Manager CANNOT access user management
        $this->actingAs($this->managerUser)
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }

    public function test_asst_treasury_manager_can_schedule_treasury_visits_but_cannot_schedule_cashier(): void
    {
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_TREASURY_MANAGER, 'schedules.manage_treasury'));
        $this->assertFalse(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_TREASURY_MANAGER, 'schedules.manage_cashier'));

        // Asst Treasury Manager CANNOT access User Management
        $this->actingAs($this->asstTreasuryMgrUser)
            ->getJson('/api/admin/users')
            ->assertStatus(403);

        // Asst Treasury Manager CAN view and export reports (scoped to treasury)
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_TREASURY_MANAGER, 'reports.view'));
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_TREASURY_MANAGER, 'reports.export'));
    }

    public function test_asst_cashier_manager_can_schedule_cashier_visits_but_cannot_schedule_treasury(): void
    {
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_CASHIER_MANAGER, 'schedules.manage_cashier'));
        $this->assertFalse(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_CASHIER_MANAGER, 'schedules.manage_treasury'));

        // Asst Cashier Manager CANNOT access User Management
        $this->actingAs($this->asstCashierMgrUser)
            ->getJson('/api/admin/users')
            ->assertStatus(403);

        // Asst Cashier Manager CAN view and export reports (scoped to cashier)
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_CASHIER_MANAGER, 'reports.view'));
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_ASST_CASHIER_MANAGER, 'reports.export'));
    }

    public function test_area_treasury_supervisor_can_execute_treasury_checklists_only(): void
    {
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR, 'checklists.execute_treasury'));
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR, 'audit.conduct'));
        $this->assertFalse(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR, 'checklists.execute_cashier'));
        $this->assertFalse(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR, 'schedules.manage_treasury'));
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR, 'reports.export_own'));
    }

    public function test_area_cashier_supervisor_can_execute_cashier_checklists_only(): void
    {
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR, 'checklists.execute_cashier'));
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR, 'audit.conduct'));
        $this->assertFalse(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR, 'checklists.execute_treasury'));
        $this->assertFalse(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR, 'schedules.manage_cashier'));
        $this->assertTrue(AuthorizationService::roleHasPermission(AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR, 'reports.export_own'));
    }

    public function test_area_supervisor_store_scoping_grants_access_only_to_assigned_stores(): void
    {
        // Assign Store A to Treasury Supervisor for 'treasury'
        SupervisorStoreAssignment::create([
            'supervisor_id' => $this->treasurySupUser->id,
            'store_id' => $this->storeA->id,
            'audit_type' => 'treasury',
            'assigned_by' => $this->asstTreasuryMgrUser->id,
        ]);

        // 1. Treasury Supervisor checking Store A (assigned) -> Authorized (200)
        $this->actingAs($this->treasurySupUser)
            ->postJson('/api/rbac/verify-store-access', [
                'store_id' => $this->storeA->id,
                'audit_type' => 'treasury',
            ])
            ->assertStatus(200)
            ->assertJsonPath('authorized', true);

        // 2. Treasury Supervisor checking Store B (unassigned) -> Denied (403)
        $this->actingAs($this->treasurySupUser)
            ->postJson('/api/rbac/verify-store-access', [
                'store_id' => $this->storeB->id,
                'audit_type' => 'treasury',
            ])
            ->assertStatus(403)
            ->assertJsonPath('authorized', false);

        // 3. Treasury Supervisor checking Store A but with Cashier type -> Denied (403)
        $this->actingAs($this->treasurySupUser)
            ->postJson('/api/rbac/verify-store-access', [
                'store_id' => $this->storeA->id,
                'audit_type' => 'cashier',
            ])
            ->assertStatus(403)
            ->assertJsonPath('authorized', false);
    }

    public function test_admin_and_manager_have_global_store_scoping_access(): void
    {
        // Admin can access Store B even without assignment in table
        $this->actingAs($this->adminUser)
            ->postJson('/api/rbac/verify-store-access', [
                'store_id' => $this->storeB->id,
                'audit_type' => 'treasury',
            ])
            ->assertStatus(200)
            ->assertJsonPath('authorized', true);

        // Manager can access Store B even without assignment in table
        $this->actingAs($this->managerUser)
            ->postJson('/api/rbac/verify-store-access', [
                'store_id' => $this->storeB->id,
                'audit_type' => 'cashier',
            ])
            ->assertStatus(200)
            ->assertJsonPath('authorized', true);
    }

    public function test_unauthenticated_requests_are_rejected_with_401(): void
    {
        $this->getJson('/api/rbac/my-permissions')
            ->assertStatus(401);

        $this->getJson('/api/reports/calendar-visits')
            ->assertStatus(401);
    }
}
