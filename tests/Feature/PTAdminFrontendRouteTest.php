<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use Illuminate\Support\Facades\File;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminFrontendRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents(storage_path('installed'), 'installed');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed'));

        parent::tearDown();
    }

    public function test_frontend_entry_route_uses_web_prefix_and_injects_asset_base(): void
    {
        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertSee('/vendor/ptadmin/admin/assets/admin-app.js', false);
    }

    public function test_frontend_config_script_returns_runtime_prefixes(): void
    {
        $response = $this->get('/admin/ptconfig.js');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('window.__PTADMIN__', false);
        $response->assertSee('"webBase":"/admin"', false);
        $response->assertSee('"apiBase":"/system"', false);
        $response->assertSee('"loginPath":"/system/login"', false);
        $response->assertSee('"userResourcesPath":"/system/auth/resources"', false);
        $response->assertSee('"moduleManifestPath":"/system/auth/frontends"', false);
    }

    public function test_frontend_entry_falls_back_to_published_dist_when_available(): void
    {
        File::ensureDirectoryExists(public_path('vendor/ptadmin/admin'));
        File::put(public_path('vendor/ptadmin/admin/index.html'), '<html><body>published-admin</body></html>');

        $this->get('/admin/dashboard')->assertSee('published-admin', false);

        File::deleteDirectory(public_path('vendor/ptadmin/admin'));
    }
}
