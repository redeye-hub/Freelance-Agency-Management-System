<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $fillable = [
        'project_assignment_id', 'number', 'hourly_rate', 'commission_percent', 'weekly_hours',
        'start_date', 'end_date', 'terms', 'status', 'sent_at', 'responded_at', 'response_note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ProjectAssignment::class, 'project_assignment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Standard clauses used when HR does not type custom terms. */
    public static function defaultTerms(): string
    {
        return implode("\n", [
            '1. The Freelancer provides the services described for the project named above as an independent contractor.',
            '2. Work is paid at the hourly rate above for hours approved on weekly timesheets.',
            '3. The Agency retains the stated commission percentage of the amount billed to the client for this work.',
            '4. The Freelancer keeps all client and project information confidential, during and after this engagement.',
            '5. Either party may end this contract with 7 days written notice through the FAMS platform.',
        ]);
    }
}
