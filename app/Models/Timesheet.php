<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timesheet extends Model
{
    protected $fillable = [
        'user_id', 'week_start', 'status', 'submitted_at',
        'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /** Freelancers can change a sheet only while it is a draft or was sent back. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function totalHours(): float
    {
        return (float) $this->entries()->sum('hours');
    }
}
