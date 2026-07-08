<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a single administrator account from environment configuration.
 *
 * Set ADMIN_NAME, ADMIN_EMAIL and ADMIN_PASSWORD in .env. Idempotent: matches
 * on the email and promotes/refreshes the account on every run.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('quiz.admin.email');
        $name = (string) config('quiz.admin.name');
        $password = (string) config('quiz.admin.password');

        if ($email === '' || $password === '') {
            $this->command?->warn('AdminUserSeeder: ADMIN_EMAIL / ADMIN_PASSWORD not set — skipping.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : 'TCL Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill(['is_admin' => true])->save();

        $this->command?->info("AdminUserSeeder: admin account ready for [{$email}].");
    }
}
