<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AuditChecklist;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreVisitSchedule;
use App\Models\User;
use App\Models\VisitAudit;
use App\Services\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $asstTreasuryMgr;
    protected User $asstCashierMgr;
    protected User $treasurySup;
    protected Store $store;
    protected StoreVisitSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ADMIN], ['display_name' => 'Admin']);
        $asstTreasuryRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ASST_TREASURY_MANAGER], ['display_name' => 'Asst Treasury Mgr']);
        $asstCashierRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ASST_CASHIER_MANAGER], ['display_name' => 'Asst Cashier Mgr']);
        $treasurySupRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR], ['display_name' => 'Treasury Sup']);

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id, 'status' => 'active']);
        $this->asstTreasuryMgr = User::factory()->create(['role_id' => $asstTreasuryRole->id, 'status' => 'active']);
        $this->asstCashierMgr = User::factory()->create(['role_id' => $asstCashierRole->id, 'status' => 'active']);
        $this->treasurySup = User::factory()->create(['role_id' => $treasurySupRole->id, 'status' => 'active']);

        $area = Area::create(['area_name' => 'Main Area', 'area_code' => 'AR-01', 'is_active' => true]);
        $this->store = Store::create(['store_name' => 'Store 1', 'store_code' => 'ST-01', 'area_id' => $area->id, 'is_active' => true]);

        $this->schedule = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-001',
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'scheduled_by' => $this->asstTreasuryMgr->id,
            'audit_type' => 'treasury',
            'scheduled_date' => Carbon::now('Asia/Manila')->toDateString(),
            'status' => 'scheduled',
        ]);
    }

    public function test_asst_treasury_manager_can_create_treasury_template(): void
    {
        $response = $this->actingAs($this->asstTreasuryMgr)->postJson('/api/checklists', [
            'audit_type' => 'treasury',
            'category' => 'Cash Handling',
            'activity' => 'Check safe vault dual combination',
            'weight' => 10.0,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.category', 'Cash Handling');

        $this->assertDatabaseHas('audit_checklists', [
            'audit_type' => 'treasury',
            'category' => 'Cash Handling',
            'activity' => 'Check safe vault dual combination',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => AuditChecklist::class,
            'action' => 'created',
            'user_id' => $this->asstTreasuryMgr->id,
        ]);
    }

    public function test_asst_cashier_manager_cannot_create_treasury_template(): void
    {
        $this->actingAs($this->asstCashierMgr)->postJson('/api/checklists', [
            'audit_type' => 'treasury',
            'category' => 'Cash Handling',
            'activity' => 'Check safe vault',
            'weight' => 10.0,
        ])->assertStatus(403);
    }

    public function test_starting_audit_creates_snapshots_from_active_templates(): void
    {
        AuditChecklist::create([
            'audit_type' => 'treasury',
            'category' => 'Vault Compliance',
            'activity' => 'Verify vault key custodianship',
            'weight' => 15.0,
            'is_active' => true,
            'created_by' => $this->asstTreasuryMgr->id,
        ]);

        $response = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/start/{$this->schedule->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(1, 'data.answers');

        $this->assertDatabaseHas('visit_audits', [
            'store_visit_schedule_id' => $this->schedule->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('audit_checklist_answers', [
            'category' => 'Vault Compliance',
            'activity' => 'Verify vault key custodianship',
            'weight' => 15.0,
        ]);
    }

    public function test_save_draft_and_scoring_calculation(): void
    {
        $tmpl1 = AuditChecklist::create([
            'audit_type' => 'treasury',
            'category' => 'Vault',
            'activity' => 'Item 1',
            'weight' => 10.0,
            'is_active' => true,
            'created_by' => $this->asstTreasuryMgr->id,
        ]);
        $tmpl2 = AuditChecklist::create([
            'audit_type' => 'treasury',
            'category' => 'Vault',
            'activity' => 'Item 2',
            'weight' => 10.0,
            'is_active' => true,
            'created_by' => $this->asstTreasuryMgr->id,
        ]);

        $startRes = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/start/{$this->schedule->id}");
        $auditId = $startRes->json('data.id');
        $answers = $startRes->json('data.answers');

        // Save draft: Item 1 = YES, Item 2 = NO
        $draftRes = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/{$auditId}/draft", [
            'summary_remarks' => 'Partial check conducted',
            'answers' => [
                ['id' => $answers[0]['id'], 'answer' => 'yes', 'remarks' => 'Good'],
                ['id' => $answers[1]['id'], 'answer' => 'no', 'remarks' => 'Issue found'],
            ],
        ]);

        $draftRes->assertStatus(200);
        $this->assertEquals(50.0, (float) $draftRes->json('data.rating.score_percentage'));

        // Submit final audit
        $submitRes = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/{$auditId}/submit");
        $submitRes->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        $this->assertEquals('completed', $this->schedule->fresh()->status);
    }

    public function test_reopen_audit_requires_justification_reason(): void
    {
        $audit = VisitAudit::create([
            'store_visit_schedule_id' => $this->schedule->id,
            'supervisor_id' => $this->treasurySup->id,
            'audit_type' => 'treasury',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Fail without reason
        $this->actingAs($this->asstTreasuryMgr)->postJson("/api/visit-audits/{$audit->id}/reopen", [
            'reopen_reason' => '',
        ])->assertStatus(422);

        // Succeed with reason
        $this->actingAs($this->asstTreasuryMgr)->postJson("/api/visit-audits/{$audit->id}/reopen", [
            'reopen_reason' => 'Store manager disputed item 2 with new receipts.',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'reopened')
            ->assertJsonPath('data.reopen_reason', 'Store manager disputed item 2 with new receipts.');

        $this->assertEquals('in_progress', $this->schedule->fresh()->status);
    }
}

