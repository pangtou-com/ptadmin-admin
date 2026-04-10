<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use PTAdmin\Admin\Tests\TestCase;

class PTAdminComposerManifestTest extends TestCase
{
    public function test_composer_manifest_uses_standard_package_layout(): void
    {
        $manifest = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('ptadmin/admin', $manifest['name'] ?? null);
        self::assertSame('library', $manifest['type'] ?? null);
        self::assertSame('src/Admin/', $manifest['autoload']['psr-4']['PTAdmin\\Admin\\'] ?? null);
        self::assertSame('src/Foundation/', $manifest['autoload']['psr-4']['PTAdmin\\Foundation\\'] ?? null);
        self::assertSame('src/Contracts/', $manifest['autoload']['psr-4']['PTAdmin\\Contracts\\'] ?? null);
        self::assertSame('src/Support/', $manifest['autoload']['psr-4']['PTAdmin\\Support\\'] ?? null);
        self::assertSame(['src/Support/Helpers/helpers.php'], $manifest['autoload']['files'] ?? null);
        self::assertSame('tests/', $manifest['autoload-dev']['psr-4']['PTAdmin\\Admin\\Tests\\'] ?? null);
        self::assertSame('vendor/bin/phpunit -c phpunit.xml.dist', $manifest['scripts']['test'] ?? null);
        self::assertSame('^1.0', $manifest['require']['ptadmin/addon'] ?? null);
        self::assertSame('^1.0', $manifest['require']['ptadmin/easy'] ?? null);
        self::assertArrayHasKey('require-dev', $manifest);
        self::assertFileExists(__DIR__.'/../../README.md');
        self::assertFileExists(__DIR__.'/../../phpunit.xml.dist');
    }
}
