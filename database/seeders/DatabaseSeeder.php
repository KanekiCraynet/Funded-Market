<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InstrumentSeeder::class,
        ]);

        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1234567890',
            'is_active' => true,
            'email_verified' => true,
            'preferences' => [
                'risk_level' => 'MEDIUM',
                'time_horizon' => 'medium_term',
                'max_position_size' => 15,
                'notifications' => true,
            ],
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Test user created: test@example.com / password');
    }
}