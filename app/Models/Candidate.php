<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    public const STAGES = ['applied', 'screening', 'interview', 'approved', 'rejected'];

    protected $fillable = [
        'name', 'email', 'phone', 'headline', 'skills', 'expected_rate',
        'portfolio_url', 'cover_note', 'stage', 'assigned_to',
        'interview_at', 'notes', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'expected_rate' => 'decimal:2',
            'interview_at' => 'datetime',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return string[] */
    public function skillList(): array
    {
        return collect(explode(',', (string) $this->skills))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values()
            ->all();
    }
}
