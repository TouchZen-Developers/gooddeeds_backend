<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        $adminExists = User::where('email', 'admin@gooddeeds.com')->exists();

        if (!$adminExists) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@gooddeeds.com',
                'password' => Hash::make('Admin@123'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]);

            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: admin@gooddeeds.com');
            $this->command->info('Password: Admin@123');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
