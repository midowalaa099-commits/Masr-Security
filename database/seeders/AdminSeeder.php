<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Create the admin account using ADMIN_* env values.
 */
class AdminSeeder extends Seeder
{
    /**
     * Well-known credentials that are never accepted regardless of environment.
     */
    private const WEAK_PASSWORDS = [
        'password',
        'admin',
        'administrator',
        'admin123',
        'root',
        'secret',
        '123456',
        '12345678',
        '123456789',
        'qwerty',
        'letmein',
    ];

    public function run(): void
    {
        $name = env('ADMIN_NAME', 'MASR Admin');
        $email = env('ADMIN_EMAIL', 'admin@masr-security.com');
        $password = $this->resolvePassword(env('ADMIN_PASSWORD'));

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => env('ADMIN_PHONE', '+20 100 000 0000'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill(['role' => UserRole::Admin])->save();

        $this->command?->info("Admin user ready: {$email}");
    }

    private function resolvePassword(mixed $password): string
    {
        if (filled($password)) {
            if (! is_string($password) || Str::length($password) < 8) {
                throw new \RuntimeException('ADMIN_PASSWORD must be at least 8 characters long.');
            }

            if (in_array(Str::lower($password), self::WEAK_PASSWORDS, true)) {
                throw new \RuntimeException('ADMIN_PASSWORD must not be a well-known weak password.');
            }

            return $password;
        }

        if (app()->environment(['local', 'testing'])) {
            $generated = Str::password(16);

            $this->command?->warn('No ADMIN_PASSWORD set; a temporary password was generated but is not shown. Re-run with ADMIN_PASSWORD set to use a known password.');

            return $generated;
        }

        throw new \RuntimeException('ADMIN_PASSWORD must be configured in non-local environments.');
    }
}
