<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'absensis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'karyawan_id',
        'nama_karyawan',
        'bulan',
        'tahun',
        'total_hadir',
        'total_sakit',
        'total_izin',
        'total_alpha',
        'total_hari_kerja',
        'receipt_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'karyawan_id' => 'integer',
        'bulan' => 'integer',
        'tahun' => 'integer',
        'total_hadir' => 'integer',
        'total_sakit' => 'integer',
        'total_izin' => 'integer',
        'total_alpha' => 'integer',
        'total_hari_kerja' => 'integer',
    ];
}
