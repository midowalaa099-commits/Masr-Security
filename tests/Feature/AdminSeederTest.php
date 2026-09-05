<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        unset($_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_PASSWORD']);

        putenv('ADMIN_PASSWORD');

        $this->app->detectEnvironment(fn () => 'testing');

        parent::tearDown();
    }

    private function setPassword(?string $password): void
    {
        unset($_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_PASSWORD']);

        if ($password === null) {
            putenv('ADMIN_PASSWORD');

            return;
        }

        $_ENV['ADMIN_PASSWORD'] = $password;

        putenv('ADMIN_PASSWORD='.$password);
    }

    public function test_seeds_the_admin_with_role_and_password(): void
    {
        $this->setPassword('Very-Strong-Secret-123');

        $this->seed(AdminSeeder::class);

        $admin = User::query()->where('email', 'admin@masr-security.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(password_verify('Very-Strong-Secret-123', $admin->password));
    }

    public function test_rejects_well_known_weak_passwords(): void
    {
        $this->setPassword('password');

        $this->expectException(\RuntimeException::class);

        $this->seed(AdminSeeder::class);
    }

    public function test_rejects_passwords_shorter_than_eight_characters(): void
    {
        $this->setPassword('short');

        $this->expectException(\RuntimeException::class);

        $this->seed(AdminSeeder::class);
    }

    public function test_generates_a_one_time_password_in_local_or_testing(): void
    {
        $this->setPassword(null);

        $this->seed(AdminSeeder::class);

        $admin = User::query()->where('email', 'admin@masr-security.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertFalse(password_verify('password', $admin->password));
        $this->assertTrue(strlen((string) $admin->password) > 0);
    }

    public function test_requires_an_explicit_password_outside_local_or_testing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->setPassword(null);

        $this->expectException(\RuntimeException::class);

        (new AdminSeeder)->setContainer($this->app)->run();
    }
}
