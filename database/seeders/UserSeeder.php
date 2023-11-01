<?php

namespace Database\Seeders;

use App\Models\AccessControl;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a user
        $user = \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'a@b.com',
            'eth_addr' => '0x123',
        ]);

        $accessControl = AccessControl::firstOrCreate([
            'eth_addr' => $user->eth_addr,
        ], [
            'role' => 'admin',
        ]);
    }
}
