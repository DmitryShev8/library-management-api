<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('STAFF_EMAIL');
        $password = env('STAFF_PASSWORD');

        if (!$email || !$password) {
            throw new RuntimeException('Staff credentials are not configured.');
        }

        $staff = User::firstOrNew(['email' => $email]);

        $staff->name = env('STAFF_NAME', 'Library Staff');
        $staff->password = Hash::make($password);
        $staff->role = 'staff';
        $staff->save();
    }
}