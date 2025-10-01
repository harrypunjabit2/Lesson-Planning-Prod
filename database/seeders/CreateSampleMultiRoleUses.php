<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateSampleMultiRoleUsers extends Command
{
    protected $signature = 'users:create-samples';
    protected $description = 'Create sample users with different role combinations';

    public function handle()
    {
        $sampleUsers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => 'password123',
                'roles' => ['admin'],
                'is_active' => true,
            ],
            [
                'name' => 'Planner Grader',
                'email' => 'planner-grader@example.com',
                'password' => 'password123',
                'roles' => ['planner', 'grader'],
                'is_active' => true,
            ],
            [
                'name' => 'Viewer Grader',
                'email' => 'viewer-grader@example.com',
                'password' => 'password123',
                'roles' => ['viewer', 'grader'],
                'is_active' => true,
            ],
            [
                'name' => 'Grader Only',
                'email' => 'grader@example.com',
                'password' => 'password123',
                'roles' => ['grader'],
                'is_active' => true,
            ],
            [
                'name' => 'Viewer Only',
                'email' => 'viewer@example.com',
                'password' => 'password123',
                'roles' => ['viewer'],
                'is_active' => true,
            ],
        ];

        foreach ($sampleUsers as $userData) {
            $existingUser = User::where('email', $userData['email'])->first();
            
            if ($existingUser) {
                $this->warn("User {$userData['email']} already exists. Updating roles...");
                $user = $existingUser;
            } else {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'is_active' => $userData['is_active'],
                ]);
                
                $this->info("Created user: {$userData['email']}");
            }

            // Sync roles
            $user->syncRoles($userData['roles']);
            
            $this->info("Assigned roles [" . implode(', ', $userData['roles']) . "] to {$user->email}");
        }

        $this->info("\nSample users created successfully!");
        $this->info("Default password for all users: password123");
    }
}