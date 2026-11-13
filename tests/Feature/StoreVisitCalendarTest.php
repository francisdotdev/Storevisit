<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreVisitSchedule;
use App\Models\User;
use App\Services\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreVisitCalendarTest extends TestCase
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
            'last_name' => 'AsstManager',
            'email' => 'atm@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->asstTreasuryMgrRole->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->asstCashierMgrUser = User::create([
            'employee_id' => 'ACM01',
            'first_name' => 'Cashier',
            'last_name' => 'AsstManager',
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

        // 3. Area and Stores
        $this->area = Area::create([
            'area_code' => 'AREA-ALB',
            'area_name' => 'Albay Area',
            'is_active' => true,
        ]);

        $this->storeA = Store::create([
            'area_id' => $this->area->id,
            'store_code' => 'STR-LEG-01',
            'store_name' => 'LCC Legazpi Main',
            'is_active' => true,
        ]);

        $this->storeB = Store::create([
            'area_id' => $this->area->id,
            'store_code' => 'STR-DAR-01',
            'store_name' => 'LCC Daraga Express',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_view_calendar_month_grid_events(): void
    {
        StoreVisitSchedule::create([
            'schedule_code' => 'SCH-TEST-001',
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'scheduled_by' => $this->asstTreasuryMgrUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => Carbon::now()->startOfMonth()->addDays(5)->toDateString(),
            'status' => 'scheduled',
        ]);

        $currentMonth = Carbon::now()->format('Y-m');

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/schedules?all=1&month={$currentMonth}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'events' => [
                    '*' => [
                        'id',
                        'schedule_code',
                        'scheduled_date',
                        'audit_type',
                        'status',
                        'store',
                        'supervisor',
                    ],
                ],
                'stats' => ['total_visits', 'scheduled', 'in_progress', 'completed', 'cancelled'],
            ])
            ->assertJsonPath('stats.total_visits', 1);
    }

    public function test_authenticated_user_can_view_calendar_list_view_with_brd_fields(): void
    {
        $visit = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-BRD-001',
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'scheduled_by' => $this->asstTreasuryMgrUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-15',
            'started_at' => '2026-11-15 08:30:00',
            'completed_at' => '2026-11-15 17:00:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->managerUser)
            ->getJson('/api/schedules?month=2026-11');

        $response->assertStatus(200)
            ->assertJsonPath('schedules.data.0.scheduled_date', '2026-11-15')
            ->assertJsonPath('schedules.data.0.store.store_code', 'STR-LEG-01')
            ->assertJsonPath('schedules.data.0.started_at', '2026-11-15T08:30:00.000000Z')
            ->assertJsonPath('schedules.data.0.completed_at', '2026-11-15T17:00:00.000000Z')
            ->assertJsonPath('schedules.data.0.status', 'completed');
    }

    public function test_asst_treasury_manager_can_schedule_treasury_visit_per_brd(): void
    {
        $payload = [
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-20',
            'notes' => 'Conduct Treasury audit.',
        ];

        $response = $this->actingAs($this->asstTreasuryMgrUser)
            ->postJson('/api/schedules', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('schedule.store_id', $this->storeA->id)
            ->assertJsonPath('schedule.supervisor_id', $this->treasurySupUser->id)
            ->assertJsonPath('schedule.audit_type', 'treasury')
            ->assertJsonPath('schedule.status', 'scheduled');

        $this->assertDatabaseHas('store_visit_schedules', [
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'audit_type' => 'treasury',
            'scheduled_by' => $this->asstTreasuryMgrUser->id,
        ]);
    }

    public function test_asst_cashier_manager_can_schedule_cashier_visit_per_brd(): void
    {
        $payload = [
            'store_id' => $this->storeB->id,
            'supervisor_id' => $this->cashierSupUser->id,
            'audit_type' => 'cashier',
            'scheduled_date' => '2026-11-22',
        ];

        $response = $this->actingAs($this->asstCashierMgrUser)
            ->postJson('/api/schedules', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('schedule.audit_type', 'cashier');

        $this->assertDatabaseHas('store_visit_schedules', [
            'store_id' => $this->storeB->id,
            'supervisor_id' => $this->cashierSupUser->id,
            'audit_type' => 'cashier',
            'scheduled_by' => $this->asstCashierMgrUser->id,
        ]);
    }

    public function test_asst_treasury_manager_cannot_schedule_cashier_visit(): void
    {
        $payload = [
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->cashierSupUser->id,
            'audit_type' => 'cashier',
            'scheduled_date' => '2026-11-20',
        ];

        $this->actingAs($this->asstTreasuryMgrUser)
            ->postJson('/api/schedules', $payload)
            ->assertStatus(403);
    }

    public function test_asst_cashier_manager_cannot_schedule_treasury_visit(): void
    {
        $payload = [
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-20',
        ];

        $this->actingAs($this->asstCashierMgrUser)
            ->postJson('/api/schedules', $payload)
            ->assertStatus(403);
    }

    public function test_supervisor_cannot_create_schedules(): void
    {
        $payload = [
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-20',
        ];

        $this->actingAs($this->treasurySupUser)
            ->postJson('/api/schedules', $payload)
            ->assertStatus(403);
    }

    public function test_supervisor_viewing_is_strictly_scoped_to_assigned_schedules(): void
    {
        // Schedule for Treasury Supervisor
        $schA = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-MINE',
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'scheduled_by' => $this->asstTreasuryMgrUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-10',
            'status' => 'scheduled',
        ]);

        // Schedule for Cashier Supervisor
        $schB = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-OTHER',
            'store_id' => $this->storeB->id,
            'supervisor_id' => $this->cashierSupUser->id,
            'scheduled_by' => $this->asstCashierMgrUser->id,
            'audit_type' => 'cashier',
            'scheduled_date' => '2026-11-10',
            'status' => 'scheduled',
        ]);

        // Treasury supervisor sees only their own schedule
        $resSup = $this->actingAs($this->treasurySupUser)->getJson('/api/schedules?month=2026-11');
        $resSup->assertStatus(200)
            ->assertJsonPath('schedules.total', 1)
            ->assertJsonPath('schedules.data.0.id', $schA->id);

        // Treasury supervisor blocked from viewing Cashier supervisor's schedule details
        $this->actingAs($this->treasurySupUser)
            ->getJson("/api/schedules/{$schB->id}")
            ->assertStatus(403);

        // Treasury supervisor can view their own schedule details
        $this->actingAs($this->treasurySupUser)
            ->getJson("/api/schedules/{$schA->id}")
            ->assertStatus(200);

        // Manager sees both schedules
        $resMgr = $this->actingAs($this->managerUser)->getJson('/api/schedules?month=2026-11');
        $resMgr->assertStatus(200)
            ->assertJsonPath('schedules.total', 2);
    }

    public function test_supervisor_can_start_and_complete_assigned_visit(): void
    {
        $schedule = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-EXEC-01',
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'scheduled_by' => $this->asstTreasuryMgrUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-11',
            'status' => 'scheduled',
        ]);

        // 1. Start Visit (in_progress)
        $resStart = $this->actingAs($this->treasurySupUser)
            ->postJson("/api/schedules/{$schedule->id}/status", [
                'status' => 'in_progress',
            ]);

        $resStart->assertStatus(200)
            ->assertJsonPath('schedule.status', 'in_progress');

        $this->assertNotNull($schedule->fresh()->started_at);

        // 2. Complete Visit (completed)
        $resEnd = $this->actingAs($this->treasurySupUser)
            ->postJson("/api/schedules/{$schedule->id}/status", [
                'status' => 'completed',
            ]);

        $resEnd->assertStatus(200)
            ->assertJsonPath('schedule.status', 'completed');

        $this->assertNotNull($schedule->fresh()->completed_at);
    }

    public function test_other_supervisor_cannot_update_visit_status(): void
    {
        $schedule = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-FORBIDDEN-01',
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'scheduled_by' => $this->asstTreasuryMgrUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-11',
            'status' => 'scheduled',
        ]);

        // Cashier supervisor trying to start Treasury supervisor's visit
        $this->actingAs($this->cashierSupUser)
            ->postJson("/api/schedules/{$schedule->id}/status", [
                'status' => 'in_progress',
            ])
            ->assertStatus(403);
    }

    public function test_schedule_update_and_cancellation(): void
    {
        $schedule = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-EDIT-01',
            'store_id' => $this->storeA->id,
            'supervisor_id' => $this->treasurySupUser->id,
            'scheduled_by' => $this->asstTreasuryMgrUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-15',
            'status' => 'scheduled',
        ]);

        // Update Date & Notes
        $this->actingAs($this->asstTreasuryMgrUser)
            ->putJson("/api/schedules/{$schedule->id}", [
                'store_id' => $this->storeB->id,
                'supervisor_id' => $this->treasurySupUser->id,
                'scheduled_date' => '2026-11-18',
                'notes' => 'Updated notes.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('schedule.scheduled_date', '2026-11-18')
            ->assertJsonPath('schedule.store_id', $this->storeB->id);

        // Delete / Cancel Visit
        $this->actingAs($this->asstTreasuryMgrUser)
            ->deleteJson("/api/schedules/{$schedule->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('store_visit_schedules', [
            'id' => $schedule->id,
        ]);
    }
}

