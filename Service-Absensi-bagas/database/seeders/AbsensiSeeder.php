<?php

namespace Database\Seeders;

use App\Models\Absensi;
use Illuminate\Database\Seeder;

class AbsensiSeeder extends Seeder
{
    /**
     * Seed data absensi sample untuk testing.
     */
    public function run(): void
    {
        $karyawanData = [
            ['id' => 1, 'nama' => 'Budi Santoso'],
            ['id' => 2, 'nama' => 'Siti Nurhaliza'],
            ['id' => 3, 'nama' => 'Ahmad Fauzi'],
            ['id' => 4, 'nama' => 'Dewi Lestari'],
            ['id' => 5, 'nama' => 'Rizky Pratama'],
            ['id' => 6, 'nama' => 'Putri Wulandari'],
            ['id' => 7, 'nama' => 'Hendra Wijaya'],
            ['id' => 8, 'nama' => 'Rina Marlina'],
            ['id' => 9, 'nama' => 'Agus Setiawan'],
            ['id' => 10, 'nama' => 'Nurul Hidayah'],
        ];

        // Data absensi untuk bulan Januari - Mei 2026
        foreach ($karyawanData as $karyawan) {
            for ($bulan = 1; $bulan <= 5; $bulan++) {
                $totalHariKerja = 22;
                $totalHadir = rand(17, $totalHariKerja);
                $sisaHari = $totalHariKerja - $totalHadir;
                $totalSakit = rand(0, min(2, $sisaHari));
                $sisaHari -= $totalSakit;
                $totalIzin = rand(0, min(2, $sisaHari));
                $totalAlpha = $sisaHari - $totalIzin;

                Absensi::create([
                    'karyawan_id' => $karyawan['id'],
                    'nama_karyawan' => $karyawan['nama'],
                    'bulan' => $bulan,
                    'tahun' => 2026,
                    'total_hadir' => $totalHadir,
                    'total_sakit' => $totalSakit,
                    'total_izin' => $totalIzin,
                    'total_alpha' => $totalAlpha,
                    'total_hari_kerja' => $totalHariKerja,
                ]);
            }
        }
    }
}
