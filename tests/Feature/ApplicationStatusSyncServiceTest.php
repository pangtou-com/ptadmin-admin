<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Services\ApplicationInstanceService;
use PTAdmin\Admin\Services\ApplicationStatusSyncService;
use PTAdmin\Admin\Tests\TestCase;

class ApplicationStatusSyncServiceTest extends TestCase
{
    private string $statusDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusDirectory = storage_path('framework/testing/application-status-sync');
        File::deleteDirectory($this->statusDirectory);
        config()->set('app.name', '测试应用');
        config()->set('app.url', 'https://Admin.Example.com/ptadmin');
        config()->set('ptadmin.application_instance_path', $this->statusDirectory.'/ptadmin-application-identity.json');
        config()->set('ptadmin.application_status_path', $this->statusDirectory.'/application-status.json');
        config()->set('ptadmin.application_sync_url', 'https://platform.test/api-addon/application-sync');
        config()->set('ptadmin.application_sync_ttl', 21600);
        app(ApplicationInstanceService::class)->ensure();

        Addon::swap(new ApplicationStatusAddonManager([
            'cms' => [
                'code' => 'cms',
                'version' => '1.2.0',
                'disable' => false,
                'license_required' => false,
            ],
        ]));
    }

    protected function tearDown(): void
    {
        Addon::swap(new ApplicationStatusAddonManager([]));
        File::deleteDirectory($this->statusDirectory);

        parent::tearDown();
    }

    public function test_sync_sends_signed_application_and_addon_status_without_platform_login(): void
    {
        $service = $this->serviceWithResponses([
            [
                'code' => 0,
                'data' => [
                    'contract_version' => 1,
                    'synced_at' => date(DATE_ATOM),
                    'latest' => [
                        'frontend_version' => '0.2.0',
                        'backend_version' => '1.2.0',
                    ],
                    'addons' => [[
                        'code' => 'cms',
                        'installed_version' => '1.2.0',
                        'latest_version' => '1.3.0',
                        'update_available' => true,
                        'authorized' => true,
                    ]],
                    'advice' => [[
                        'id' => 'cms-update',
                        'level' => 'warning',
                        'title' => 'CMS 可更新',
                        'message' => 'CMS 1.3.0 已发布。',
                    ]],
                ],
            ],
        ]);

        $status = $service->sync(true);

        self::assertSame('success', $status['status']);
        self::assertSame('cms-update', $status['response']['advice'][0]['id']);
        self::assertCount(1, $service->publicSummary($status)['addon_updates']);

        $body = $service->requests[0];
        $payload = $body['payload'] ?? [];
        self::assertSame('application-sync-v1', $body['signature_version'] ?? null);
        self::assertSame('测试应用', $payload['application_name'] ?? null);
        self::assertSame('admin.example.com', $payload['domain'] ?? null);
        self::assertSame('cms', $payload['addons'][0]['code'] ?? null);
        self::assertSame('not_required', $payload['addons'][0]['license']['state'] ?? null);
        self::assertArrayNotHasKey('activation_token', $payload['addons'][0]['license'] ?? []);
        self::assertArrayNotHasKey('authorization', $body);

        $canonicalPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::assertSame(hash('sha256', (string) $canonicalPayload), $body['payload_hash'] ?? null);
        $signaturePayload = implode("\n", [
            'application-sync-v1',
            $body['application_instance_id'],
            (string) $body['timestamp'],
            $body['nonce'],
            $body['payload_hash'],
        ]);
        self::assertSame(1, openssl_verify(
            $signaturePayload,
            base64_decode((string) $body['signature'], true),
            (string) $body['instance_public_key'],
            OPENSSL_ALGO_SHA256
        ));
    }

    public function test_failed_sync_keeps_the_last_successful_platform_response(): void
    {
        $service = $this->serviceWithResponses([
            [
                'contract_version' => 1,
                'synced_at' => date(DATE_ATOM),
                'latest' => [],
                'addons' => [],
                'advice' => [[
                    'id' => 'saved-advice',
                    'level' => 'info',
                    'title' => '已保存建议',
                    'message' => '最近一次成功结果。',
                ]],
            ],
            ['__status' => 503],
        ]);

        self::assertSame('success', $service->sync(true)['status']);
        $failed = $service->sync(true);

        self::assertSame('failed', $failed['status']);
        self::assertSame('PLATFORM_UNAVAILABLE', $failed['last_error']['code']);
        self::assertSame('saved-advice', $failed['response']['advice'][0]['id']);
        self::assertNotSame('', $failed['last_succeeded_at']);
    }

    public function test_sync_uses_configured_host_ip_when_platform_dns_is_unavailable(): void
    {
        config()->set('ptadmin.application_sync_host_ip', '61.147.93.222');
        $service = $this->serviceWithResponses([]);

        $options = $service->curlResolveOption('https://www.pangtou.com/api-addon/application-sync');

        self::assertSame(
            ['www.pangtou.com:443:61.147.93.222'],
            $options[CURLOPT_RESOLVE] ?? null
        );
    }

    public function test_sync_does_not_force_host_resolution_when_host_ip_is_empty(): void
    {
        config()->set('ptadmin.application_sync_host_ip', '');
        $service = $this->serviceWithResponses([]);

        self::assertSame([], $service->curlResolveOption('https://www.pangtou.com/api-addon/application-sync'));
    }

    public function test_platform_validation_error_is_preserved_in_cached_status(): void
    {
        $service = $this->serviceWithResponses([
            [
                'code' => 42201,
                'message' => '请求内容无效',
                'error_code' => 'INVALID_PAYLOAD',
            ],
        ]);

        $status = $service->sync(true);

        self::assertSame('INVALID_PAYLOAD', $status['last_error']['code']);
        self::assertSame('[INVALID_PAYLOAD] 请求内容无效', $status['last_error']['message']);
    }

    public function test_sync_domain_prefers_current_request_host(): void
    {
        app()->instance('request', Request::create('https://Tenant.Example.com:8443/ptadmin/dashboard'));
        $service = $this->serviceWithResponses([]);

        self::assertSame('tenant.example.com', $service->resolvedApplicationDomain());
    }

    public function test_sync_domain_falls_back_to_app_url_for_local_request(): void
    {
        app()->instance('request', Request::create('http://localhost/ptadmin/dashboard'));
        $service = $this->serviceWithResponses([]);

        self::assertSame('admin.example.com', $service->resolvedApplicationDomain());
    }

    /**
     * @param array<int, array<string, mixed>> $responses
     */
    private function serviceWithResponses(array $responses): TestApplicationStatusSyncService
    {
        return new TestApplicationStatusSyncService(
            app(ApplicationInstanceService::class),
            $responses
        );
    }
}

