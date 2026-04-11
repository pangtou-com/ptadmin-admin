<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use PTAdmin\Admin\Http\Middleware\CanInstallMiddleware;
use PTAdmin\Admin\Services\Install\RequirementService;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminInstallModuleTest extends TestCase
{
    protected function setUp(): void
    {
        @unlink($this->installedMarkerPath());
        @unlink($this->agreementMarkerPath());

        parent::setUp();
    }

    protected function tearDown(): void
    {
        @unlink($this->installedMarkerPath());
        @unlink($this->agreementMarkerPath());

        parent::tearDown();
    }

    public function test_install_module_is_available_before_system_is_marked_as_installed(): void
    {
        self::assertSame('7.4.0', config('ptadmin-install.core.min_php_version'));
        self::assertSame(
            CanInstallMiddleware::class,
            app(Router::class)->getMiddleware()['ptadmin.install'] ?? null
        );
        self::assertTrue(Route::has('ptadmin.install.welcome'));
        self::assertTrue(Route::has('ptadmin.install.accept'));
        self::assertTrue(Route::has('ptadmin.install.requirements'));
        self::assertTrue(Route::has('ptadmin.install.environment'));
        self::assertTrue(Route::has('ptadmin.install.stream'));

        $this->get('/install')
            ->assertOk()
            ->assertSee('欢迎使用PTAdmin')
            ->assertDontSee('layui');

        $this->get('/install/requirements')
            ->assertRedirect(route('ptadmin.install.welcome', [
                'redirect' => '/install/requirements',
                'error' => 'protocol',
            ]));

        $this->get('/install/env')
            ->assertRedirect(route('ptadmin.install.welcome', [
                'redirect' => '/install/env',
                'error' => 'protocol',
            ]));

        $this->post('/install/accept', [
            'redirect' => '/install/requirements',
        ])->assertRedirect('/install/requirements');

        file_put_contents($this->agreementMarkerPath(), (string) time());

        $this->get('/install/requirements')
            ->assertOk()
            ->assertSee('环境检测')
            ->assertSee('PHP版本')
            ->assertSee('.env');
    }

    public function test_environment_step_redirects_back_to_requirements_when_requirement_check_has_failures(): void
    {
        $this->app->instance(RequirementService::class, new class() extends RequirementService {
            public function getCheckResults(): array
            {
                return [[
                    'title' => 'PHP版本',
                    'results' => [[
                        'title' => 'PHP',
                        'config' => '>= 8.0',
                        'state' => false,
                    ]],
                ]];
            }
        });

        file_put_contents($this->agreementMarkerPath(), (string) time());

        $this->get('/install/env')
            ->assertRedirect(route('ptadmin.install.requirements', [
                'error' => 'requirements',
            ]));

        $this->get('/install/requirements')
            ->assertOk()
            ->assertSee('环境检查未通过')
            ->assertSee('disabled');
    }

    public function test_accepting_protocol_can_continue_to_original_target_step(): void
    {
        $this->post('/install/accept', [
            'redirect' => route('ptadmin.install.environment'),
        ])->assertRedirect('/install/env');
    }

    public function test_install_routes_are_not_registered_after_system_is_installed(): void
    {
        file_put_contents($this->installedMarkerPath(), 'installed');

        $this->refreshApplication();

        self::assertFalse(Route::has('ptadmin.install.welcome'));
        self::assertFalse(Route::has('ptadmin.install.accept'));
        self::assertFalse(Route::has('ptadmin.install.requirements'));
        self::assertFalse(Route::has('ptadmin.install.environment'));
        self::assertFalse(Route::has('ptadmin.install.stream'));

        $this->get('/install')->assertNotFound();
        $this->get('/install/requirements')->assertNotFound();
        $this->get('/install/env')->assertNotFound();
    }

    private function installedMarkerPath(): string
    {
        if (app()->bound('path.storage')) {
            return storage_path('installed');
        }

        /** @var string $packageRoot */
        $packageRoot = dirname(__DIR__, 2);

        return $packageRoot.'/storage/installed';
    }

    private function agreementMarkerPath(): string
    {
        if (!app()->bound('path.storage')) {
            /** @var string $packageRoot */
            $packageRoot = dirname(__DIR__, 2);

            return $packageRoot.'/storage/framework/ptadmin-install-agreement.lock';
        }

        return storage_path('framework/ptadmin-install-agreement.lock');
    }
}
