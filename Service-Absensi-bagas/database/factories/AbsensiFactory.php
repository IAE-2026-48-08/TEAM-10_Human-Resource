<?php

namespace Database\Factories;

use App\Models\Absensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Absensi>
 */
class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalHariKerja = 22;
        $totalHadir = fake()->numberBetween(15, $totalHariKerja);
        $sisaHari = $totalHariKerja - $totalHadir;
        $totalSakit = fake()->numberBetween(0, min(3, $sisaHari));
        $sisaHari -= $totalSakit;
        $totalIzin = fake()->numberBetween(0, min(2, $sisaHari));
        $totalAlpha = $sisaHari - $totalIzin;

        return [
            'karyawan_id' => fake()->numberBetween(1, 10),
            'nama_karyawan' => fake()->name(),
            'bulan' => fake()->numberBetween(1, 12),
            'tahun' => 2026,
            'total_hadir' => $totalHadir,
            'total_sakit' => $totalSakit,
            'total_izin' => $totalIzin,
            'total_alpha' => $totalAlpha,
            'total_hari_kerja' => $totalHariKerja,
        ];
    }
}
