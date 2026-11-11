<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    public const STAGES = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'];

    protected $fillable = [
        'name', 'email', 'phone', 'company', 'service', 'message',
        'source', 'stage', 'assigned_to', 'client_id', 'notes',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}