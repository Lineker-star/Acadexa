<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Runs on every deployment (composer deploy): only fill an empty database,
        // never duplicate data or reset accounts that already exist.
        if (User::query()->exists()) {
            $this->command?->info('Database already seeded: nothing to do.');
            return;
        }

        $this->call([
            SettingSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            CourseSeeder::class,
            CmsPageSeeder::class,
        ]);
    }
}
