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
        config()->set('ptadmin.application_sync_jitter', 0);
        config()->set('ptadmin.web_prefix', 'application-status-sync-admin');
        $this->writePublishedFrontendLock('0.1.27');
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
        File::deleteDirectory(public_path('application-status-sync-admin'));

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
        self::assertSame(0, $status['failure_count']);
        self::assertSame(21600, strtotime($status['next_attempt_at']) - strtotime($status['last_succeeded_at']));
        self::assertSame('cms-update', $status['response']['advice'][0]['id']);
        self::assertCount(1, $service->publicSummary($status)['addon_updates']);

        $body = $service->requests[0];
        $payload = $body['payload'] ?? [];
        self::assertSame('application-sync-v1', $body['signature_version'] ?? null);
        self::assertSame('测试应用', $payload['application_name'] ?? null);
        self::assertSame('admin.example.com', $payload['domain'] ?? null);
        self::assertSame('0.1.27', $payload['frontend_version'] ?? null);
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
        self::assertSame(1, $failed['failure_count']);
        self::assertSame(300, strtotime($failed['next_attempt_at']) - strtotime($failed['last_attempted_at']));
    }

    public function test_failed_sync_is_throttled_and_uses_progressive_backoff(): void
    {
        $service = $this->serviceWithResponses([
            ['__status' => 503],
            ['__status' => 503],
        ]);

        $first = $service->sync(true);
        $throttled = $service->sync(false);

        self::assertSame(1, $first['failure_count']);
        self::assertSame(1, $throttled['failure_count']);
        self::assertCount(1, $service->requests);

        $first['next_attempt_at'] = date(DATE_ATOM, time() - 1);
        File::put(
            config('ptadmin.application_status_path'),
            json_encode($first, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $second = $service->sync(false);

        self::assertSame(2, $second['failure_count']);
        self::assertSame(900, strtotime($second['next_attempt_at']) - strtotime($second['last_attempted_at']));
        self::assertCount(2, $service->requests);
    }

    public function test_automatic_sync_interval_cannot_be_configured_below_one_hour(): void
    {
        config()->set('ptadmin.application_sync_ttl', 60);
        $service = $this->serviceWithResponses([[
            'contract_version' => 1,
            'synced_at' => date(DATE_ATOM),
            'latest' => [],
            'addons' => [],
            'advice' => [],
        ]]);

        $status = $service->sync(true);

        self::assertSame(3600, strtotime($status['next_attempt_at']) - strtotime($status['last_succeeded_at']));
    }

    public function test_dashboard_aggregates_platform_advice_by_highest_level(): void
    {
        Addon::swap(new ApplicationStatusAddonManager([]));
        $service = $this->serviceWithResponses([[
            'contract_version' => 1,
            'synced_at' => date(DATE_ATOM),
            'latest' => [],
            'addons' => [],
            'advice' => [
                ['id' => 'info', 'level' => 'info', 'title' => '普通提醒', 'message' => '普通内容'],
                ['id' => 'danger', 'level' => 'danger', 'title' => '安全提醒', 'message' => '需要立即处理'],
                ['id' => 'warning', 'level' => 'warning', 'title' => '更新提醒', 'message' => '存在可用更新'],
            ],
        ]]);

        $summary = $service->publicSummary($service->sync(true));

        self::assertCount(1, $summary['application_advice']);
        self::assertSame('platform-advice-summary', $summary['application_advice'][0]['id']);
        self::assertSame('danger', $summary['application_advice'][0]['level']);
        self::assertSame('平台检查发现 3 项提醒', $summary['application_advice'][0]['title']);
        self::assertStringContainsString('安全提醒', $summary['application_advice'][0]['message']);
    }

    public function test_dashboard_aggregates_addon_license_warnings(): void
    {
        $service = $this->serviceWithResponses([]);
        $method = new \ReflectionMethod(ApplicationStatusSyncService::class, 'dashboardLicenseAdvice');
        $method->setAccessible(true);
        $advice = $method->invoke($service, [
            ['code' => 'blocked-addon', 'state' => 'blocked'],
            ['code' => 'grace-addon', 'state' => 'grace'],
            ['code' => 'legacy-addon', 'state' => 'legacy_review'],
        ]);

        self::assertIsArray($advice);
        self::assertSame('addon-license-summary', $advice['id']);
        self::assertSame('danger', $advice['level']);
        self::assertSame('3 个插件需要关注授权状态', $advice['title']);
        self::assertStringContainsString('已阻断 1 个', $advice['message']);
        self::assertStringContainsString('待激活 1 个', $advice['message']);
        self::assertStringContainsString('待归档 1 个', $advice['message']);
        self::assertSame('/cloud/apps', $advice['action']['url']);
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

    public function test_identity_storage_failure_skips_sync_without_recording_an_error(): void
    {
        $blockedDirectory = $this->statusDirectory.'/identity-parent';
        File::delete(config('ptadmin.application_instance_path'));
        File::delete(config('ptadmin.application_instance_path').'.lock');
        File::put($blockedDirectory, 'not-a-directory');
        config()->set('ptadmin.application_instance_path', $blockedDirectory.'/identity.json');
        $service = $this->serviceWithResponses([[
            'contract_version' => 1,
            'synced_at' => date(DATE_ATOM),
            'latest' => [],
            'addons' => [],
            'advice' => [],
        ]]);

        $status = $service->sync(true);

        self::assertSame('never', $status['status']);
        self::assertNull($status['last_error']);
        self::assertCount(0, $service->requests);
        self::assertFileDoesNotExist(config('ptadmin.application_status_path'));
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

    private function writePublishedFrontendLock(string $version): void
    {
        $path = public_path(admin_web_prefix().'/.release-lock.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(['version' => $version], JSON_UNESCAPED_SLASHES));
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
