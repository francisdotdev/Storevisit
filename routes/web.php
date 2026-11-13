<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuditChecklistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\RatingsController;
use App\Http\Controllers\RbacController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreVisitScheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitAuditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web & API Routes for LCC Store Visit Audit Portal
|--------------------------------------------------------------------------
*/

// API Endpoints under session/web middleware
Route::prefix('api')->group(function () {
    // CSRF token endpoint
    Route::get('/auth/csrf', function () {
        return response()->json(['csrf_token' => csrf_token()]);
    });

    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::get('/roles', [AuthController::class, 'roles']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Developer RBAC Matrix (Available strictly in local environment only)
    if (app()->environment('local')) {
        Route::get('/rbac/matrix', [RbacController::class, 'matrix']);
    }

    // Protected routes requiring active session
    Route::middleware('auth')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        // Administrator User Management (Strictly restricted to Admin role)
        Route::prefix('admin/users')->middleware('role:admin')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::post('/{user}/approve', [UserController::class, 'approve']);
            Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        });

        // Area Management
        Route::prefix('areas')->group(function () {
            Route::get('/', [AreaController::class, 'index']);
            Route::post('/', [AreaController::class, 'store']);
            Route::get('/{area}', [AreaController::class, 'show']);
            Route::put('/{area}', [AreaController::class, 'update']);
            Route::post('/{area}/toggle-status', [AreaController::class, 'toggleStatus']);
        });

        // Store Management & Supervisor Assignments
        Route::prefix('stores')->group(function () {
            Route::get('/', [StoreController::class, 'index']);
            Route::post('/', [StoreController::class, 'store']);
            Route::get('/supervisors', [StoreController::class, 'getEligibleSupervisors']);
            Route::get('/{store}', [StoreController::class, 'show']);
            Route::put('/{store}', [StoreController::class, 'update']);
            Route::post('/{store}/toggle-status', [StoreController::class, 'toggleStatus']);
            Route::post('/{store}/assign-supervisor', [StoreController::class, 'assignSupervisor']);
            Route::delete('/{store}/assignments/{assignment}', [StoreController::class, 'removeSupervisorAssignment']);
        });

        // Store Visit Calendar Schedules
        Route::prefix('schedules')->group(function () {
            Route::get('/', [StoreVisitScheduleController::class, 'index']);
            Route::post('/', [StoreVisitScheduleController::class, 'store']);
            Route::get('/{schedule}', [StoreVisitScheduleController::class, 'show']);
            Route::put('/{schedule}', [StoreVisitScheduleController::class, 'update']);
            Route::post('/{schedule}/status', [StoreVisitScheduleController::class, 'updateStatus']);
            Route::delete('/{schedule}', [StoreVisitScheduleController::class, 'destroy']);
        });

        // Checklist Templates (Step 8)
        Route::prefix('checklists')->group(function () {
            Route::get('/', [AuditChecklistController::class, 'index']);
            Route::post('/', [AuditChecklistController::class, 'store']);
            Route::put('/{auditChecklist}', [AuditChecklistController::class, 'update']);
            Route::delete('/{auditChecklist}', [AuditChecklistController::class, 'destroy']);
        });

        // Visit Audits Execution & Scoring (Step 8)
        Route::prefix('visit-audits')->group(function () {
            Route::post('/start/{schedule}', [VisitAuditController::class, 'startAudit']);
            Route::get('/{visitAudit}', [VisitAuditController::class, 'show']);
            Route::post('/{visitAudit}/draft', [VisitAuditController::class, 'saveDraft']);
            Route::post('/{visitAudit}/submit', [VisitAuditController::class, 'submitAudit']);
            Route::post('/{visitAudit}/reopen', [VisitAuditController::class, 'reopenAudit']);
        });

        // Audit Observations (Step 9 - FR2004)
        Route::prefix('observations')->group(function () {
            Route::get('/', [ObservationController::class, 'index']);
            Route::post('/', [ObservationController::class, 'store']);
            Route::get('/{auditObservation}', [ObservationController::class, 'show']);
            Route::put('/{auditObservation}', [ObservationController::class, 'update']);
            Route::post('/{auditObservation}/resolve', [ObservationController::class, 'resolve']);
            Route::post('/{auditObservation}/close', [ObservationController::class, 'close']);
            Route::post('/{auditObservation}/reopen', [ObservationController::class, 'reopen']);
            Route::delete('/{auditObservation}', [ObservationController::class, 'destroy']);
        });

        // Audit Visit Ratings & Matrix (Step 10 - FR2005 / FR3004-B)
        Route::prefix('ratings')->group(function () {
            Route::get('/matrix', [RatingsController::class, 'matrix']);
            Route::get('/export/csv', [RatingsController::class, 'exportCsv']);
            Route::get('/export/excel', [RatingsController::class, 'exportExcel']);
            Route::get('/export/pdf', [RatingsController::class, 'exportPdf']);
            Route::get('/store/{store}', [RatingsController::class, 'storeBreakdown']);
        });

        // Reports & Export (Step 11 - FR3002 / FR3004-A / FR1008)
        Route::prefix('reports')->group(function () {
            Route::get('/calendar-visits', [ReportController::class, 'calendarVisits']);
            Route::get('/calendar-visits/export/csv', [ReportController::class, 'exportCalendarVisitsCsv']);
            Route::get('/calendar-visits/export/excel', [ReportController::class, 'exportCalendarVisitsExcel']);
            Route::get('/calendar-visits/export/pdf', [ReportController::class, 'exportCalendarVisitsPdf']);

            Route::get('/observations', [ReportController::class, 'observations']);
            Route::get('/observations/export/csv', [ReportController::class, 'exportObservationsCsv']);
            Route::get('/observations/export/excel', [ReportController::class, 'exportObservationsExcel']);
            Route::get('/observations/export/pdf', [ReportController::class, 'exportObservationsPdf']);
        });

        // RBAC Inspection Endpoints
        Route::prefix('rbac')->group(function () {
            Route::get('/my-permissions', [RbacController::class, 'myPermissions']);
            Route::post('/verify-store-access', [RbacController::class, 'verifyStoreAccess']);

            // Developer verification probe endpoints (Strictly registered only when APP_ENV=local)
            if (app()->environment('local')) {
                Route::get('/test/reports-view', [RbacController::class, 'testReportsView'])
                    ->middleware('permission:reports.view');

                Route::get('/test/reports-export', [RbacController::class, 'testReportsExport'])
                    ->middleware('permission:reports.export');

                Route::get('/test/treasury-schedule', [RbacController::class, 'testTreasurySchedule'])
                    ->middleware('permission:schedules.manage_treasury');

                Route::get('/test/cashier-schedule', [RbacController::class, 'testCashierSchedule'])
                    ->middleware('permission:schedules.manage_cashier');

                Route::get('/test/treasury-checklist', [RbacController::class, 'testTreasuryChecklist'])
                    ->middleware('permission:checklists.execute_treasury');

                Route::get('/test/cashier-checklist', [RbacController::class, 'testCashierChecklist'])
                    ->middleware('permission:checklists.execute_cashier');
            }
        });
    });
});

// Single Page Application fallback - excludes /api routes
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
