<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Responsible for overall system access, user approvals, account management, and high-level reporting.',
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'High-level monitoring, audit observations and visit ratings review, and report exporting.',
            ],
            [
                'name' => 'asst_treasury_manager',
                'display_name' => 'Assistant Treasury Manager',
                'description' => 'Manages area locations, Treasury store assignments, visit schedules, and Treasury audit checklists.',
            ],
            [
                'name' => 'asst_cashier_manager',
                'display_name' => 'Assistant Cashier Manager',
                'description' => 'Manages area locations, Cashier store assignments, visit schedules, and Cashier audit checklists.',
            ],
            [
                'name' => 'area_treasury_supervisor',
                'display_name' => 'Area Treasury Supervisor',
                'description' => 'Field auditor executing Treasury checklists and logging Treasury audit observations.',
            ],
            [
                'name' => 'area_cashier_supervisor',
                'display_name' => 'Area Cashier Supervisor',
                'description' => 'Field auditor executing Cashier checklists and logging Cashier audit observations.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}

