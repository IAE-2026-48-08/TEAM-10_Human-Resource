<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'employee_name',
        'period_month',
        'period_year',
        'base_salary',
        'total_present',
        'total_absent',
        'total_leave',
        'deduction',
        'bonus',
        'net_salary',
        'status',
        'processed_at',
        'receipt_number',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'base_salary'  => 'float',
        'net_salary'   => 'float',
        'deduction'    => 'float',
        'bonus'        => 'float',
    ];
}