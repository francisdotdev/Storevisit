<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AuditObservation;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditObservationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $managerUser;
    protected User $treasurySup;
    protected User $cashierSup;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ADMIN], ['display_name' => 'Admin']);
        $mgrRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_MANAGER], ['display_name' => 'Manager']);
        $treasurySupRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR], ['display_name' => 'Treasury Sup']);
        $cashierSupRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR], ['display_name' => 'Cashier Sup']);

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id, 'status' => 'active']);
        $this->managerUser = User::factory()->create(['role_id' => $mgrRole->id, 'status' => 'active']);
        $this->treasurySup = User::factory()->create(['role_id' => $treasurySupRole->id, 'status' => 'active']);
        $this->cashierSup = User::factory()->create(['role_id' => $cashierSupRole->id, 'status' => 'active']);

        $area = Area::create(['area_name' => 'Albay', 'area_code' => 'ALB', 'is_active' => true]);
        $this->store = Store::create(['store_name' => 'Legazpi Store', 'store_code' => 'LEG-01', 'area_id' => $area->id, 'is_active' => true]);
    }

    public function test_creating_observation_derives_audit_type_from_role(): void
    {
        $response = $this->actingAs($this->treasurySup)->postJson('/api/observations', [
            'store_id' => $this->store->id,
            'personnel_name' => 'Maria Santos',
            'issue_description' => 'Discrepancy in petty cash box count',
            'action_required' => 'Reconcile receipts by end of shift',
            'target_escalation_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.audit_type', 'treasury')
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('audit_observations', [
            'store_id' => $this->store->id,
            'audit_type' => 'treasury',
            'personnel_name' => 'Maria Santos',
        ]);
    }

    public function test_resolving_observation_requires_resolution_details(): void
    {
        $obs = AuditObservation::create([
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'audit_type' => 'treasury',
            'personnel_name' => 'Maria Santos',
            'issue_description' => 'Unreconciled receipt',
            'action_required' => 'Submit receipt',
            'target_escalation_date' => now()->addDays(2)->toDateString(),
            'status' => 'open',
        ]);

        // Fail without details
        $this->actingAs($this->treasurySup)->postJson("/api/observations/{$obs->id}/resolve", [
            'resolution_details' => '',
        ])->assertStatus(422);

        // Succeed with details
        $this->actingAs($this->treasurySup)->postJson("/api/observations/{$obs->id}/resolve", [
            'resolution_details' => 'Receipt submitted and validated with central cashier.',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'resolved');

        $this->assertEquals('resolved', $obs->fresh()->status);
        $this->assertNotNull($obs->fresh()->date_resolved);
    }

    public function test_reopening_requires_mandatory_reason(): void
    {
        $obs = AuditObservation::create([
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'audit_type' => 'treasury',
            'personnel_name' => 'Maria Santos',
            'issue_description' => 'Issue',
            'action_required' => 'Action',
            'target_escalation_date' => now()->toDateString(),
            'status' => 'resolved',
            'date_resolved' => now()->toDateString(),
        ]);

        $this->actingAs($this->managerUser)->postJson("/api/observations/{$obs->id}/reopen", [
            'reopen_reason' => 'Audit team re-checked and discrepancy recurred.',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.reopen_reason', 'Audit team re-checked and discrepancy recurred.');

        $this->assertEquals('open', $obs->fresh()->status);
    }

    public function test_closing_observation_restricted_to_managers_and_admins(): void
    {
        $obs = AuditObservation::create([
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'audit_type' => 'treasury',
            'personnel_name' => 'Maria Santos',
            'issue_description' => 'Issue',
            'action_required' => 'Action',
            'target_escalation_date' => now()->toDateString(),
            'status' => 'resolved',
            'date_resolved' => now()->toDateString(),
        ]);

        // Supervisor cannot close
        $this->actingAs($this->treasurySup)->postJson("/api/observations/{$obs->id}/close")
            ->assertStatus(403);

        // Manager CAN close
        $this->actingAs($this->managerUser)->postJson("/api/observations/{$obs->id}/close")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');

        $this->assertNotNull($obs->fresh()->closed_at);
    }
}

