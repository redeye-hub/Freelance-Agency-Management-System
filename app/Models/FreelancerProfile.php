<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FreelancerProfile extends Model
{
    public const AVAILABILITY = ['available', 'busy', 'unavailable'];

    protected $fillable = ['user_id', 'headline', 'bio', 'hourly_rate', 'availability'];

    protected function casts(): array
    {
        return ['hourly_rate' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }
}