<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VisitAudit extends Model
{
    use HasFactory;

    protected $table = 'visit_audits';

    protected $fillable = [
        'store_visit_schedule_id',
        'supervisor_id',
        'audit_type',
        'status',
        'actual_started_at',
        'actual_ended_at',
        'submitted_at',
        'summary_remarks',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'actual_started_at' => 'datetime',
            'actual_ended_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StoreVisitSchedule::class, 'store_visit_schedule_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AuditChecklistAnswer::class, 'visit_audit_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(AuditVisitRating::class, 'visit_audit_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(AuditObservation::class, 'visit_audit_id');
    }
}

