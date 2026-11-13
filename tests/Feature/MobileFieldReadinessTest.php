<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AuditChecklist;
use App\Models\AuditLog;
use App\Models\AuditObservation;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreVisitSchedule;
use App\Models\User;
use App\Models\VisitAudit;
use App\Services\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MobileFieldReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $treasurySup;
    protected User $asstTreasuryMgr;
    protected Store $store;
    protected StoreVisitSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $asstTreasuryRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ASST_TREASURY_MANAGER], ['display_name' => 'Asst Treasury Mgr']);
        $treasurySupRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR], ['display_name' => 'Treasury Sup']);

        $this->asstTreasuryMgr = User::factory()->create(['role_id' => $asstTreasuryRole->id, 'status' => 'active']);
        $this->treasurySup = User::factory()->create(['role_id' => $treasurySupRole->id, 'status' => 'active']);

        $area = Area::create(['area_name' => 'Naga Area', 'area_code' => 'NAG-01', 'is_active' => true]);
        $this->store = Store::create(['store_name' => 'LCC Naga Central', 'store_code' => 'LCC-NC', 'area_id' => $area->id, 'is_active' => true]);

        $this->schedule = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-MOB-001',
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'scheduled_by' => $this->asstTreasuryMgr->id,
            'audit_type' => 'treasury',
            'scheduled_date' => Carbon::now('Asia/Manila')->toDateString(),
            'status' => 'scheduled',
        ]);
    }

    public function test_double_submission_of_audit_is_idempotent_and_creates_single_log(): void
    {
        AuditChecklist::create([
            'audit_type' => 'treasury',
            'category' => 'Cash Handling',
            'activity' => 'Safe Count Verification',
            'weight' => 20.0,
            'is_active' => true,
            'created_by' => $this->asstTreasuryMgr->id,
        ]);

        $startRes = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/start/{$this->schedule->id}");
        $auditId = $startRes->json('data.id');
        $answers = $startRes->json('data.answers');

        // Save draft
        $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/{$auditId}/draft", [
            'summary_remarks' => 'Completed full store inspection.',
            'answers' => [
                ['id' => $answers[0]['id'], 'answer' => 'yes', 'remarks' => 'Verified verified'],
            ],
        ])->assertStatus(200);

        // First Submission
        $submit1 = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/{$auditId}/submit");
        $submit1->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        // Second Submission (e.g. mobile double-tap / slow cellular network retry)
        $submit2 = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/{$auditId}/submit");
        $submit2->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        // Verify only 1 audit_logs entry with action='submitted'
        $submittedLogsCount = AuditLog::where('auditable_type', VisitAudit::class)
            ->where('auditable_id', $auditId)
            ->where('action', 'submitted')
            ->count();

        $this->assertEquals(1, $submittedLogsCount);
    }

    public function test_observation_creation_with_same_idempotency_key_returns_existing_record(): void
    {
        Cache::flush();
        $idempotencyKey = 'mobile-req-' . uniqid();

        $payload = [
            'store_id' => $this->store->id,
            'personnel_name' => 'Maria Santos',
            'issue_description' => 'Unreconciled petty cash register drawer discrepancy.',
            'action_required' => 'Balance cash register before end of shift.',
            'target_escalation_date' => now()->addDays(2)->toDateString(),
        ];

        // 1st request with Idempotency Key
        $res1 = $this->actingAs($this->treasurySup)->withHeaders([
            'X-Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/observations', $payload);

        $res1->assertStatus(201);
        $obsId1 = $res1->json('data.id');
        $this->assertNotNull($obsId1);

        // 2nd request with exact same Idempotency Key (e.g. mobile retry)
        $res2 = $this->actingAs($this->treasurySup)->withHeaders([
            'X-Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/observations', $payload);

        $res2->assertStatus(200);
        $obsId2 = $res2->json('data.id');

        $this->assertEquals($obsId1, $obsId2);
        $this->assertEquals(1, AuditObservation::where('store_id', $this->store->id)->count());
    }

    public function test_observation_creation_with_rapid_content_debounce_prevents_duplicate(): void
    {
        Cache::flush();

        $payload = [
            'store_id' => $this->store->id,
            'personnel_name' => 'Juan Dela Cruz',
            'issue_description' => 'Missing receipt books for secondary cash register.',
            'action_required' => 'Procure official logbook from Treasury office.',
            'target_escalation_date' => now()->addDays(1)->toDateString(),
        ];

        // 1st call without explicit key
        $res1 = $this->actingAs($this->treasurySup)->postJson('/api/observations', $payload);
        $res1->assertStatus(201);
        $obsId1 = $res1->json('data.id');

        // 2nd call immediately with same payload
        $res2 = $this->actingAs($this->treasurySup)->postJson('/api/observations', $payload);
        $res2->assertStatus(200);
        $obsId2 = $res2->json('data.id');

        $this->assertEquals($obsId1, $obsId2);
        $this->assertEquals(1, AuditObservation::where('personnel_name', 'Juan Dela Cruz')->count());
    }

    public function test_starting_audit_twice_returns_existing_visit_audit_idempotently(): void
    {
        AuditChecklist::create([
            'audit_type' => 'treasury',
            'category' => 'Compliance',
            'activity' => 'Check register receipts',
            'weight' => 10.0,
            'is_active' => true,
            'created_by' => $this->asstTreasuryMgr->id,
        ]);

        // Start audit call 1
        $res1 = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/start/{$this->schedule->id}");
        $res1->assertStatus(200);
        $auditId1 = $res1->json('data.id');

        // Start audit call 2 (e.g. repeated tap)
        $res2 = $this->actingAs($this->treasurySup)->postJson("/api/visit-audits/start/{$this->schedule->id}");
        $res2->assertStatus(200);
        $auditId2 = $res2->json('data.id');

        $this->assertEquals($auditId1, $auditId2);
        $this->assertEquals(1, VisitAudit::where('store_visit_schedule_id', $this->schedule->id)->count());
    }

    public function test_unauthenticated_requests_return_401_json_for_mobile_session_recovery(): void
    {
        // Must return JSON 401, not 302 redirect to /login
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);

        $postResponse = $this->postJson("/api/visit-audits/start/{$this->schedule->id}");
        $postResponse->assertStatus(401);
    }
}

