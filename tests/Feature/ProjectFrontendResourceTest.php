<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use Illuminate\Support\Facades\File;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Tests\TestCase;

class ProjectFrontendResourceTest extends TestCase
{
    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestPath = storage_path('app/project-frontend-resources.json');
        File::delete($this->manifestPath);
        config()->set('ptadmin.project_frontend_code', '__app__');
        config()->set('ptadmin.project_frontend_resource_manifest', $this->manifestPath);
    }

    protected function tearDown(): void
    {
        File::delete($this->manifestPath);

        parent::tearDown();
    }

    public function test_founder_can_preview_project_resources_in_debug_mode(): void
    {
        $this->migratePackageTables();
        config()->set('app.debug', true);
        $this->writeManifest(array_reverse($this->projectDefinitions()));

        $founder = $this->createAdminAccount([
            'username' => 'project-resource-founder',
            'password' => 'secret123',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/auth/resources');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'roles' => ['创始人'],
            ],
        ]);

        $resources = $this->flattenResources((array) $response->json('data.resources'));

        self::assertSame('项目功能', $resources['project']['title'] ?? null);
        self::assertSame('订单管理', $resources['project.orders']['title'] ?? null);
        self::assertSame('__app__', $resources['project.orders']['module'] ?? null);
        self::assertSame('micro-app:__app__', $resources['project.orders']['page_key'] ?? null);
        self::assertSame('/orders', $resources['project.orders']['route'] ?? null);
        self::assertLessThan(0, (int) ($resources['project.orders']['id'] ?? 0));
        self::assertSame(0, AdminResource::query()->where('name', 'project.orders')->count());
    }

    public function test_project_resources_are_not_previewed_outside_debug_mode(): void
    {
        $this->migratePackageTables();
        config()->set('app.debug', false);
        $this->writeManifest($this->projectDefinitions());

        $founder = $this->createAdminAccount([
            'username' => 'project-resource-production',
            'password' => 'secret123',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/auth/resources');

        $resources = $this->flattenResources((array) $response->json('data.resources'));
        self::assertArrayNotHasKey('project', $resources);
        self::assertArrayNotHasKey('project.orders', $resources);
    }

    public function test_admin_resource_command_synchronizes_and_disables_removed_project_resources(): void
    {
        $this->migratePackageTables();
        $this->writeManifest(array_reverse($this->projectDefinitions()));

        $this->artisan('admin:resource')
            ->expectsOutput('项目二开资源同步完成')
            ->expectsOutput('清单：'.$this->manifestPath)
            ->expectsOutput('资源数量：3')
            ->assertExitCode(0);

        self::assertDatabaseHas('admin_resources', [
            'name' => 'project.orders',
            'module' => '__app__',
            'page_key' => 'micro-app:__app__',
            'addon_code' => '__app__',
            'status' => 1,
        ]);
        $parentId = (int) AdminResource::query()->where('name', 'project')->value('id');
        self::assertSame($parentId, (int) AdminResource::query()->where('name', 'project.orders')->value('parent_id'));

        $this->writeManifest([$this->projectDefinitions()[0]]);

        $this->artisan('admin:resource')
            ->expectsOutput('资源数量：1')
            ->assertExitCode(0);

        self::assertDatabaseHas('admin_resources', [
            'name' => 'project',
            'status' => 1,
        ]);
        self::assertDatabaseHas('admin_resources', [
            'name' => 'project.orders',
            'status' => 0,
        ]);
        self::assertDatabaseHas('admin_resources', [
            'name' => 'project.orders.export',
            'status' => 0,
        ]);
    }

    public function test_admin_resource_command_rejects_missing_manifest(): void
    {
        $this->migratePackageTables();

        $this->artisan('admin:resource')
            ->expectsOutput('项目资源清单不存在：'.$this->manifestPath)
            ->assertExitCode(1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function projectDefinitions(): array
    {
        return [
            [
                'name' => 'project',
                'title' => '项目功能',
                'type' => 'dir',
                'is_nav' => 1,
                'sort' => 100,
            ],
            [
                'name' => 'project.orders',
                'title' => '订单管理',
                'parent' => 'project',
                'type' => 'nav',
                'page_key' => 'micro-app:__app__',
                'route' => '/orders',
                'is_nav' => 1,
                'sort' => 10,
            ],
            [
                'name' => 'project.orders.export',
                'title' => '导出订单',
                'parent' => 'project.orders',
                'type' => 'btn',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     */
    private function writeManifest(array $definitions): void
    {
        File::put($this->manifestPath, (string) json_encode([
            'resources' => $definitions,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array<int, array<string, mixed>> $resources
     * @return array<string, array<string, mixed>>
     */
    private function flattenResources(array $resources): array
    {
        $results = [];
        foreach ($resources as $resource) {
            $results[(string) $resource['name']] = $resource;
            if (!empty($resource['children']) && \is_array($resource['children'])) {
                $results += $this->flattenResources($resource['children']);
            }
        }

        return $results;
    }
}
