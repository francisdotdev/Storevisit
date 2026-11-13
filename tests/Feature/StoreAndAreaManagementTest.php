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

class StoreAndAreaManagementTest extends TestCase
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

    protected Area $albayArea;
    protected Area $camSurArea;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
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
            ['display_name' => 'Area Treasury Supervisor', 'description' => 'Treasury Field Auditor']
        );

        $this->cashierSupRole = Role::firstOrCreate(
            ['name' => AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR],
            ['display_name' => 'Area Cashier Supervisor', 'description' => 'Cashier Field Auditor']
        );

        // 2. Users
        $this->adminUser = User::create([
            'employee_id' => 'ADMIN01',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->adminRole->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->managerUser = User::create([
            'employee_id' => 'MGR01',
            'first_name' => 'General',
            'last_name' => 'Manager',
            'email' => 'manager@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->managerRole->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->asstTreasuryMgrUser = User::create([
            'employee_id' => 'ATM01',
            'first_name' => 'Treasury',
            'last_name' => 'AsstMgr',
            'email' => 'atm@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->asstTreasuryMgrRole->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->asstCashierMgrUser = User::create([
            'employee_id' => 'ACM01',
            'first_name' => 'Cashier',
            'last_name' => 'AsstMgr',
            'email' => 'acm@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->asstCashierMgrRole->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->treasurySupUser = User::create([
            'employee_id' => 'ATS01',
            'first_name' => 'Treasury',
            'last_name' => 'Supervisor',
            'email' => 'ats@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->treasurySupRole->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->cashierSupUser = User::create([
            'employee_id' => 'ACS01',
            'first_name' => 'Cashier',
            'last_name' => 'Supervisor',
            'email' => 'acs@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->cashierSupRole->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        // 3. Areas
        $this->albayArea = Area::create([
            'area_code' => 'AREA-ALB',
            'area_name' => 'Albay Area',
            'is_active' => true,
            'created_by' => $this->asstTreasuryMgrUser->id,
        ]);

        $this->camSurArea = Area::create([
            'area_code' => 'AREA-CAS',
            'area_name' => 'Camarines Sur Area',
            'is_active' => true,
            'created_by' => $this->asstCashierMgrUser->id,
        ]);
    }

    public function test_authorized_user_can_view_areas_list_and_stats(): void
    {
        $response = $this->actingAs($this->asstTreasuryMgrUser)
            ->getJson('/api/areas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'areas' => ['data', 'total'],
                'stats' => ['total', 'active', 'inactive', 'total_stores'],
            ])
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.active', 2);
    }

    public function test_asst_treasury_manager_can_create_area_per_brd(): void
    {
        $payload = [
            'area_code' => 'AREA-SOR',
            'area_name' => 'Sorsogon Area',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->asstTreasuryMgrUser)
            ->postJson('/api/areas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('area.area_code', 'AREA-SOR')
            ->assertJsonPath('area.area_name', 'Sorsogon Area');

        $this->assertDatabaseHas('areas', [
            'area_code' => 'AREA-SOR',
            'created_by' => $this->asstTreasuryMgrUser->id,
        ]);
    }

    public function test_supervisor_cannot_create_area(): void
    {
        $payload = [
            'area_code' => 'AREA-UNAUTH',
            'area_name' => 'Unauthorized Area',
        ];

        $this->actingAs($this->treasurySupUser)
            ->postJson('/api/areas', $payload)
            ->assertStatus(403);
    }

    public function test_area_update_and_status_toggle(): void
    {
        $area = Area::create([
            'area_code' => 'AREA-TEST',
            'area_name' => 'Initial Name',
            'is_active' => true,
        ]);

        // Update
        $this->actingAs($this->adminUser)
            ->putJson("/api/areas/{$area->id}", [
                'area_code' => 'AREA-TEST-UPDATED',
                'area_name' => 'Updated Name',
                'is_active' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('area.area_code', 'AREA-TEST-UPDATED')
            ->assertJsonPath('area.area_name', 'Updated Name');

        // Toggle Status
        $this->actingAs($this->adminUser)
            ->postJson("/api/areas/{$area->id}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('area.is_active', false);

        $this->assertFalse($area->fresh()->is_active);
    }

    public function test_asst_manager_can_create_store_associated_with_area(): void
    {
        $payload = [
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-LEG-01',
            'store_name' => 'LCC Supermarket Legazpi',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->asstTreasuryMgrUser)
            ->postJson('/api/stores', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('store.store_code', 'STR-LEG-01')
            ->assertJsonPath('store.area_id', $this->albayArea->id);

        $this->assertDatabaseHas('stores', [
            'store_code' => 'STR-LEG-01',
            'area_id' => $this->albayArea->id,
            'created_by' => $this->asstTreasuryMgrUser->id,
        ]);
    }

    public function test_supervisor_cannot_create_or_modify_stores(): void
    {
        $store = Store::create([
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-TEST-01',
            'store_name' => 'Test Store',
            'is_active' => true,
        ]);

        // Attempt Create
        $this->actingAs($this->treasurySupUser)
            ->postJson('/api/stores', [
                'area_id' => $this->albayArea->id,
                'store_code' => 'STR-ILLEGAL',
                'store_name' => 'Illegal Store',
            ])
            ->assertStatus(403);

        // Attempt Edit
        $this->actingAs($this->treasurySupUser)
            ->putJson("/api/stores/{$store->id}", [
                'area_id' => $this->albayArea->id,
                'store_code' => 'STR-TEST-01',
                'store_name' => 'Hacked Store',
            ])
            ->assertStatus(403);

        // Attempt Toggle
        $this->actingAs($this->treasurySupUser)
            ->postJson("/api/stores/{$store->id}/toggle-status")
            ->assertStatus(403);
    }

    public function test_store_search_filtering_and_pagination(): void
    {
        Store::create([
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-ALB-01',
            'store_name' => 'LCC Express Daraga',
            'is_active' => true,
        ]);

        Store::create([
            'area_id' => $this->camSurArea->id,
            'store_code' => 'STR-CAS-01',
            'store_name' => 'LCC Department Store Naga',
            'is_active' => false,
        ]);

        // Search by keyword
        $resSearch = $this->actingAs($this->adminUser)
            ->getJson('/api/stores?search=Daraga');
        $resSearch->assertStatus(200)
            ->assertJsonPath('stores.total', 1)
            ->assertJsonPath('stores.data.0.store_code', 'STR-ALB-01');

        // Filter by Area
        $resArea = $this->actingAs($this->adminUser)
            ->getJson("/api/stores?area_id={$this->camSurArea->id}");
        $resArea->assertStatus(200)
            ->assertJsonPath('stores.total', 1)
            ->assertJsonPath('stores.data.0.store_code', 'STR-CAS-01');

        // Filter by Status
        $resActive = $this->actingAs($this->adminUser)
            ->getJson('/api/stores?status=active');
        $resActive->assertStatus(200)
            ->assertJsonPath('stores.total', 1);
    }

    public function test_supervisor_viewing_stores_is_strictly_scoped_to_assigned_stores(): void
    {
        $storeAssigned = Store::create([
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-ASSIGNED',
            'store_name' => 'Assigned Store',
            'is_active' => true,
        ]);

        $storeUnassigned = Store::create([
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-UNASSIGNED',
            'store_name' => 'Unassigned Store',
            'is_active' => true,
        ]);

        // Assign storeAssigned to Treasury Supervisor
        SupervisorStoreAssignment::create([
            'supervisor_id' => $this->treasurySupUser->id,
            'store_id' => $storeAssigned->id,
            'audit_type' => 'treasury',
            'assigned_by' => $this->asstTreasuryMgrUser->id,
        ]);

        // Treasury supervisor can only see 1 store
        $resSup = $this->actingAs($this->treasurySupUser)->getJson('/api/stores');
        $resSup->assertStatus(200)
            ->assertJsonPath('stores.total', 1)
            ->assertJsonPath('stores.data.0.id', $storeAssigned->id);

        // Supervisor cannot view details of unassigned store
        $this->actingAs($this->treasurySupUser)
            ->getJson("/api/stores/{$storeUnassigned->id}")
            ->assertStatus(403);

        // Supervisor can view details of assigned store
        $this->actingAs($this->treasurySupUser)
            ->getJson("/api/stores/{$storeAssigned->id}")
            ->assertStatus(200);

        // Admin sees both stores
        $resAdmin = $this->actingAs($this->adminUser)->getJson('/api/stores');
        $resAdmin->assertStatus(200)
            ->assertJsonPath('stores.total', 2);
    }

    public function test_asst_treasury_manager_can_assign_treasury_supervisor_to_store(): void
    {
        $store = Store::create([
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-LEG-MAIN',
            'store_name' => 'Legazpi Main',
            'is_active' => true,
        ]);

        $payload = [
            'supervisor_id' => $this->treasurySupUser->id,
            'audit_type' => 'treasury',
        ];

        $response = $this->actingAs($this->asstTreasuryMgrUser)
            ->postJson("/api/stores/{$store->id}/assign-supervisor", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('assignment.supervisor_id', $this->treasurySupUser->id)
            ->assertJsonPath('assignment.audit_type', 'treasury');

        $this->assertDatabaseHas('supervisor_store_assignments', [
            'store_id' => $store->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'audit_type' => 'treasury',
            'assigned_by' => $this->asstTreasuryMgrUser->id,
        ]);
    }

    public function test_department_cross_assignment_is_forbidden(): void
    {
        $store = Store::create([
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-CROSS-TEST',
            'store_name' => 'Cross Test Store',
            'is_active' => true,
        ]);

        // 1. Asst Treasury Manager trying to assign with audit_type = 'cashier' is forbidden
        $this->actingAs($this->asstTreasuryMgrUser)
            ->postJson("/api/stores/{$store->id}/assign-supervisor", [
                'supervisor_id' => $this->cashierSupUser->id,
                'audit_type' => 'cashier',
            ])
            ->assertStatus(403);

        // 2. Asst Cashier Manager trying to assign with audit_type = 'treasury' is forbidden
        $this->actingAs($this->asstCashierMgrUser)
            ->postJson("/api/stores/{$store->id}/assign-supervisor", [
                'supervisor_id' => $this->treasurySupUser->id,
                'audit_type' => 'treasury',
            ])
            ->assertStatus(403);

        // 3. Trying to assign a Cashier Supervisor to a Treasury audit type returns 422
        $this->actingAs($this->adminUser)
            ->postJson("/api/stores/{$store->id}/assign-supervisor", [
                'supervisor_id' => $this->cashierSupUser->id,
                'audit_type' => 'treasury',
            ])
            ->assertStatus(422);
    }

    public function test_removing_supervisor_assignment(): void
    {
        $store = Store::create([
            'area_id' => $this->albayArea->id,
            'store_code' => 'STR-DEL-ASG',
            'store_name' => 'Delete Assignment Store',
            'is_active' => true,
        ]);

        $assignment = SupervisorStoreAssignment::create([
            'supervisor_id' => $this->treasurySupUser->id,
            'store_id' => $store->id,
            'audit_type' => 'treasury',
            'assigned_by' => $this->asstTreasuryMgrUser->id,
        ]);

        $this->actingAs($this->asstTreasuryMgrUser)
            ->deleteJson("/api/stores/{$store->id}/assignments/{$assignment->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('supervisor_store_assignments', [
            'id' => $assignment->id,
        ]);
    }
}

