<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use App\Models\VisitAudit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatingsMatrixService
{
    protected string $timezone;
    protected string $weekStrategy;
    protected string $aggregation;

    public function __construct()
    {
        $this->timezone = config('ratings.timezone', 'Asia/Manila');
        $this->weekStrategy = config('ratings.week_strategy', 'day_of_month');
        $this->aggregation = config('ratings.multiple_visit_aggregation', 'average');
    }

    /**
     * Build the ratings matrix data structure.
     */
    public function getMatrixData(User $user, array $filters = []): array
    {
        $auditType = $filters['audit_type'] ?? 'treasury';
        $yearMonth = $filters['month'] ?? Carbon::now($this->timezone)->format('Y-m');
        $areaId = $filters['area_id'] ?? null;

        $startOfMonth = Carbon::createFromFormat('Y-m', $yearMonth, $this->timezone)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 1. Scoping stores based on role (Default deny for unauthorized roles)
        $storesQuery = Store::with('area')->orderBy('area_id')->orderBy('store_name');

        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            // Unrestricted
            if ($areaId) {
                $storesQuery->where('area_id', $areaId);
            }
        } elseif ($user->hasRole('asst_treasury_manager')) {
            if ($auditType !== 'treasury') {
                return ['rows' => [], 'summary' => [], 'meta' => ['month' => $yearMonth, 'audit_type' => $auditType]];
            }
            if ($areaId) {
                $storesQuery->where('area_id', $areaId);
            }
        } elseif ($user->hasRole('asst_cashier_manager')) {
            if ($auditType !== 'cashier') {
                return ['rows' => [], 'summary' => [], 'meta' => ['month' => $yearMonth, 'audit_type' => $auditType]];
            }
            if ($areaId) {
                $storesQuery->where('area_id', $areaId);
            }
        } elseif ($user->hasRole('area_treasury_supervisor') || $user->hasRole('area_cashier_supervisor')) {
            $expectedType = $user->hasRole('area_treasury_supervisor') ? 'treasury' : 'cashier';
            if ($auditType !== $expectedType) {
                return ['rows' => [], 'summary' => [], 'meta' => ['month' => $yearMonth, 'audit_type' => $auditType]];
            }

            $assignedStoreIds = DB::table('supervisor_store_assignments')
                ->where('supervisor_id', $user->id)
                ->where('is_active', true)
                ->pluck('store_id')
                ->toArray();

            $storesQuery->whereIn('id', $assignedStoreIds);
            if ($areaId) {
                $storesQuery->where('area_id', $areaId);
            }
        } else {
            // Default deny: unknown or unauthorized role
            return ['rows' => [], 'summary' => [], 'meta' => ['month' => $yearMonth, 'audit_type' => $auditType]];
        }

        $stores = $storesQuery->get();

        // 2. Fetch submitted visit audits in this month for this audit type
        $audits = VisitAudit::with(['rating', 'schedule'])
            ->where('status', 'submitted')
            ->where('audit_type', $auditType)
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [
                $startOfMonth->copy()->startOfDay()->utc(),
                $endOfMonth->copy()->endOfDay()->utc(),
            ])
            ->whereHas('rating')
            ->get();

        // 3. Map audits to store and week
        $storeWeekScores = [];
        foreach ($audits as $audit) {
            $storeId = $audit->schedule?->store_id;
            if (!$storeId) {
                continue;
            }

            // Convert actual_started_at or submitted_at to Manila time
            $auditDate = $audit->actual_started_at
                ? $audit->actual_started_at->copy()->setTimezone($this->timezone)
                : $audit->submitted_at->copy()->setTimezone($this->timezone);

            $day = (int) $auditDate->format('j');
            $week = $this->determineWeek($day);

            $score = (float) ($audit->rating->score_percentage ?? 0.0);

            if (!isset($storeWeekScores[$storeId][$week])) {
                $storeWeekScores[$storeId][$week] = [];
            }
            $storeWeekScores[$storeId][$week][] = [
                'score' => $score,
                'date' => $auditDate,
            ];
        }

        // 4. Construct matrix rows
        $rows = [];
        $weekSums = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        $weekCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($stores as $store) {
            $weekVals = [];
            $storeScoresForAverage = [];

            for ($w = 1; $w <= 4; $w++) {
                $entries = $storeWeekScores[$store->id][$w] ?? [];
                if (empty($entries)) {
                    $weekVals[$w] = null;
                } else {
                    if ($this->aggregation === 'latest') {
                        usort($entries, fn($a, $b) => $b['date'] <=> $a['date']);
                        $val = $entries[0]['score'];
                    } else {
                        // Average
                        $val = round(array_sum(array_column($entries, 'score')) / count($entries), 2);
                    }
                    $weekVals[$w] = $val;
                    $storeScoresForAverage[] = $val;

                    $weekSums[$w] += $val;
                    $weekCounts[$w]++;
                }
            }

            $monthAvg = !empty($storeScoresForAverage)
                ? round(array_sum($storeScoresForAverage) / count($storeScoresForAverage), 2)
                : null;

            $rows[] = [
                'store_id' => $store->id,
                'store_code' => $store->store_code,
                'store_name' => $store->store_name,
                'area_name' => $store->area?->area_name ?? 'N/A',
                'week_1' => $weekVals[1],
                'week_2' => $weekVals[2],
                'week_3' => $weekVals[3],
                'week_4' => $weekVals[4],
                'month_average' => $monthAvg,
            ];
        }

        $summary = [
            'week_1_avg' => $weekCounts[1] > 0 ? round($weekSums[1] / $weekCounts[1], 2) : null,
            'week_2_avg' => $weekCounts[2] > 0 ? round($weekSums[2] / $weekCounts[2], 2) : null,
            'week_3_avg' => $weekCounts[3] > 0 ? round($weekSums[3] / $weekCounts[3], 2) : null,
            'week_4_avg' => $weekCounts[4] > 0 ? round($weekSums[4] / $weekCounts[4], 2) : null,
            'total_stores' => count($stores),
        ];

        return [
            'rows' => $rows,
            'summary' => $summary,
            'meta' => [
                'month' => $yearMonth,
                'audit_type' => $auditType,
                'timezone' => $this->timezone,
            ],
        ];
    }

    /**
     * Determine week number (1-4) based on day of month.
     */
    protected function determineWeek(int $day): int
    {
        if ($day <= 7) {
            return 1;
        }
        if ($day <= 14) {
            return 2;
        }
        if ($day <= 21) {
            return 3;
        }
        return 4;
    }

    /**
     * Export matrix to CSV.
     */
    public function exportCsv(User $user, array $filters = []): StreamedResponse
    {
        $data = $this->getMatrixData($user, $filters);
        $month = $data['meta']['month'];
        $type = ucfirst($data['meta']['audit_type']);
        $filename = "Ratings_Matrix_{$type}_{$month}.csv";

        $headers = [
            'Area',
            'Store Code',
            'Store Name',
            'Week 1 (Days 1-7)',
            'Week 2 (Days 8-14)',
            'Week 3 (Days 15-21)',
            'Week 4 (Days 22-End)',
            'Month Average (%)',
        ];

        $rows = [];
        foreach ($data['rows'] as $r) {
            $rows[] = [
                $r['area_name'],
                $r['store_code'],
                $r['store_name'],
                $r['week_1'] !== null ? number_format($r['week_1'], 2) . '%' : '-',
                $r['week_2'] !== null ? number_format($r['week_2'], 2) . '%' : '-',
                $r['week_3'] !== null ? number_format($r['week_3'], 2) . '%' : '-',
                $r['week_4'] !== null ? number_format($r['week_4'], 2) . '%' : '-',
                $r['month_average'] !== null ? number_format($r['month_average'], 2) . '%' : '-',
            ];
        }

        // Summary row
        $rows[] = [
            'AVERAGE',
            '',
            '',
            $data['summary']['week_1_avg'] !== null ? number_format($data['summary']['week_1_avg'], 2) . '%' : '-',
            $data['summary']['week_2_avg'] !== null ? number_format($data['summary']['week_2_avg'], 2) . '%' : '-',
            $data['summary']['week_3_avg'] !== null ? number_format($data['summary']['week_3_avg'], 2) . '%' : '-',
            $data['summary']['week_4_avg'] !== null ? number_format($data['summary']['week_4_avg'], 2) . '%' : '-',
            '',
        ];

        return CsvExporter::stream($filename, $headers, $rows);
    }

    /**
     * Export matrix to Excel (.xlsx).
     */
    public function exportExcel(User $user, array $filters = []): StreamedResponse
    {
        $data = $this->getMatrixData($user, $filters);
        $month = $data['meta']['month'];
        $type = ucfirst($data['meta']['audit_type']);
        $filename = "Ratings_Matrix_{$type}_{$month}.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ratings Matrix');

        // Title Block
        $sheet->setCellValue('A1', "LCC Store Visit Audit Portal - Ratings Monitoring Matrix");
        $sheet->setCellValue('A2', "Audit Type: {$type} | Month: {$month} | Generated: " . Carbon::now($this->timezone)->format('Y-m-d H:i'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('555555');

        // Headers
        $headers = ['Area', 'Store Code', 'Store Name', 'Week 1', 'Week 2', 'Week 3', 'Week 4', 'Month Average'];
        $colIndex = 1;
        foreach ($headers as $h) {
            $sheet->setCellValue([$colIndex, 4], $h);
            $colIndex++;
        }

        $headerRange = 'A4:H4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F3F4F6');

        // Rows
        $rowNum = 5;
        foreach ($data['rows'] as $r) {
            $sheet->setCellValue("A{$rowNum}", CsvExporter::sanitizeCell($r['area_name']));
            $sheet->setCellValue("B{$rowNum}", CsvExporter::sanitizeCell($r['store_code']));
            $sheet->setCellValue("C{$rowNum}", CsvExporter::sanitizeCell($r['store_name']));
            $sheet->setCellValue("D{$rowNum}", $r['week_1'] !== null ? $r['week_1'] : '');
            $sheet->setCellValue("E{$rowNum}", $r['week_2'] !== null ? $r['week_2'] : '');
            $sheet->setCellValue("F{$rowNum}", $r['week_3'] !== null ? $r['week_3'] : '');
            $sheet->setCellValue("G{$rowNum}", $r['week_4'] !== null ? $r['week_4'] : '');
            $sheet->setCellValue("H{$rowNum}", $r['month_average'] !== null ? $r['month_average'] : '');

            // Formatting percentages
            $sheet->getStyle("D{$rowNum}:H{$rowNum}")->getNumberFormat()->setFormatCode('0.00"%"');
            $rowNum++;
        }

        // Summary row
        $sheet->setCellValue("A{$rowNum}", 'AVERAGE');
        $sheet->setCellValue("D{$rowNum}", $data['summary']['week_1_avg'] ?? '');
        $sheet->setCellValue("E{$rowNum}", $data['summary']['week_2_avg'] ?? '');
        $sheet->setCellValue("F{$rowNum}", $data['summary']['week_3_avg'] ?? '');
        $sheet->setCellValue("G{$rowNum}", $data['summary']['week_4_avg'] ?? '');
        $sheet->getStyle("A{$rowNum}:H{$rowNum}")->getFont()->setBold(true);
        $sheet->getStyle("D{$rowNum}:G{$rowNum}")->getNumberFormat()->setFormatCode('0.00"%"');

        // Auto-fit columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
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
     * Export matrix to PDF.
     */
    public function exportPdf(User $user, array $filters = []): \Illuminate\Http\Response
    {
        $data = $this->getMatrixData($user, $filters);
        $month = $data['meta']['month'];
        $type = ucfirst($data['meta']['audit_type']);
        $filename = "Ratings_Matrix_{$type}_{$month}.pdf";

        $pdf = Pdf::loadView('reports.ratings_matrix_pdf', [
            'data' => $data,
            'title' => "Store Visit Audit Ratings - {$type}",
            'subtitle' => "Month: {$month} | Generated: " . Carbon::now($this->timezone)->format('Y-m-d H:i') . " ({$this->timezone})",
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
