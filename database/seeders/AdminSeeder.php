<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (!$email || !$password) {
            throw new RuntimeException('Admin credentials are not configured.');
        }

        $admin = User::firstOrNew([
            'email' => $email,
        ]);

        $admin->name = env('ADMIN_NAME', 'System Admin');
        $admin->password = Hash::make($password);
        $admin->role = 'admin';

        $admin->save();
    }
}