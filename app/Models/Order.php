<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_name',
        'table_number',
        'status',
        'total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'paid_at',
        'motoboy_name',
        'created_at'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
