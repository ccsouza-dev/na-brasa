<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'price',
        'category',
        'active',
        'cost_price',
        'stock_quantity',
        'min_stock',
        'barcode',
        'supplier_id'
    ];
}
