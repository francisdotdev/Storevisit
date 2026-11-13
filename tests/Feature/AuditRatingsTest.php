<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AuditVisitRating;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreVisitSchedule;
use App\Models\User;
use App\Models\VisitAudit;
use App\Services\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditRatingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;
    protected User $treasurySup;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $mgrRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_MANAGER], ['display_name' => 'Manager']);
        $treasurySupRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR], ['display_name' => 'Treasury Sup']);

        $this->managerUser = User::factory()->create(['role_id' => $mgrRole->id, 'status' => 'active']);
        $this->treasurySup = User::factory()->create(['role_id' => $treasurySupRole->id, 'status' => 'active']);

        $area = Area::create(['area_name' => 'Bicol Region', 'area_code' => 'BIC', 'is_active' => true]);
        $this->store = Store::create(['store_name' => 'LCC Mall Legazpi', 'store_code' => 'STR-01', 'area_id' => $area->id, 'is_active' => true]);
    }

    public function test_ratings_matrix_buckets_audits_into_weeks(): void
    {
        $sched = StoreVisitSchedule::create([
            'schedule_code' => 'SCH-100',
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'scheduled_by' => $this->managerUser->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-05',
            'status' => 'completed',
        ]);

        $audit = VisitAudit::create([
            'store_visit_schedule_id' => $sched->id,
            'supervisor_id' => $this->treasurySup->id,
            'audit_type' => 'treasury',
            'status' => 'submitted',
            'actual_started_at' => Carbon::createFromFormat('Y-m-d H:i:s', '2026-11-05 10:00:00', 'Asia/Manila')->utc(),
            'submitted_at' => Carbon::createFromFormat('Y-m-d H:i:s', '2026-11-05 12:00:00', 'Asia/Manila')->utc(),
        ]);

        AuditVisitRating::create([
            'visit_audit_id' => $audit->id,
            'store_visit_schedule_id' => $sched->id,
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'audit_type' => 'treasury',
            'visit_date' => '2026-11-05',
            'total_possible_weight' => 100.0,
            'total_earned_weight' => 95.0,
            'score_percentage' => 95.0,
        ]);

        $response = $this->actingAs($this->managerUser)->getJson('/api/ratings/matrix?audit_type=treasury&month=2026-11');

        $response->assertStatus(200)
            ->assertJsonPath('data.rows.0.store_code', 'STR-01')
            ->assertJsonPath('data.rows.0.week_2', null);

        $this->assertEquals(95.0, $response->json('data.rows.0.week_1'));
        $this->assertEquals(95.0, $response->json('data.rows.0.month_average'));
    }

    public function test_matrix_exports_csv_and_excel(): void
    {
        $this->actingAs($this->managerUser)
            ->get('/api/ratings/export/csv?audit_type=treasury&month=2026-11')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->managerUser)
            ->get('/api/ratings/export/excel?audit_type=treasury&month=2026-11')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
