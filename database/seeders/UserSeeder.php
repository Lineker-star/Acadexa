<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'name'              => 'ACADEXA Admin',
            'email'             => 'admin@acadexa.com',
            'password'          => Hash::make('ACADEXA@2026'),
            'role'              => 'super_admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // Regular Admin
        User::create([
            'name'              => 'Site Manager',
            'email'             => 'manager@acadexa.com',
            'password'          => Hash::make('ACADEXA@2026'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // Instructor 1
        User::create([
            'name'              => 'Dr. Marie Nguembe',
            'email'             => 'marie@acadexa.com',
            'password'          => Hash::make('ACADEXA@2026'),
            'role'              => 'instructor',
            'instructor_status' => 'confirmed',
            'is_active'         => true,
            'email_verified_at' => now(),
            'bio'               => 'Doctor of Computer Science with 10+ years teaching web development and programming at ZTF University Institute.',
            'country'           => 'Cameroon',
        ]);

        // Instructor 2
        User::create([
            'name'              => 'Prof. Jean-Paul Mbarga',
            'email'             => 'jeanpaul@acadexa.com',
            'password'          => Hash::make('ACADEXA@2026'),
            'role'              => 'instructor',
            'instructor_status' => 'confirmed',
            'is_active'         => true,
            'email_verified_at' => now(),
            'bio'               => 'Professor of Business Administration and Entrepreneurship. Passionate about helping students build successful businesses in Africa.',
            'country'           => 'Cameroon',
        ]);

        // Instructor 3
        User::create([
            'name'              => 'Ms. Aisha Bello',
            'email'             => 'aisha@acadexa.com',
            'password'          => Hash::make('ACADEXA@2026'),
            'role'              => 'instructor',
            'instructor_status' => 'confirmed',
            'is_active'         => true,
            'email_verified_at' => now(),
            'bio'               => 'Digital Marketing Expert and Social Media Strategist with experience across Sub-Saharan Africa.',
            'country'           => 'Nigeria',
        ]);

        // Demo Student
        User::create([
            'name'              => 'Demo Student',
            'email'             => 'student@acadexa.com',
            'password'          => Hash::make('ACADEXA@2026'),
            'role'              => 'student',
            'is_active'         => true,
            'email_verified_at' => now(),
            'trial_started_at'  => now(),
            'country'           => 'Cameroon',
        ]);
    }
}
