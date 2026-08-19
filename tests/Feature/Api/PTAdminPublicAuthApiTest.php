<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PTAdmin\Admin\Models\User;
use PTAdmin\Admin\Tests\TestCase;

final class PTAdminPublicAuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ptadmin.public_auth.guard', 'frontend');
        config()->set('auth.guards.frontend', [
            'driver' => 'ptadmin',
            'provider' => 'users',
        ]);
        config()->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
        config()->set('ptadmin.captcha.scenes', [
            'frontend.register' => [
                'enabled' => false,
            ],
        ]);
        Cache::put('systemConfig', [
            '__sections__' => [],
            '__fields__' => [
                'security.is_register' => 1,
            ],
            '__public_fields__' => [],
        ]);

        $this->createUsersTable();
        $this->createFrontendUserTokensTable();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('users');
        Cache::forget('systemConfig');
        config()->set('ptadmin.captcha.scenes', null);

        parent::tearDown();
    }

    public function test_public_auth_api_completes_registration_login_me_and_logout(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'package-user',
            'password' => 'secret123',
            'nickname' => 'Package User',
        ])->assertOk()
            ->assertJsonPath('data.username', 'package-user');

        $login = $this->postJson('/api/auth/login', [
            'username' => 'package-user',
            'password' => 'secret123',
        ])->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.user.username', 'package-user');

        $token = (string) $login->json('data.token');
        self::assertNotSame('', $token);
        $headers = $this->jsonApiHeaders($token);

        $this->withHeaders($headers)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.username', 'package-user');

        $this->withHeaders($headers)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->withHeaders($headers)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('code', 419)
            ->assertJsonPath('message', '未登录');
    }

    public function test_public_auth_api_rejects_invalid_password_and_unauthenticated_requests(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'package-user',
            'password' => 'secret123',
        ])->assertOk();

        $this->withHeaders($this->jsonApiHeaders())->postJson('/api/auth/login', [
            'username' => 'package-user',
            'password' => 'wrong123',
        ])->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '登录失败，账户密码错误');

        $this->withHeaders($this->jsonApiHeaders())->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('code', 419)
            ->assertJsonPath('message', '未登录');
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 20)->unique();
            $table->string('nickname', 20)->default('');
            $table->string('password', 255);
            $table->string('salt', 10)->default('');
            $table->string('email', 50)->nullable()->unique();
            $table->string('mobile', 20)->nullable()->unique();
            $table->string('avatar', 255)->nullable();
            $table->string('bio', 255)->nullable();
            $table->string('level', 20)->default('');
            $table->unsignedTinyInteger('gender')->default(0);
            $table->string('birthday', 20)->nullable();
            $table->decimal('money', 12, 2)->default(0);
            $table->decimal('score', 12, 2)->default(0);
            $table->unsignedInteger('login_days')->default(0);
            $table->unsignedInteger('max_login_days')->default(0);
            $table->unsignedInteger('pre_at')->default(0);
            $table->unsignedInteger('last_at')->default(0);
            $table->string('login_ip', 45)->nullable();
            $table->unsignedInteger('join_ip')->default(0);
            $table->unsignedInteger('join_at')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->rememberToken();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();
        });
    }

    private function createFrontendUserTokensTable(): void
    {
        Schema::create('user_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('guard_name', 50);
            $table->string('token', 64);
            $table->unsignedInteger('ip')->default(0);
            $table->unsignedInteger('expires_at')->default(0);
            $table->unsignedInteger('last_used_at')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->index(['target_type', 'target_id']);
        });
    }
}
