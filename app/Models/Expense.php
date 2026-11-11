<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'description',
        'supplier',
        'payment_method',
        'amount',
        'paid_by',
        'expense_date',
        'created_at'
    ];
}
