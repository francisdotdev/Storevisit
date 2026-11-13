<?php

namespace App\Http\Controllers;

use App\Reports\CalendarVisitReport;
use App\Reports\ObservationReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected CalendarVisitReport $calendarReport,
        protected ObservationReport $observationReport
    ) {}

    /**
     * Calendar Visits Screen Data (FR3002).
     */
    public function calendarVisits(Request $request): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeReportView($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order', 'per_page'
        ]);

        $data = $this->calendarReport->getScreenData($user, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Export Calendar Visits to CSV.
     */
    public function exportCalendarVisitsCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $this->authorizeReportExport($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);

        return $this->calendarReport->exportCsv($user, $filters);
    }

    /**
     * Export Calendar Visits to Excel.
     */
    public function exportCalendarVisitsExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $this->authorizeReportExport($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);

        return $this->calendarReport->exportExcel($user, $filters);
    }

    /**
     * Export Calendar Visits to PDF.
     */
    public function exportCalendarVisitsPdf(Request $request): \Illuminate\Http\Response
    {
        $user = Auth::user();
        $this->authorizeReportExport($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);

        return $this->calendarReport->exportPdf($user, $filters);
    }

    /**
     * Observations Screen Data (FR3004-A).
     */
    public function observations(Request $request): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeReportView($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order', 'per_page'
        ]);

        $data = $this->observationReport->getScreenData($user, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Export Observations to CSV.
     */
    public function exportObservationsCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $this->authorizeReportExport($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);

        return $this->observationReport->exportCsv($user, $filters);
    }

    /**
     * Export Observations to Excel.
     */
    public function exportObservationsExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $this->authorizeReportExport($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);

        return $this->observationReport->exportExcel($user, $filters);
    }

    /**
     * Export Observations to PDF.
     */
    public function exportObservationsPdf(Request $request): \Illuminate\Http\Response
    {
        $user = Auth::user();
        $this->authorizeReportExport($user);

        $filters = $request->only([
            'audit_type', 'status', 'area_id', 'store_id', 'supervisor_id',
            'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);

        return $this->observationReport->exportPdf($user, $filters);
    }

    protected function authorizeReportView($user): void
    {
        $allowed = $user->hasRole('admin') ||
            $user->hasRole('manager') ||
            $user->hasRole('asst_treasury_manager') ||
            $user->hasRole('asst_cashier_manager') ||
            $user->hasRole('area_treasury_supervisor') ||
            $user->hasRole('area_cashier_supervisor');

        if (!$allowed) {
            abort(403, 'Unauthorized to view reports.');
        }
    }

    protected function authorizeReportExport($user): void
    {
        $allowed = $user->hasRole('admin') ||
            $user->hasRole('manager') ||
            $user->hasRole('asst_treasury_manager') ||
            $user->hasRole('asst_cashier_manager') ||
            $user->hasRole('area_treasury_supervisor') ||
            $user->hasRole('area_cashier_supervisor');

        if (!$allowed) {
            abort(403, 'Unauthorized to export reports.');
        }
    }
}

