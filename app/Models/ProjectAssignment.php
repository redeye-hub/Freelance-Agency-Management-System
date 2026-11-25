<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectAssignment extends Model
{
    protected $fillable = ['project_id', 'user_id', 'hourly_rate', 'commission_percent', 'weekly_hours', 'status'];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'commission_percent' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Hazrat's contract for this assignment (one at most). */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }
}
