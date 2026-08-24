<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use Illuminate\Support\Facades\File;
use PTAdmin\Admin\Services\PlatformSnapshotService;
use PTAdmin\Admin\Tests\TestCase;

class PlatformSnapshotServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete((string) config('ptadmin.platform_snapshot_path'));
        File::delete(dirname((string) config('ptadmin.platform_snapshot_path')).'/snapshot.lock');
        File::deleteDirectory(storage_path('framework/testing/platform-version-metadata'));

        parent::tearDown();
    }

    public function test_schedule_refresh_checks_frontend_manifest_before_full_snapshot_expires(): void
    {
        config()->set('ptadmin.platform_snapshot_ttl', 86400);
        config()->set('ptadmin.frontend_manifest_cache_ttl', 300);
        $path = (string) config('ptadmin.platform_snapshot_path');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode([
            'synced_at' => date(DATE_ATOM),
            'latest' => ['frontend_version' => '0.1.27'],
            'meta' => [
                'frontend_manifest_synced_at' => date(DATE_ATOM, time() - 301),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $service = new class() extends PlatformSnapshotService {
            public int $frontendRefreshes = 0;

            public function refreshFrontendVersion(): array
            {
                $this->frontendRefreshes++;

                return [];
            }
        };

        $service->scheduleRefresh();
        $this->app->terminate();

        self::assertSame(1, $service->frontendRefreshes);
    }

    public function test_frontend_refresh_also_reads_latest_stable_admin_package_version(): void
    {
        $fixtureRoot = storage_path('framework/testing/platform-version-metadata');
        $frontendManifestPath = $fixtureRoot.'/console-build.json';
        $adminPackageMetadataPath = $fixtureRoot.'/admin-package.json';
        File::ensureDirectoryExists($fixtureRoot);
        File::put($frontendManifestPath, json_encode([
            'latest' => '0.1.29',
        ], JSON_UNESCAPED_SLASHES));
        File::put($adminPackageMetadataPath, json_encode([
            'packages' => [
                'ptadmin/admin' => [
                    ['version' => 'dev-main'],
                    ['version' => 'v2.2.7'],
                    ['version' => '2.2.6'],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES));

        $service = new PlatformSnapshotService(
            'file://'.$frontendManifestPath,
            'file://'.$adminPackageMetadataPath
        );
        $snapshot = $service->refreshFrontendVersion();

        self::assertSame('0.1.29', $snapshot['latest']['frontend_version']);
        self::assertSame('2.2.7', $snapshot['latest']['framework_version']);
        self::assertSame('file://'.$adminPackageMetadataPath, $snapshot['source']['admin_package_metadata_url']);
        self::assertNotSame('', $snapshot['meta']['admin_package_metadata_synced_at']);
    }
}
