<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'cnpj', 'contact_info', 'created_at'];
    public $timestamps = false;
}
