<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'description',
        'source',
        'payment_method',
        'amount',
        'received_by',
        'revenue_date',
        'created_at'
    ];
}
