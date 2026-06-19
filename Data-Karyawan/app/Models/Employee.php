<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{

    protected $fillable = [
        'nip',
        'nama',
        'jabatan',
        'departemen',
        'gaji_pokok',
        'email'
    ];
}
