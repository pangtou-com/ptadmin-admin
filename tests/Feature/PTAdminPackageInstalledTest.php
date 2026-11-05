<?php

declare(strict_types=1);

namespace Tests\Feature;

use PTAdmin\Core\Providers\PTAdminServiceProvider;
use Tests\TestCase;

class PTAdminPackageInstalledTest extends TestCase
{
    public function test_ptadmin_core_package_is_available(): void
    {
        self::assertTrue(class_exists(PTAdminServiceProvider::class));
        self::assertSame('ptadmin', config('ptadmin.api_prefix'));
        self::assertSame('ptadmin', config('ptadmin.route_prefix'));
        self::assertSame('admin', config('ptadmin.web_prefix'));
    }
}
