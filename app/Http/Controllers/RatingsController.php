<?php

namespace App\Http\Controllers;

use App\Models\AuditVisitRating;
use App\Models\Store;
use App\Services\RatingsMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatingsController extends Controller
{
    public function __construct(
        protected RatingsMatrixService $matrixService
    ) {}

    /**
     * Get Ratings Monitoring Matrix JSON data.
     */
    public function matrix(Request $request): JsonResponse
    {
        $user = Auth::user();
        $filters = [
            'audit_type' => $request->query('audit_type', 'treasury'),
            'month' => $request->query('month'),
            'area_id' => $request->query('area_id'),
        ];

        $data = $this->matrixService->getMatrixData($user, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Export Ratings Matrix to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $filters = [
            'audit_type' => $request->query('audit_type', 'treasury'),
            'month' => $request->query('month'),
            'area_id' => $request->query('area_id'),
        ];

        return $this->matrixService->exportCsv($user, $filters);
    }

    /**
     * Export Ratings Matrix to Excel (.xlsx).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $filters = [
            'audit_type' => $request->query('audit_type', 'treasury'),
            'month' => $request->query('month'),
            'area_id' => $request->query('area_id'),
        ];

        return $this->matrixService->exportExcel($user, $filters);
    }

    /**
     * Export Ratings Matrix to PDF.
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $user = Auth::user();
        $filters = [
            'audit_type' => $request->query('audit_type', 'treasury'),
            'month' => $request->query('month'),
            'area_id' => $request->query('area_id'),
        ];

        return $this->matrixService->exportPdf($user, $filters);
    }

    /**
     * Store ratings history breakdown.
     */
    public function storeBreakdown(Request $request, Store $store): JsonResponse
    {
        $user = Auth::user();
        $query = AuditVisitRating::with(['visitAudit.answers', 'supervisor'])
            ->where('store_id', $store->id)
            ->orderBy('visit_date', 'desc');

        if ($request->filled('audit_type')) {
            $query->where('audit_type', $request->query('audit_type'));
        }

        $ratings = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $ratings,
        ]);
    }
}

