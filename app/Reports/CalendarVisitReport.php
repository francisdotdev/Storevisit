<?php

namespace App\Reports;

use App\Models\StoreVisitSchedule;
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

class CalendarVisitReport
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
        $query = StoreVisitSchedule::query()
            ->with(['store.area', 'supervisor', 'scheduler', 'visitAudit'])
            ->select('store_visit_schedules.*');

        // 1. Role-based scoping (Condition 8: Default Deny)
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
            // Unrecognized or unauthorized role: 0 rows
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
            $query->where('scheduled_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('scheduled_date', '<=', $filters['date_to']);
        }

        // 3. Sorting (Condition 9: Whitelisted sort columns)
        $allowedSorts = [
            'scheduled_date' => 'scheduled_date',
            'status' => 'status',
            'audit_type' => 'audit_type',
            'created_at' => 'created_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'scheduled_date';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        } else {
            $query->orderBy('scheduled_date', 'desc');
        }

        return $query;
    }

    /**
     * Map model instance into standardized report row.
     */
    public function formatRow(StoreVisitSchedule $schedule): array
    {
        $todayManila = Carbon::now($this->timezone)->toDateString();
        $scheduledDate = $schedule->scheduled_date ? Carbon::parse($schedule->scheduled_date)->toDateString() : null;

        // Condition 10: Show past "scheduled" visits that never started as "Missed"
        // TODO: confirm with requestor
        $statusDisplay = ucfirst($schedule->status);
        if ($schedule->status === 'scheduled' && $scheduledDate && $scheduledDate < $todayManila && !$schedule->started_at) {
            $statusDisplay = 'Missed';
        }

        // Planned time window (e.g. 08:00 - 12:00 or notes or date)
        $timeWindow = 'Full Day';
        if (!empty($schedule->notes) && preg_match('/\b\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}\b/', $schedule->notes, $matches)) {
            $timeWindow = $matches[0];
        }

        // Actual start and end in Manila time
        $actualStarted = $schedule->started_at
            ? Carbon::parse($schedule->started_at)->setTimezone($this->timezone)->format('Y-m-d H:i')
            : ($schedule->visitAudit?->actual_started_at
                ? Carbon::parse($schedule->visitAudit->actual_started_at)->setTimezone($this->timezone)->format('Y-m-d H:i')
                : null);

        $actualEnded = $schedule->completed_at
            ? Carbon::parse($schedule->completed_at)->setTimezone($this->timezone)->format('Y-m-d H:i')
            : ($schedule->visitAudit?->actual_ended_at
                ? Carbon::parse($schedule->visitAudit->actual_ended_at)->setTimezone($this->timezone)->format('Y-m-d H:i')
                : null);

        return [
            'id' => $schedule->id,
            'schedule_code' => $schedule->schedule_code,
            'scheduled_date' => $scheduledDate,
            'time_window' => $timeWindow,
            'area_name' => $schedule->store?->area?->area_name ?? 'N/A',
            'store_code' => $schedule->store?->store_code ?? 'N/A',
            'store_name' => $schedule->store?->store_name ?? 'N/A',
            'audit_type' => ucfirst($schedule->audit_type),
            'supervisor_name' => $schedule->supervisor?->name ?? 'Unassigned',
            'status' => $statusDisplay,
            'actual_started' => $actualStarted ?? '-',
            'actual_ended' => $actualEnded ?? '-',
            'notes' => $schedule->notes ?? '',
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
        $filename = 'Calendar_Visits_Report_' . Carbon::now($this->timezone)->format('Ymd_His') . '.csv';

        $headers = [
            'Schedule Code',
            'Scheduled Date',
            'Planned Time Window',
            'Area',
            'Store Code',
            'Store Name',
            'Audit Type',
            'Supervisor',
            'Status',
            'Actual Started (Manila)',
            'Actual Ended (Manila)',
            'Notes',
        ];

        $rows = [];
        foreach ($items as $item) {
            $r = $this->formatRow($item);
            $rows[] = [
                $r['schedule_code'],
                $r['scheduled_date'],
                $r['time_window'],
                $r['area_name'],
                $r['store_code'],
                $r['store_name'],
                $r['audit_type'],
                $r['supervisor_name'],
                $r['status'],
                $r['actual_started'],
                $r['actual_ended'],
                $r['notes'],
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
        $filename = 'Calendar_Visits_Report_' . Carbon::now($this->timezone)->format('Ymd_His') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Calendar Visits');

        // Header
        $sheet->setCellValue('A1', 'LCC Store Visit Audit Portal - Calendar Visits Report');
        $sheet->setCellValue('A2', 'Generated: ' . Carbon::now($this->timezone)->format('Y-m-d H:i') . " ({$this->timezone}) | Records: " . count($items));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = [
            'Schedule Code', 'Scheduled Date', 'Planned Window', 'Area',
            'Store Code', 'Store Name', 'Audit Type', 'Supervisor',
            'Status', 'Actual Started', 'Actual Ended', 'Notes'
        ];

        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValue([$col, 4], $h);
            $col++;
        }

        $sheet->getStyle('A4:L4')->getFont()->setBold(true);
        $sheet->getStyle('A4:L4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4F6');

        $rowNum = 5;
        foreach ($items as $item) {
            $r = $this->formatRow($item);
            $sheet->setCellValue("A{$rowNum}", CsvExporter::sanitizeCell($r['schedule_code']));
            $sheet->setCellValue("B{$rowNum}", CsvExporter::sanitizeCell($r['scheduled_date']));
            $sheet->setCellValue("C{$rowNum}", CsvExporter::sanitizeCell($r['time_window']));
            $sheet->setCellValue("D{$rowNum}", CsvExporter::sanitizeCell($r['area_name']));
            $sheet->setCellValue("E{$rowNum}", CsvExporter::sanitizeCell($r['store_code']));
            $sheet->setCellValue("F{$rowNum}", CsvExporter::sanitizeCell($r['store_name']));
            $sheet->setCellValue("G{$rowNum}", CsvExporter::sanitizeCell($r['audit_type']));
            $sheet->setCellValue("H{$rowNum}", CsvExporter::sanitizeCell($r['supervisor_name']));
            $sheet->setCellValue("I{$rowNum}", CsvExporter::sanitizeCell($r['status']));
            $sheet->setCellValue("J{$rowNum}", CsvExporter::sanitizeCell($r['actual_started']));
            $sheet->setCellValue("K{$rowNum}", CsvExporter::sanitizeCell($r['actual_ended']));
            $sheet->setCellValue("L{$rowNum}", CsvExporter::sanitizeCell($r['notes']));
            $rowNum++;
        }

        foreach (range('A', 'L') as $c) {
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
        $filename = 'Calendar_Visits_Report_' . Carbon::now($this->timezone)->format('Ymd_His') . '.pdf';

        $formatted = $items->map(fn($item) => $this->formatRow($item))->toArray();

        $pdf = Pdf::loadView('reports.calendar_visits_pdf', [
            'rows' => $formatted,
            'title' => 'Calendar Visits Report',
            'subtitle' => 'Generated: ' . Carbon::now($this->timezone)->format('Y-m-d H:i') . " ({$this->timezone}) | Cap: {$limit} rows",
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
