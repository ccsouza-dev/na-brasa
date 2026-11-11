<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'payment_method',
        'description',
        'category',
        'responsible',
        'transaction_date',
        'order_id',
        'original_source',
        'original_id',
        'created_at',
    ];

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
