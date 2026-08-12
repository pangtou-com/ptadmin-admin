<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use PTAdmin\Admin\Services\ApplicationInstanceService;
use PTAdmin\Admin\Tests\TestCase;
use RuntimeException;

class ApplicationInstanceServiceTest extends TestCase
{
    private string $identityPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityPath = sys_get_temp_dir().'/ptadmin-application-instance-'.getmypid().'.json';
        config()->set('ptadmin.application_instance_path', $this->identityPath);
        @unlink($this->identityPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->identityPath);

        parent::tearDown();
    }

    public function test_ensure_creates_stable_identity_and_verifiable_signature(): void
    {
        $service = app(ApplicationInstanceService::class);
        $identity = $service->ensure();
        $sameIdentity = $service->ensure();
        $payload = 'license-verification-payload';
        $signature = base64_decode($service->sign($payload), true);

        self::assertSame($identity['application_instance_id'], $sameIdentity['application_instance_id']);
        self::assertStringStartsWith('pt_', $identity['application_instance_id']);
        self::assertArrayNotHasKey('private_key', $identity);
        self::assertIsString($signature);
        self::assertSame(1, openssl_verify($payload, $signature, $identity['public_key'], OPENSSL_ALGO_SHA256));
        self::assertSame($identity['application_instance_id'], ptadmin_application_instance()['application_instance_id']);
    }

    public function test_corrupt_identity_is_not_silently_replaced(): void
    {
        file_put_contents($this->identityPath, '{invalid-json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('identity is unreadable');

        app(ApplicationInstanceService::class)->ensure();
    }

    public function test_runtime_helper_does_not_recreate_a_missing_application_identity(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('identity is not initialized');

        ptadmin_application_instance();
    }
}
