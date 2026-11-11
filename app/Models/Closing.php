<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Closing extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'start_date',
        'end_date',
        'total_revenue',
        'total_expenses_caixa',
        'reimbursement_jaqueline',
        'reimbursement_paloma',
        'fixed_costs_total',
        'fixed_costs_details',
        'net_profit',
        'profit_share_jaqueline',
        'profit_share_paloma',
        'created_at'
    ];
}
