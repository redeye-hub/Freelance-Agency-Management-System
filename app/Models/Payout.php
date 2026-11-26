<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    public const STATUSES = ['pending', 'approved', 'paid', 'rejected'];
    public const METHODS = ['bank_transfer', 'bkash', 'nagad', 'cash', 'other'];

    protected $fillable = [
        'number', 'user_id', 'hours', 'gross', 'commission', 'net', 'status',
        'requested_by', 'reviewed_by', 'reviewed_at', 'method', 'reference', 'paid_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'gross' => 'decimal:2',
            'commission' => 'decimal:2',
            'net' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayoutLine::class);
    }
}
