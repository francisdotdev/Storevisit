<?php

namespace App\Services;

use App\Models\AuditVisitRating;
use App\Models\VisitAudit;

class AuditScoringService
{
    /**
     * Compute and persist rating for a visit audit session.
     */
    public function computeRating(VisitAudit $visitAudit): AuditVisitRating
    {
        $answers = $visitAudit->answers()->get();

        $totalPossible = 0.00;
        $totalEarned = 0.00;

        foreach ($answers as $ans) {
            $weight = (float) ($ans->weight ?? 0.00);
            $normalizedAnswer = strtolower((string) $ans->answer);

            if ($normalizedAnswer === 'n_a' || $normalizedAnswer === 'na') {
                // N/A is excluded from denominator
                $ans->earned_weight = 0.00;
                $ans->save();
                continue;
            }

            $totalPossible += $weight;
            if ($normalizedAnswer === 'yes' || $normalizedAnswer === '1' || $normalizedAnswer === 'true') {
                $ans->earned_weight = $weight;
                $totalEarned += $weight;
            } else {
                $ans->earned_weight = 0.00;
            }
            $ans->save();
        }

        $percentage = $totalPossible > 0 ? round(($totalEarned / $totalPossible) * 100, 2) : 0.00;
        $grade = $this->determineGrade($percentage);

        $schedule = $visitAudit->schedule;

        return AuditVisitRating::updateOrCreate(
            ['visit_audit_id' => $visitAudit->id],
            [
                'store_visit_schedule_id' => $visitAudit->store_visit_schedule_id,
                'store_id' => $schedule?->store_id,
                'supervisor_id' => $visitAudit->supervisor_id,
                'audit_type' => $visitAudit->audit_type,
                'visit_date' => $schedule?->scheduled_date ?? now()->toDateString(),
                'total_possible_weight' => round($totalPossible, 2),
                'total_earned_weight' => round($totalEarned, 2),
                'total_earned_score' => round($totalEarned, 2),
                'score_percentage' => $percentage,
                'final_percentage' => $percentage,
                'grade' => $grade,
                'rating_grade' => $grade,
                'summary_remarks' => $visitAudit->summary_remarks,
                'rated_at' => now(),
            ]
        );
    }

    /**
     * Determine grade string from config/audit.php thresholds.
     */
    protected function determineGrade(float $percentage): ?string
    {
        $thresholds = config('audit.grade_thresholds', []);
        if (empty($thresholds) || !is_array($thresholds)) {
            return null; // As requested, null if not configured
        }

        foreach ($thresholds as $band) {
            $min = (float) ($band['min'] ?? 0);
            $max = (float) ($band['max'] ?? 100);
            if ($percentage >= $min && $percentage <= $max) {
                return $band['grade'] ?? null;
            }
        }

        return null;
    }
}