final class TestApplicationStatusSyncService extends ApplicationStatusSyncService
{
    /** @var array<int, array<string, mixed>> */
    public array $requests = [];

    /** @var array<int, array<string, mixed>> */
    private array $responses;

    /** @param array<int, array<string, mixed>> $responses */
    public function __construct(
        ApplicationInstanceService $applicationInstanceService,
        array $responses
    ) {
        parent::__construct($applicationInstanceService);
        $this->responses = $responses;
    }

    /** @param array<string, mixed> $request
     *  @return array{status:int, body:string, request_id:string}
     */
    protected function postJson(string $url, array $request): array
    {
        $this->requests[] = $request;
        $response = array_shift($this->responses) ?? ['__status' => 500];
        $status = (int) ($response['__status'] ?? 200);
        unset($response['__status']);

        return [
            'status' => $status,
            'body' => (string) json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'request_id' => '',
        ];
    }

    /** @return array<int, array<int, string>> */
    public function curlResolveOption(string $url): array
    {
        return $this->resolveCurlOption($url);
    }

    public function resolvedApplicationDomain(): string
    {
        return $this->applicationDomain();
    }
}

final class ApplicationStatusAddonManager
{
    /** @var array<string, array<string, mixed>> */
    private array $addons;

    /** @param array<string, array<string, mixed>> $addons */
    public function __construct(array $addons)
    {
        $this->addons = $addons;
    }

    /** @return array<string, array<string, mixed>> */
    public function getInstalledAddons(): array
    {
        return $this->addons;
    }

    /** @return array<string, array<string, mixed>> */
    public function getAddons(): array
    {
        return $this->addons;
    }

    public function getAddonVersion(string $code): ?string
    {
        return isset($this->addons[$code]) ? (string) ($this->addons[$code]['version'] ?? '') : null;
    }
}
