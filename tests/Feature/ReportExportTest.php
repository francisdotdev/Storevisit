<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AuditObservation;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreVisitSchedule;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\CsvExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $managerUser;
    protected User $asstTreasuryMgr;
    protected User $asstCashierMgr;
    protected User $treasurySup;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ADMIN], ['display_name' => 'Admin']);
        $mgrRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_MANAGER], ['display_name' => 'Manager']);
        $asstTreasuryRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ASST_TREASURY_MANAGER], ['display_name' => 'Asst Treasury']);
        $asstCashierRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_ASST_CASHIER_MANAGER], ['display_name' => 'Asst Cashier']);
        $treasurySupRole = Role::firstOrCreate(['name' => AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR], ['display_name' => 'Treasury Sup']);

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id, 'status' => 'active']);
        $this->managerUser = User::factory()->create(['role_id' => $mgrRole->id, 'status' => 'active']);
        $this->asstTreasuryMgr = User::factory()->create(['role_id' => $asstTreasuryRole->id, 'status' => 'active']);
        $this->asstCashierMgr = User::factory()->create(['role_id' => $asstCashierRole->id, 'status' => 'active']);
        $this->treasurySup = User::factory()->create(['role_id' => $treasurySupRole->id, 'status' => 'active']);

        $area = Area::create(['area_name' => 'Albay Operations', 'area_code' => 'ALB', 'is_active' => true]);
        $this->store = Store::create(['store_name' => 'LCC Legazpi', 'store_code' => 'LEG-01', 'area_id' => $area->id, 'is_active' => true]);
    }

    public function test_calendar_visits_report_screen_and_scoping(): void
    {
        StoreVisitSchedule::create([
            'schedule_code' => 'SCH-T01',
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'scheduled_by' => $this->asstTreasuryMgr->id,
            'audit_type' => 'treasury',
            'scheduled_date' => '2026-11-10',
            'status' => 'scheduled',
            'notes' => 'Time: 08:00 - 12:00',
        ]);

        StoreVisitSchedule::create([
            'schedule_code' => 'SCH-C01',
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'scheduled_by' => $this->asstCashierMgr->id,
            'audit_type' => 'cashier',
            'scheduled_date' => '2026-11-10',
            'status' => 'scheduled',
        ]);

        // Manager sees both
        $mgrRes = $this->actingAs($this->managerUser)->getJson('/api/reports/calendar-visits');
        $mgrRes->assertStatus(200);
        $this->assertEquals(2, $mgrRes->json('data.total'));

        // Asst Treasury Manager sees only Treasury
        $treasuryRes = $this->actingAs($this->asstTreasuryMgr)->getJson('/api/reports/calendar-visits');
        $treasuryRes->assertStatus(200);
        $this->assertEquals(1, $treasuryRes->json('data.total'));
        $this->assertEquals('SCH-T01', $treasuryRes->json('data.data.0.schedule_code'));
        $this->assertEquals('08:00 - 12:00', $treasuryRes->json('data.data.0.time_window'));
    }

    public function test_observation_report_includes_days_open_and_improvements(): void
    {
        AuditObservation::create([
            'store_id' => $this->store->id,
            'supervisor_id' => $this->treasurySup->id,
            'audit_type' => 'treasury',
            'observed_at' => Carbon::now('Asia/Manila')->subDays(5),
            'personnel_name' => 'Juan Dela Cruz',
            'issue_description' => 'Discrepancy in vault key handover',
            'action_required' => 'Update logbook',
            'areas_for_improvement' => 'Improve dual combination log tracking',
            'target_escalation_date' => Carbon::now('Asia/Manila')->addDays(2)->toDateString(),
            'status' => 'open',
        ]);

        $res = $this->actingAs($this->managerUser)->getJson('/api/reports/observations');
        $res->assertStatus(200);
        $this->assertEquals(1, $res->json('data.total'));
        $row = $res->json('data.data.0');
        $this->assertEquals('Improve dual combination log tracking', $row['improvements']);
        $this->assertGreaterThanOrEqual(4, $row['days_open']);
    }

    public function test_csv_exporter_sanitizes_formula_injection_characters(): void
    {
        $dangerousValues = ['=1+1', '+SUM(A1)', '-cmd|', '@HYPERLINK', "\tTAB", "\rCR"];
        foreach ($dangerousValues as $val) {
            $sanitized = CsvExporter::sanitizeCell($val);
            $this->assertStringStartsWith("'", $sanitized, "Failed to sanitize formula character in {$val}");
        }

        $safeValue = 'Normal Text';
        $this->assertEquals('Normal Text', CsvExporter::sanitizeCell($safeValue));
    }

    public function test_reports_export_to_csv_excel_and_pdf(): void
    {
        // CSV Export
        $this->actingAs($this->managerUser)
            ->get('/api/reports/calendar-visits/export/csv')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Excel Export
        $this->actingAs($this->managerUser)
            ->get('/api/reports/calendar-visits/export/excel')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // PDF Export
        $this->actingAs($this->managerUser)
            ->get('/api/reports/calendar-visits/export/pdf')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Condition 7: Verify DomPDF is configured with isRemoteEnabled = false and does not fetch remote images.
     */
    public function test_dompdf_configuration_disables_remote_fetching(): void
    {
        $this->assertFalse(config('dompdf.options.enable_remote'));
        $this->assertFalse(config('dompdf.options.enable_php'));
        $this->assertStringNotContainsString('public', config('dompdf.options.font_dir'));

        // Attempting to render HTML containing a remote image should not throw an outbound exception and renders safely
        $html = '<html><body><h1>Security Test</h1><img src="http://192.0.2.1/test_remote_image.jpg" alt="Blocked Image" /></body></html>';
        $pdf = Pdf::loadHTML($html);
        $output = $pdf->output();

        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF', $output);
    }
}

