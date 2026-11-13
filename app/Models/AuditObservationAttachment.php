<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditObservationAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_observation_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Parent observation.
     */
    public function observation(): BelongsTo
    {
        return $this->belongsTo(AuditObservation::class, 'audit_observation_id');
    }

    /**
     * User who uploaded this file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

