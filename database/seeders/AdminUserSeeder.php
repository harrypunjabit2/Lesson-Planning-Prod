<?php

// database/seeders/AdminUserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user if it doesn't exist
        $adminEmail = 'admin@example.com';
        
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'System Administrator',
                'email' => $adminEmail,
                'password' => Hash::make('password123'), // Change this in production!
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->command->info('Admin user created with email: ' . $adminEmail);
            $this->command->info('Default password: password123');
            $this->command->warn('Please change the default password after first login!');
        } else {
            $this->command->info('Admin user already exists.');
        }

        // Create sample planner user
        $plannerEmail = 'planner@example.com';
        
        if (!User::where('email', $plannerEmail)->exists()) {
            User::create([
                'name' => 'Lesson Planner',
                'email' => $plannerEmail,
                'password' => Hash::make('planner123'), // Change this in production!
                'role' => User::ROLE_PLANNER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->command->info('Planner user created with email: ' . $plannerEmail);
            $this->command->info('Default password: planner123');
        }

        // Create sample viewer user
        $viewerEmail = 'viewer@example.com';
        
        if (!User::where('email', $viewerEmail)->exists()) {
            User::create([
                'name' => 'Data Viewer',
                'email' => $viewerEmail,
                'password' => Hash::make('viewer123'), // Change this in production!
                'role' => User::ROLE_VIEWER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->command->info('Viewer user created with email: ' . $viewerEmail);
            $this->command->info('Default password: viewer123');
        }

        $this->command->warn('Remember to change all default passwords in production!');
    }
}