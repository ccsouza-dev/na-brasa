<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'expense_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price'
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
