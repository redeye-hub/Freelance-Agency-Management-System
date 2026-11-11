<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'company_name', 'contact_name', 'email', 'phone', 'website',
        'billing_address', 'city', 'country', 'tax_id', 'notes', 'status',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}