<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditChecklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'audit_type',
        'category',
        'title',
        'activity',
        'sub_activity',
        'has_sub_activity',
        'weight',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'has_sub_activity' => 'boolean',
            'is_active' => 'boolean',
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->title)) {
                $model->title = $model->category ?: ($model->activity ?: 'Audit Checklist');
            }
        });
    }

    /**
     * User who created this checklist item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated this checklist item.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Responses recorded against this checklist item.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AuditChecklistAnswer::class);
    }
}
