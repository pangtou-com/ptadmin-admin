<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Install;

use PTAdmin\Admin\Services\Install\Pipe\ConfigEnv;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminInstallConfigEnvTest extends TestCase
{
    public function test_config_env_generates_and_normalizes_web_and_api_prefixes(): void
    {
        $pipe = new ConfigEnv();
        $method = new \ReflectionMethod(ConfigEnv::class, 'prepareEnvFilePayload');
        $method->setAccessible(true);

        ob_start();
        $captured = $method->invoke($pipe, [
            'app_name' => 'PTAdmin',
            'app_url' => 'https://example.com',
            'username' => 'admin',
            'password' => 'secret123',
            'ptadmin_web_prefix' => '/manage-center/',
            'ptadmin_api_prefix' => '',
            'db_connection' => 'mysql',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'ptadmin',
            'db_username' => 'root',
            'db_password' => 'secret',
            'db_prefix' => 'pt_',
        ]);
        ob_end_clean();

        self::assertIsArray($captured);
        self::assertSame('manage-center', $captured['ptadmin_web_prefix']);
        self::assertSame(8, strlen((string) $captured['ptadmin_api_prefix']));
        self::assertStringContainsString('PTADMIN_WEB_PREFIX=manage-center', (string) $captured['__install_env_content']);
        self::assertMatchesRegularExpression('/PTADMIN_API_PREFIX=[A-Za-z0-9]{8}/', (string) $captured['__install_env_content']);
    }
}
