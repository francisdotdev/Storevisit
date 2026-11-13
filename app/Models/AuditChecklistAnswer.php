<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditChecklistAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_audit_id',
        'store_visit_schedule_id',
        'audit_checklist_id',
        'category',
        'activity',
        'sub_activity',
        'weight',
        'answer',
        'earned_weight',
        'score',
        'scale',
        'remarks',
        'personnel_in_charge',
        'answered_by',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'earned_weight' => 'decimal:2',
            'score' => 'decimal:2',
            'scale' => 'decimal:2',
        ];
    }

    /**
     * Header visit audit session.
     */
    public function visitAudit(): BelongsTo
    {
        return $this->belongsTo(VisitAudit::class, 'visit_audit_id');
    }

    /**
     * Store visit schedule this answer belongs to (legacy / secondary).
     */
    public function visitSchedule(): BelongsTo
    {
        return $this->belongsTo(StoreVisitSchedule::class, 'store_visit_schedule_id');
    }

    /**
     * Checklist item template answered.
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(AuditChecklist::class, 'audit_checklist_id');
    }

    /**
     * Auditor who provided this answer.
     */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
