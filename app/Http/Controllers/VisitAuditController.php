<?php

namespace App\Http\Controllers;

use App\Models\AuditChecklist;
use App\Models\AuditChecklistAnswer;
use App\Models\StoreVisitSchedule;
use App\Models\VisitAudit;
use App\Services\AuditLogService;
use App\Services\AuditScoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VisitAuditController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected AuditScoringService $scoringService
    ) {}

    /**
     * Start or resume an audit execution session for a scheduled visit.
     */
    public function startAudit(Request $request, StoreVisitSchedule $schedule): JsonResponse
    {
        $user = Auth::user();

        // 1. Authorization: Only the assigned supervisor or admin
        if (!$user->hasRole('admin') && $schedule->supervisor_id !== $user->id) {
            abort(403, 'You are not assigned to audit this visit.');
        }

        // 2. Date window validation
        $windowSetting = config('audit.allowed_date_window', 'scheduled_date_only');
        $manilaTimezone = config('reports.timezone', 'Asia/Manila');
        $todayManila = Carbon::now($manilaTimezone)->toDateString();
        $scheduledDateManila = Carbon::parse($schedule->scheduled_date)->toDateString();

        if ($windowSetting === 'scheduled_date_only') {
            if ($todayManila !== $scheduledDateManila) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Audits can only be started on their scheduled date ({$scheduledDateManila}). Today is {$todayManila}.",
                ], 422);
            }
        }

        // 3. Find or create visit audit header
        $visitAudit = DB::transaction(function () use ($schedule, $user) {
            $audit = VisitAudit::firstOrCreate(
                ['store_visit_schedule_id' => $schedule->id],
                [
                    'supervisor_id' => $schedule->supervisor_id,
                    'audit_type' => $schedule->audit_type,
                    'status' => 'draft',
                    'actual_started_at' => now(),
                ]
            );

            // If schedule not in progress, update it
            if ($schedule->status === 'scheduled') {
                $schedule->update([
                    'status' => 'in_progress',
                    'started_at' => $schedule->started_at ?? now(),
                ]);
            }

            // Snapshot active checklist items if answers do not exist yet
            if ($audit->answers()->count() === 0) {
                $templates = AuditChecklist::where('audit_type', $audit->audit_type)
                    ->where('is_active', true)
                    ->orderBy('category')
                    ->orderBy('sort_order')
                    ->get();

                foreach ($templates as $tmpl) {
                    AuditChecklistAnswer::create([
                        'visit_audit_id' => $audit->id,
                        'store_visit_schedule_id' => $schedule->id,
                        'audit_checklist_id' => $tmpl->id,
                        'category' => $tmpl->category,
                        'activity' => $tmpl->activity,
                        'sub_activity' => $tmpl->sub_activity,
                        'weight' => $tmpl->weight,
                        'answer' => null,
                        'earned_weight' => 0.00,
                        'score' => 0.00,
                        'scale' => $tmpl->weight,
                        'answered_by' => $user->id,
                    ]);
                }
            }

            return $audit;
        });

        $this->auditLogService->log($visitAudit, 'started', null, ['status' => $visitAudit->status], $user);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatVisitAuditPayload($visitAudit),
        ]);
    }

    /**
     * Show audit session details.
     */
    public function show(VisitAudit $visitAudit): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeVisitAccess($user, $visitAudit);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatVisitAuditPayload($visitAudit),
        ]);
    }

    /**
     * Save draft checklist answers.
     */
    public function saveDraft(Request $request, VisitAudit $visitAudit): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasRole('admin') && $visitAudit->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($visitAudit->status === 'submitted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot modify a submitted audit. Request a manager to reopen it.',
            ], 422);
        }

        $validated = $request->validate([
            'summary_remarks' => ['nullable', 'string'],
            'answers' => ['required', 'array'],
            'answers.*.id' => ['required', 'integer'],
            'answers.*.answer' => ['nullable', 'in:yes,no,n_a'],
            'answers.*.remarks' => ['nullable', 'string', 'max:500'],
            'answers.*.personnel_in_charge' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($visitAudit, $validated, $user) {
            if (isset($validated['summary_remarks'])) {
                $visitAudit->update(['summary_remarks' => $validated['summary_remarks']]);
            }

            foreach ($validated['answers'] as $ansData) {
                $ans = AuditChecklistAnswer::where('visit_audit_id', $visitAudit->id)
                    ->where('id', $ansData['id'])
                    ->first();

                if ($ans) {
                    $weight = (float) $ans->weight;
                    $norm = strtolower((string) ($ansData['answer'] ?? ''));
                    $earned = ($norm === 'yes') ? $weight : 0.00;

                    $ans->update([
                        'answer' => $ansData['answer'] ?? null,
                        'earned_weight' => $earned,
                        'remarks' => $ansData['remarks'] ?? null,
                        'personnel_in_charge' => $ansData['personnel_in_charge'] ?? null,
                        'answered_by' => $user->id,
                    ]);
                }
            }
        });

        // Compute preview rating
        $rating = $this->scoringService->computeRating($visitAudit);

        $this->auditLogService->log($visitAudit, 'saved_draft', null, ['answers_count' => count($validated['answers'])], $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Draft saved.',
            'data' => $this->formatVisitAuditPayload($visitAudit->fresh()),
        ]);
    }

    /**
     * Submit completed audit.
     */
    public function submitAudit(Request $request, VisitAudit $visitAudit): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasRole('admin') && $visitAudit->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($visitAudit->status === 'submitted') {
            return response()->json([
                'status' => 'error',
                'message' => 'This audit has already been submitted.',
            ], 422);
        }

        // Verify that all answers are provided
        $unanswered = $visitAudit->answers()->whereNull('answer')->count();
        if ($unanswered > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "There are {$unanswered} unanswered checklist items. Please answer all questions before submitting.",
            ], 422);
        }

        $validated = $request->validate([
            'summary_remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($visitAudit, $validated) {
            $visitAudit->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'actual_ended_at' => now(),
                'summary_remarks' => $validated['summary_remarks'] ?? $visitAudit->summary_remarks,
            ]);

            // Update schedule
            $schedule = $visitAudit->schedule;
            if ($schedule) {
                $schedule->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            // Final score computation
            $this->scoringService->computeRating($visitAudit);
        });

        $this->auditLogService->log($visitAudit, 'submitted', null, ['status' => 'submitted'], $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Audit submitted successfully.',
            'data' => $this->formatVisitAuditPayload($visitAudit->fresh()),
        ]);
    }

    /**
     * Reopen a submitted audit (Managers & Admins only).
     */
    public function reopenAudit(Request $request, VisitAudit $visitAudit): JsonResponse
    {
        $user = Auth::user();

        // Must be manager/admin for this audit type
        $isAuthorized = $user->hasRole('admin') ||
            $user->hasRole('manager') ||
            ($visitAudit->audit_type === 'treasury' && $user->hasRole('asst_treasury_manager')) ||
            ($visitAudit->audit_type === 'cashier' && $user->hasRole('asst_cashier_manager'));

        if (!$isAuthorized) {
            abort(403, 'Unauthorized to reopen submitted audits.');
        }

        $validated = $request->validate([
            'reopen_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $old = $visitAudit->toArray();

        DB::transaction(function () use ($visitAudit, $validated, $user) {
            $visitAudit->update([
                'status' => 'reopened',
                'reopened_by' => $user->id,
                'reopened_at' => now(),
                'reopen_reason' => $validated['reopen_reason'],
            ]);

            $schedule = $visitAudit->schedule;
            if ($schedule) {
                $schedule->update(['status' => 'in_progress']);
            }
        });

        $this->auditLogService->log($visitAudit, 'reopened', $old, $visitAudit->toArray(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Audit has been reopened for editing.',
            'data' => $this->formatVisitAuditPayload($visitAudit->fresh()),
        ]);
    }

    /**
     * Format payload with relations.
     */
    protected function formatVisitAuditPayload(VisitAudit $audit): array
    {
        $audit->loadMissing([
            'schedule.store.area',
            'supervisor',
            'reopenedBy',
            'rating',
            'answers' => fn($q) => $q->orderBy('category')->orderBy('id'),
        ]);

        return [
            'id' => $audit->id,
            'store_visit_schedule_id' => $audit->store_visit_schedule_id,
            'schedule' => $audit->schedule,
            'supervisor' => $audit->supervisor,
            'audit_type' => $audit->audit_type,
            'status' => $audit->status,
            'actual_started_at' => $audit->actual_started_at,
            'actual_ended_at' => $audit->actual_ended_at,
            'submitted_at' => $audit->submitted_at,
            'summary_remarks' => $audit->summary_remarks,
            'reopened_by' => $audit->reopenedBy?->name,
            'reopened_at' => $audit->reopened_at,
            'reopen_reason' => $audit->reopen_reason,
            'answers' => $audit->answers,
            'rating' => $audit->rating,
        ];
    }

    protected function authorizeVisitAccess($user, VisitAudit $audit): void
    {
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return;
        }

        if ($audit->audit_type === 'treasury' && $user->hasRole('asst_treasury_manager')) {
            return;
        }

        if ($audit->audit_type === 'cashier' && $user->hasRole('asst_cashier_manager')) {
            return;
        }

        if ($audit->supervisor_id === $user->id) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}

