<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
