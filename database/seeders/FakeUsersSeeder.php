<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds a handful of fake users for development and impersonation testing.
 */
class FakeUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Sarah Chen', 'email' => 'sarah@example.com', 'tokens' => 50],
            ['name' => 'Marcus Johnson', 'email' => 'marcus@example.com', 'tokens' => 12],
            ['name' => 'Emily Rodriguez', 'email' => 'emily@example.com', 'tokens' => 0],
            ['name' => 'David Kim', 'email' => 'david@example.com', 'tokens' => 250],
            ['name' => 'Olivia Patel', 'email' => 'olivia@example.com', 'tokens' => 5],
            ['name' => 'James OConnor', 'email' => 'james@example.com', 'tokens' => 100],
            ['name' => 'Aisha Williams', 'email' => 'aisha@example.com', 'tokens' => 33],
            ['name' => 'Lucas Schmidt', 'email' => 'lucas@example.com', 'tokens' => 17],
            ['name' => 'Maria Gonzalez', 'email' => 'maria@example.com', 'tokens' => 80],
            ['name' => 'Noah Anderson', 'email' => 'noah@example.com', 'tokens' => 4],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => Str::slug($data['name']),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'token_balance' => $data['tokens'],
                    'is_admin' => false,
                ],
            );
        }
    }
}
