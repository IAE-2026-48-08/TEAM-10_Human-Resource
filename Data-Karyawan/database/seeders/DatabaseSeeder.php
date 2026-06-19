<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default employees
        \App\Models\Employee::create([
            'nip' => 'EMP001',
            'nama' => 'Budi Santoso',
            'jabatan' => 'HR Staff',
            'departemen' => 'Human Resource',
            'gaji_pokok' => 5000000.00,
            'email' => 'budi@example.com'
        ]);

        \App\Models\Employee::create([
            'nip' => 'EMP002',
            'nama' => 'Dewi Lestari',
            'jabatan' => 'Financial Analyst',
            'departemen' => 'Finance',
            'gaji_pokok' => 6500000.00,
            'email' => 'dewi@example.com'
        ]);

        \App\Models\Employee::create([
            'nip' => 'EMP003',
            'nama' => 'Ramdani Cahyo',
            'jabatan' => 'Senior Developer',
            'departemen' => 'IT',
            'gaji_pokok' => 8000000.00,
            'email' => 'ramdani@example.com'
        ]);
    }
}
