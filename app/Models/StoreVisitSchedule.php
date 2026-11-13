<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreVisitSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_code',
        'store_id',
        'supervisor_id',
        'scheduled_by',
        'audit_type',
        'scheduled_date',
        'end_date',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the target store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the supervisor assigned to this visit.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the assistant manager who scheduled the visit.
     */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /**
     * Checklist answers recorded during this visit.
     */
    public function checklistAnswers(): HasMany
    {
        return $this->hasMany(AuditChecklistAnswer::class);
    }

    /**
     * Observations logged during this visit.
     */
    public function observations(): HasMany
    {
        return $this->hasMany(AuditObservation::class);
    }

    /**
     * Final rating score calculated for this visit.
     */
    public function rating(): HasOne
    {
        return $this->hasOne(AuditVisitRating::class);
    }

    /**
     * Header visit audit session.
     */
    public function visitAudit(): HasOne
    {
        return $this->hasOne(VisitAudit::class);
    }
}

