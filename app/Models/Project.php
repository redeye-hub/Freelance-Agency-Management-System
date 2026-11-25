<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const STATUSES = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];

    protected $fillable = ['client_id', 'manager_id', 'name', 'description', 'budget', 'deadline', 'status'];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'deadline' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class)->where('status', 'active');
    }

    /** Barson's Task model (Kanban). Only called once his files exist. */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** Hazrat's PerformanceReview model. Only called once his files exist. */
    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function isManagedBy(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'project-manager']);
    }

    /** Managers and admins see every project; a freelancer only sees projects they are actively assigned to. */
    public function isAccessibleBy(User $user): bool
    {
        if ($this->isManagedBy($user)) {
            return true;
        }

        return $this->activeAssignments()->where('user_id', $user->id)->exists();
    }

    /** Percentage of milestones marked completed (0 when there are none). */
    public function milestoneProgress(): int
    {
        $total = $this->milestones->count();

        return $total === 0 ? 0 : (int) round($this->milestones->where('status', 'completed')->count() / $total * 100);
    }
}
