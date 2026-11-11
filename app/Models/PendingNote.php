<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingNote extends Model
{
    protected $fillable = [
        'url',
        'access_key',
        'supplier_name',
        'total_amount',
        'emission_date',
        'status'
    ];

    protected $casts = [
        'emission_date' => 'date',
        'total_amount' => 'decimal:2',
    ];
}
