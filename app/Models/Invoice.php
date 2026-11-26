<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    public const STATUSES = ['draft', 'sent', 'paid', 'void'];

    protected $fillable = [
        'number', 'public_token', 'project_id', 'client_id', 'status', 'issue_date', 'due_date',
        'subtotal', 'tax_percent', 'tax_amount', 'total', 'notes', 'sent_at', 'paid_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->public_token ??= Str::random(40);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** "Overdue" is not stored: a sent invoice past its due date. */
    public function isOverdue(): bool
    {
        return $this->status === 'sent' && $this->due_date->lt(now()->startOfDay());
    }

    /** What the badge shows: overdue replaces sent when the due date has passed. */
    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }

    /** Only a sent invoice can still be paid online. */
    public function isPayable(): bool
    {
        return $this->status === 'sent';
    }
}
