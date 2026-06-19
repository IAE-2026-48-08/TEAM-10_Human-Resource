<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payroll;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            ['id' => 1, 'name' => 'Budi Santoso', 'base' => 5000000],
            ['id' => 2, 'name' => 'Siti Rahayu',  'base' => 6000000],
            ['id' => 3, 'name' => 'Ahmad Fauzi',  'base' => 5500000],
        ];

        $workDays = 22;

        foreach ($employees as $emp) {
            $present   = rand(18, 22);
            $absent    = rand(0, 3);
            $leave     = max(0, $workDays - $present - $absent);
            $deduction = ($emp['base'] / $workDays) * $absent;
            $bonus     = $present >= 20 ? 500000 : 0;
            $netSalary = $emp['base'] - $deduction + $bonus;

            Payroll::create([
                'employee_id'   => $emp['id'],
                'employee_name' => $emp['name'],
                'period_month'  => 5,
                'period_year'   => 2025,
                'base_salary'   => $emp['base'],
                'total_present' => $present,
                'total_absent'  => $absent,
                'total_leave'   => $leave,
                'deduction'     => round($deduction, 2),
                'bonus'         => $bonus,
                'net_salary'    => round($netSalary, 2),
                'status'        => 'processed',
                'processed_at'  => Carbon::now(),
            ]);
        }
    }
}
