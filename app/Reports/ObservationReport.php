<?php

namespace App\Reports;

use App\Models\AuditObservation;
use App\Models\User;
use App\Services\CsvExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ObservationReport
{
    protected string $timezone;
    protected array $limits;

    public function __construct()
    {
        $this->timezone = config('reports.timezone', 'Asia/Manila');
        $this->limits = config('reports.limits', [
            'screen' => 100,
            'csv' => 10000,
            'excel' => 5000,
            'pdf' => 1000,
        ]);
    }

    /**
     * Build base query with strict default-deny scoping.
     */
    public function query(User $user, array $filters = []): Builder
    {
        // Condition 11: Exclude soft-deleted rows (Eloquent default withoutTrashed)
        $query = AuditObservation::query()
            ->withoutTrashed()
            ->with(['store.area', 'supervisor', 'visitSchedule', 'closedBy'])
            ->select('audit_observations.*');

        // 1. Scoping (Condition 8: Default Deny)
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            // Unrestricted
        } elseif ($user->hasRole('asst_treasury_manager')) {
            $query->where('audit_type', 'treasury');
        } elseif ($user->hasRole('asst_cashier_manager')) {
            $query->where('audit_type', 'cashier');
        } elseif ($user->hasRole('area_treasury_supervisor') || $user->hasRole('area_cashier_supervisor')) {
            $expectedType = $user->hasRole('area_treasury_supervisor') ? 'treasury' : 'cashier';
            $query->where('audit_type', $expectedType);

            $assignedStoreIds = DB::table('supervisor_store_assignments')
                ->where('supervisor_id', $user->id)
                ->where('is_active', true)
                ->pluck('store_id')
                ->toArray();

            $query->whereIn('store_id', $assignedStoreIds);
        } else {
            // Unrecognized role -> 0 rows
            $query->whereRaw('1 = 0');
            return $query;
        }

        // 2. Filters
        if (!empty($filters['audit_type'])) {
            $query->where('audit_type', $filters['audit_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['area_id'])) {
            $query->whereHas('store', fn($q) => $q->where('area_id', $filters['area_id']));
        }
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['supervisor_id'])) {
            $query->where('supervisor_id', $filters['supervisor_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('observed_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('observed_at', '<=', $filters['date_to']);
        }

        // 3. Sorting (Condition 9: Whitelisted sort columns)
        $allowedSorts = [
            'observed_at' => 'observed_at',
            'status' => 'status',
            'audit_type' => 'audit_type',
            'target_escalation_date' => 'target_escalation_date',
            'created_at' => 'created_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'observed_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        } else {
            $query->orderBy('observed_at', 'desc');
        }

        return $query;
    }

    /**
     * Map model instance into standardized report row.
     */
    public function formatRow(AuditObservation $obs): array
    {
        $now = Carbon::now($this->timezone);
        $observedAt = $obs->observed_at
            ? Carbon::parse($obs->observed_at)->setTimezone($this->timezone)
            : Carbon::parse($obs->created_at)->setTimezone($this->timezone);

        // Condition 11: Calculate Days Open
        $endDate = $now;
        if (in_array($obs->status, ['resolved', 'closed'])) {
            if ($obs->date_resolved) {
                $endDate = Carbon::parse($obs->date_resolved)->setTimezone($this->timezone);
            } elseif ($obs->closed_at) {
                $endDate = Carbon::parse($obs->closed_at)->setTimezone($this->timezone);
            }
        }
        $daysOpen = max(0, (int) $observedAt->diffInDays($endDate));

        return [
            'id' => $obs->id,
            'observed_at' => $observedAt->format('Y-m-d H:i'),
            'area_name' => $obs->store?->area?->area_name ?? 'N/A',
            'store_code' => $obs->store?->store_code ?? 'N/A',
            'store_name' => $obs->store?->store_name ?? 'N/A',
            'audit_type' => ucfirst($obs->audit_type),
            'supervisor_name' => $obs->supervisor?->name ?? 'Unassigned',
            'personnel_name' => $obs->personnel_name ?? '-',
            'issue_description' => $obs->issue_description,
            'improvements' => $obs->areas_for_improvement ?? $obs->action_required ?? '-', // Condition 11: BRD label "Improvements"
            'action_required' => $obs->action_required ?? '-',
            'target_escalation_date' => $obs->target_escalation_date ? Carbon::parse($obs->target_escalation_date)->toDateString() : '-',
            'status' => ucfirst($obs->status),
            'days_open' => $daysOpen,
            'date_resolved' => $obs->date_resolved ? Carbon::parse($obs->date_resolved)->toDateString() : '-',
            'resolution_details' => $obs->resolution_details ?? '-',
        ];
    }

    /**
     * Get paginated screen data (Capped at 100 per page).
     */
    public function getScreenData(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), $this->limits['screen']);
        $paginator = $this->query($user, $filters)->paginate($perPage);

        $paginator->getCollection()->transform(fn($item) => $this->formatRow($item));

        return $paginator;
    }

    /**
     * Export to CSV.
     */
    public function exportCsv(User $user, array $filters = []): StreamedResponse
    {
        $limit = $this->limits['csv'];
        $items = $this->query($user, $filters)->limit($limit)->get();
        $filename = 'Observations_Report_' . Carbon::now($this->timezone)->format('Ymd_His') . '.csv';

        $headers = [
            'Observed Date (Manila)',
            'Area',
            'Store Code',
            'Store Name',
            'Audit Type',
            'Supervisor',
            'Store Personnel',
            'Identified Issue',
            'Improvements',
            'Action Required',
            'Target Escalation Date',
            'Status',
            'Days Open',
            'Date Resolved',
            'Resolution Details',
        ];

        $rows = [];
        foreach ($items as $item) {
            $r = $this->formatRow($item);
            $rows[] = [
                $r['observed_at'],
                $r['area_name'],
                $r['store_code'],
                $r['store_name'],
                $r['audit_type'],
                $r['supervisor_name'],
                $r['personnel_name'],
                $r['issue_description'],
                $r['improvements'],
                $r['action_required'],
                $r['target_escalation_date'],
                $r['status'],
                $r['days_open'],
                $r['date_resolved'],
                $r['resolution_details'],
            ];
        }

        return CsvExporter::stream($filename, $headers, $rows);
    }

    /**
     * Export to Excel.
     */
    public function exportExcel(User $user, array $filters = []): StreamedResponse
    {
        $limit = $this->limits['excel'];
        $items = $this->query($user, $filters)->limit($limit)->get();
        $filename = 'Observations_Report_' . Carbon::now($this->timezone)->format('Ymd_His') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Observations');

        // Header
        $sheet->setCellValue('A1', 'LCC Store Visit Audit Portal - Observations Report');
        $sheet->setCellValue('A2', 'Generated: ' . Carbon::now($this->timezone)->format('Y-m-d H:i') . " ({$this->timezone}) | Records: " . count($items));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = [
            'Observed Date', 'Area', 'Store Code', 'Store Name', 'Audit Type',
            'Supervisor', 'Store Personnel', 'Identified Issue', 'Improvements',
            'Action Required', 'Target Escalation Date', 'Status', 'Days Open',
            'Date Resolved', 'Resolution Details'
        ];

        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValue([$col, 4], $h);
            $col++;
        }

        $sheet->getStyle('A4:O4')->getFont()->setBold(true);
        $sheet->getStyle('A4:O4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4F6');

        $rowNum = 5;
        foreach ($items as $item) {
            $r = $this->formatRow($item);
            $sheet->setCellValue("A{$rowNum}", CsvExporter::sanitizeCell($r['observed_at']));
            $sheet->setCellValue("B{$rowNum}", CsvExporter::sanitizeCell($r['area_name']));
            $sheet->setCellValue("C{$rowNum}", CsvExporter::sanitizeCell($r['store_code']));
            $sheet->setCellValue("D{$rowNum}", CsvExporter::sanitizeCell($r['store_name']));
            $sheet->setCellValue("E{$rowNum}", CsvExporter::sanitizeCell($r['audit_type']));
            $sheet->setCellValue("F{$rowNum}", CsvExporter::sanitizeCell($r['supervisor_name']));
            $sheet->setCellValue("G{$rowNum}", CsvExporter::sanitizeCell($r['personnel_name']));
            $sheet->setCellValue("H{$rowNum}", CsvExporter::sanitizeCell($r['issue_description']));
            $sheet->setCellValue("I{$rowNum}", CsvExporter::sanitizeCell($r['improvements']));
            $sheet->setCellValue("J{$rowNum}", CsvExporter::sanitizeCell($r['action_required']));
            $sheet->setCellValue("K{$rowNum}", CsvExporter::sanitizeCell($r['target_escalation_date']));
            $sheet->setCellValue("L{$rowNum}", CsvExporter::sanitizeCell($r['status']));
            $sheet->setCellValue("M{$rowNum}", $r['days_open']);
            $sheet->setCellValue("N{$rowNum}", CsvExporter::sanitizeCell($r['date_resolved']));
            $sheet->setCellValue("O{$rowNum}", CsvExporter::sanitizeCell($r['resolution_details']));
            $rowNum++;
        }

        foreach (range('A', 'O') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Export to PDF.
     */
    public function exportPdf(User $user, array $filters = []): \Illuminate\Http\Response
    {
        $limit = $this->limits['pdf'];
        $items = $this->query($user, $filters)->limit($limit)->get();
        $filename = 'Observations_Report_' . Carbon::now($this->timezone)->format('Ymd_His') . '.pdf';

        $formatted = $items->map(fn($item) => $this->formatRow($item))->toArray();

        $pdf = Pdf::loadView('reports.observations_pdf', [
            'rows' => $formatted,
            'title' => 'Audit Observations Report',
            'subtitle' => 'Generated: ' . Carbon::now($this->timezone)->format('Y-m-d H:i') . " ({$this->timezone}) | Cap: {$limit} rows",
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
