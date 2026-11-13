<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'store_code',
        'store_name',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the area this store belongs to.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the user who created this store.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Supervisor assignments for this store.
     */
    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorStoreAssignment::class);
    }

    /**
     * Supervisors assigned to this store.
     */
    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supervisor_store_assignments', 'store_id', 'supervisor_id')
            ->withPivot('audit_type', 'assigned_by')
            ->withTimestamps();
    }

    /**
     * Visit schedules for this store.
     */
    public function visitSchedules(): HasMany
    {
        return $this->hasMany(StoreVisitSchedule::class);
    }

    /**
     * Audit ratings for this store.
     */
    public function auditRatings(): HasMany
    {
        return $this->hasMany(AuditVisitRating::class);
    }
}

