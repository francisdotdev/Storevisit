<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            User::firstOrCreate(
                ['email' => 'admin@lcc.com.ph'],
                [
                    'employee_id' => 'ADMIN01',
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'email' => 'admin@lcc.com.ph',
                    'password' => Hash::make('password'),
                    'role_id' => $adminRole->id,
                    'branch_code' => 'HO',
                    'status' => 'active',
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
