<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Tests\TestCase;

final class PreInstallHostContractTest extends TestCase
{
    public function createApplication(): Application
    {
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $storage = sys_get_temp_dir().'/ptadmin-admin-preinstall-'.bin2hex(random_bytes(6));
        foreach (['framework/cache', 'framework/sessions', 'framework/views', 'logs', 'public', 'bootstrap/cache'] as $directory) {
            mkdir($storage.'/'.$directory, 0700, true);
        }
        $app->useStoragePath($storage);
        $app->usePublicPath($storage.'/public');
        $app->useBootstrapPath($storage.'/bootstrap');
        $app->afterBootstrapping(LoadConfiguration::class, static function (Application $app) use ($storage): void {
            $app['config']->set([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $storage.'/database/ptadmin.db',
                'database.connections.sqlite.foreign_key_constraints' => true,
                'cache.default' => 'business-cache',
                'cache.limiter' => 'business-cache',
                'cache.stores.business-cache' => ['driver' => 'database', 'table' => 'cache', 'connection' => null],
                'session.driver' => 'database',
                'view.compiled' => $storage.'/framework/views',
                'ptadmin.install.folders' => [],
                'ptadmin.install.files' => [],
            ]);
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_host_and_install_wizard_boot_without_a_database(): void
    {
        self::assertSame([], $this->app['db']->getConnections());
        $this->get('/')->assertRedirect('/__ptadmin__/install');
        $this->getJson('/api/up')->assertRedirect('/__ptadmin__/install');
        $this->get('/__ptadmin__/install')->assertOk()
            ->assertSee('lang="zh-CN"', false)
            ->assertSee('PTAdmin 安装向导')
            ->assertDontSee('PTAdmin Installation Wizard');
        $this->getJson('/__ptadmin__/install/status')->assertOk()->assertJsonPath('installed', false);
        self::assertSame([], $this->app['db']->getConnections());
        self::assertFileDoesNotExist(storage_path('database/ptadmin.db'));
    }

    public function test_install_wizard_can_use_english_when_explicitly_selected(): void
    {
        $this->app->setLocale('en');

        $this->get('/__ptadmin__/install')->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('PTAdmin Installation Wizard')
            ->assertDontSee('PTAdmin 安装向导');
        self::assertSame([], $this->app['db']->getConnections());
        self::assertFileDoesNotExist(storage_path('database/ptadmin.db'));
    }

    public function test_new_installation_prepares_a_random_entry_without_a_fixed_admin_prefix(): void
    {
        $machine = $this->app->make(\PTAdmin\Core\Install\InstallStateMachine::class);
        $prepared = $machine->prepare();
        $prefix = $prepared['state']['web_prefix'];

        self::assertMatchesRegularExpression('/^[a-z][a-z0-9]{11}$/', $prefix);
        self::assertSame($prefix, $machine->prepare()['state']['web_prefix']);
        self::assertSame([], $this->app['db']->getConnections());
        self::assertFileDoesNotExist(storage_path('database/ptadmin.db'));
    }

    public function test_wizard_validation_does_not_require_an_app_key_or_database(): void
    {
        $this->post('/__ptadmin__/install/accept')->assertRedirect();
        $this->get('/__ptadmin__/install/requirements')->assertOk();
        $this->get('/__ptadmin__/install/account')->assertOk();
        $this->postJson('/__ptadmin__/install', [])->assertStatus(422);
        $response = $this->post('/__ptadmin__/install/stream', [])->assertOk();
        $event = json_decode(trim($response->streamedContent()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('error', $event['type']);
        self::assertSame([], $this->app['db']->getConnections());
    }

    public function test_http_middleware_preserves_installation_passwords(): void
    {
        $this->app['router']->post('/__ptadmin__/install/credential-probe', static function (\Illuminate\Http\Request $request) {
            return response()->json($request->only(['db_password', 'database', 'password', 'password_confirmation', 'app_name']));
        });
        $password = ' pass#word\\with${symbols} ';
        $this->postJson('/__ptadmin__/install/credential-probe', [
            'db_password' => $password,
            'database' => ['password' => $password],
            'password' => $password,
            'password_confirmation' => $password,
            'app_name' => ' Example ',
        ])->assertOk()
            ->assertJsonPath('db_password', $password)
            ->assertJsonPath('database.password', $password)
            ->assertJsonPath('password', $password)
            ->assertJsonPath('password_confirmation', $password)
            ->assertJsonPath('app_name', 'Example');

        self::assertSame([], $this->app['db']->getConnections());
        self::assertFileDoesNotExist(storage_path('database/ptadmin.db'));
    }

    public function test_installed_home_uses_the_generated_admin_prefix(): void
    {
        $this->app->make(\PTAdmin\Core\Install\InstallStateStore::class)
            ->markInstalled('0.1.0', 'admin_6c24fd45aea7');
        config([
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'ptadmin.web_prefix' => 'admin_6c24fd45aea7',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('基础应用已就绪')
            ->assertSee('href="http://localhost/admin_6c24fd45aea7"', false)
            ->assertSee((string) \Composer\InstalledVersions::getPrettyVersion('ptadmin/core'));
    }
}
