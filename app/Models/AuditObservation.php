<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditObservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'visit_audit_id',
        'store_visit_schedule_id',
        'supervisor_id',
        'observed_at',
        'audit_type',
        'personnel_name',
        'issue_description',
        'action_required',
        'target_escalation_date',
        'resolution_details',
        'date_resolved',
        'areas_for_improvement',
        'status',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'target_escalation_date' => 'date',
            'date_resolved' => 'date',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function visitAudit(): BelongsTo
    {
        return $this->belongsTo(VisitAudit::class, 'visit_audit_id');
    }

    public function visitSchedule(): BelongsTo
    {
        return $this->belongsTo(StoreVisitSchedule::class, 'store_visit_schedule_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AuditObservationAttachment::class);
    }
}
