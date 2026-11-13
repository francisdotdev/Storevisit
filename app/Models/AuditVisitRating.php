<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditVisitRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_audit_id',
        'store_visit_schedule_id',
        'store_id',
        'supervisor_id',
        'audit_type',
        'visit_date',
        'total_possible_weight',
        'total_earned_weight',
        'total_earned_score',
        'score_percentage',
        'final_percentage',
        'grade',
        'rating_grade',
        'summary_remarks',
        'rated_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'rated_at' => 'datetime',
            'total_possible_weight' => 'decimal:2',
            'total_earned_weight' => 'decimal:2',
            'total_earned_score' => 'decimal:2',
            'score_percentage' => 'decimal:2',
            'final_percentage' => 'decimal:2',
        ];
    }

    public function visitAudit(): BelongsTo
    {
        return $this->belongsTo(VisitAudit::class, 'visit_audit_id');
    }

    public function visitSchedule(): BelongsTo
    {
        return $this->belongsTo(StoreVisitSchedule::class, 'store_visit_schedule_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
