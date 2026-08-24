<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use PTAdmin\Admin\Exceptions\ApplicationInstanceUnavailableException;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\AddonInstallationRegistry;
use RuntimeException;
use Throwable;

class ApplicationStatusSyncService
{
    public const CONTRACT_VERSION = 1;
    public const SIGNATURE_VERSION = 'application-sync-v1';

    private const LOCK_TIMEOUT = 600;
    private const LICENSE_PROTOCOL = 'ptadmin-addon-license@1';
    private const FAILURE_RETRY_SECONDS = [300, 900, 3600, 10800, 21600];

    private ApplicationInstanceService $applicationInstanceService;
    private AdminFrontendBuildService $adminFrontendBuildService;

    public function __construct(
        ApplicationInstanceService $applicationInstanceService,
        ?AdminFrontendBuildService $adminFrontendBuildService = null
    ) {
        $this->applicationInstanceService = $applicationInstanceService;
        $this->adminFrontendBuildService = $adminFrontendBuildService ?? new AdminFrontendBuildService();
    }

    /** @return array<string, mixed> */
    public function read(): array
    {
        $path = $this->statusPath();
        if (!is_file($path) || !is_readable($path)) {
            return $this->emptyStatus();
        }

        $content = file_get_contents($path);
        $status = false === $content ? null : json_decode($content, true);

        return is_array($status) ? array_merge($this->emptyStatus(), $status) : $this->emptyStatus();
    }

    public function isStale(?array $status = null): bool
    {
        $status = is_array($status) ? $status : $this->read();
        $nextAttemptAt = strtotime((string) ($status['next_attempt_at'] ?? ''));
        if (false !== $nextAttemptAt && $nextAttemptAt > 0) {
            return $nextAttemptAt <= time();
        }
        $lastSucceededAt = strtotime((string) ($status['last_succeeded_at'] ?? ''));
        if (false === $lastSucceededAt || $lastSucceededAt <= 0) {
            return true;
        }

        return $lastSucceededAt + $this->ttl() <= time();
    }

    public function scheduleSync(): void
    {
        if (!$this->shouldAttempt()) {
            return;
        }

        app()->terminating(function (): void {
            if (!$this->shouldAttempt() || !$this->acquireLock()) {
                return;
            }
            try {
                try {
                    $this->performSync();
                } catch (Throwable $exception) {
                }
            } finally {
                $this->releaseLock();
            }
        });
    }

    /** @return array<string, mixed> */
    public function sync(bool $force = false): array
    {
        $status = $this->read();
        if (!$force && !$this->shouldAttempt($status)) {
            return $status;
        }
        if (!$this->acquireLock()) {
            return $status;
        }

        try {
            return $this->performSync();
        } finally {
            $this->releaseLock();
        }
    }

    /** @return array<string, mixed> */
    public function publicSummary(?array $status = null): array
    {
        $status = is_array($status) ? $status : $this->read();
        $response = is_array($status['response'] ?? null) ? $status['response'] : [];
        $addons = array_values(array_filter((array) ($response['addons'] ?? []), static function ($addon): bool {
            return is_array($addon) && true === ($addon['update_available'] ?? false);
        }));
        $licenseWarnings = [];
        foreach ($this->installedAddons() as $addon) {
            $installation = is_array($addon['installation'] ?? null) ? $addon['installation'] : [];
            if ('platform' !== (string) ($installation['management_scope'] ?? '')) {
                continue;
            }
            $license = is_array($addon['license'] ?? null) ? $addon['license'] : [];
            $state = (string) ($license['runtime_state'] ?? '');
            if (!in_array($state, ['grace', 'legacy_review', 'blocked', 'unknown'], true)) {
                continue;
            }
            $licenseWarnings[] = [
                'code' => (string) ($addon['code'] ?? ''),
                'version' => (string) ($addon['version'] ?? ''),
                'state' => $state,
                'reason_code' => (string) ($license['reason_code'] ?? ''),
                'grace_ends_at' => (int) ($license['grace_ends_at'] ?? 0),
            ];
        }
        $advice = $this->dashboardPlatformAdvice((array) ($response['advice'] ?? []));
        $licenseAdvice = $this->dashboardLicenseAdvice($licenseWarnings);
        if (null !== $licenseAdvice) {
            $advice[] = $licenseAdvice;
        }

        return [
            'application_sync_status' => (string) ($status['status'] ?? 'never'),
            'application_sync_last_attempted_at' => (string) ($status['last_attempted_at'] ?? ''),
            'application_sync_last_succeeded_at' => (string) ($status['last_succeeded_at'] ?? ''),
            'application_sync_next_attempt_at' => (string) ($status['next_attempt_at'] ?? ''),
            'application_sync_stale' => $this->isStale($status),
            'application_sync_error' => is_array($status['last_error'] ?? null) ? $status['last_error'] : null,
            'application_advice' => $advice,
            'addon_updates' => $addons,
            'addon_license_warnings' => $licenseWarnings,
        ];
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, mixed>>
     */
    private function dashboardPlatformAdvice(array $items): array
    {
        $items = array_values(array_filter($items, static function ($item): bool {
            return is_array($item) && in_array($item['level'] ?? null, ['danger', 'warning', 'info'], true);
        }));
        if (count($items) <= 1) {
            return $items;
        }

        $priority = ['danger' => 3, 'warning' => 2, 'info' => 1];
        usort($items, static function (array $left, array $right) use ($priority): int {
            return ($priority[(string) ($right['level'] ?? '')] ?? 0)
                <=> ($priority[(string) ($left['level'] ?? '')] ?? 0);
        });
        $primary = $items[0];
        $primaryTitle = trim((string) ($primary['title'] ?? ''));
        $primaryMessage = trim((string) ($primary['message'] ?? ''));
        $message = '' !== $primaryTitle && '' !== $primaryMessage
            ? $primaryTitle.'：'.$primaryMessage
            : $primaryTitle.$primaryMessage;

        return [[
            'id' => 'platform-advice-summary',
            'level' => (string) ($primary['level'] ?? 'warning'),
            'title' => sprintf('平台检查发现 %d 项提醒', count($items)),
            'message' => $message.' 其余提醒可在应用管理中处理。',
            'action' => [
                'label' => '查看应用',
                'url' => '/cloud/apps',
                'target' => '_self',
            ],
        ]];
    }

    /**
     * @param array<int, array<string, mixed>> $warnings
     * @return array<string, mixed>|null
     */
    private function dashboardLicenseAdvice(array $warnings): ?array
    {
        if ([] === $warnings) {
            return null;
        }

        $counts = array_count_values(array_map(static function (array $warning): string {
            return (string) ($warning['state'] ?? 'unknown');
        }, $warnings));
        $parts = [];
        foreach ([
            'blocked' => '已阻断',
            'grace' => '待激活',
            'legacy_review' => '待归档',
            'unknown' => '待同步',
        ] as $state => $label) {
            if (($counts[$state] ?? 0) > 0) {
                $parts[] = $label.' '.(int) $counts[$state].' 个';
            }
        }

        return [
            'id' => 'addon-license-summary',
            'level' => ($counts['blocked'] ?? 0) > 0 ? 'danger' : 'warning',
            'title' => sprintf('%d 个插件需要关注授权状态', count($warnings)),
            'message' => implode('，', $parts).'。',
            'action' => [
                'label' => '管理插件授权',
                'url' => '/cloud/apps',
                'target' => '_self',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function performSync(): array
    {
        $attemptedAt = date(DATE_ATOM);

        try {
            $request = $this->buildRequest();
            $response = $this->postJson($this->syncUrl(), $request);

            $body = json_decode($response['body'], true);
            if (!is_array($body)) {
                throw new RuntimeException('平台未返回有效的 JSON 数据。');
            }
            if (array_key_exists('code', $body) && 0 !== (int) $body['code']) {
                $errorCode = trim((string) ($body['error_code'] ?? ''));
                $message = trim((string) ($body['message'] ?? '平台拒绝了应用状态同步请求。'));
                $exception = new RuntimeException(
                    ('' !== $errorCode ? '['.$errorCode.'] ' : '').$message,
                    (int) ($body['code'] ?? 0)
                );
                if ('' !== $response['request_id']) {
                    $exception = new RuntimeException($exception->getMessage().'（请求 ID：'.$response['request_id'].'）', $exception->getCode(), $exception);
                }
                throw $exception;
            }
            if ($response['status'] < 200 || $response['status'] >= 300) {
                throw new RuntimeException(sprintf('平台返回 HTTP %d。', $response['status']));
            }

            $payload = is_array($body['data'] ?? null) ? $body['data'] : $body;
            $normalized = $this->normalizeResponse($payload);
            $status = [
                'status' => 'success',
                'last_attempted_at' => $attemptedAt,
                'last_succeeded_at' => $attemptedAt,
                'next_attempt_at' => date(DATE_ATOM, time() + $this->ttl() + $this->successJitterSeconds()),
                'failure_count' => 0,
                'last_error' => null,
                'response' => $normalized,
            ];
        } catch (ApplicationInstanceUnavailableException $exception) {
            return $this->read();
        } catch (Throwable $exception) {
            $status = $this->read();
            $failureCount = min(count(self::FAILURE_RETRY_SECONDS), (int) ($status['failure_count'] ?? 0) + 1);
            $status['status'] = 'failed';
            $status['last_attempted_at'] = $attemptedAt;
            $status['next_attempt_at'] = date(DATE_ATOM, time() + self::FAILURE_RETRY_SECONDS[$failureCount - 1]);
            $status['failure_count'] = $failureCount;
            $status['last_error'] = [
                'code' => $this->errorCode($exception),
                'message' => $exception->getMessage(),
            ];
        }

        $this->write($status);

        return $status;
    }

    /** @return array<string, mixed> */
    private function buildRequest(): array
    {
        $payload = [
            'application_name' => (string) config('app.name', 'PTAdmin'),
        ];
        $domain = $this->applicationDomain();
        if ('' !== $domain) {
            $payload['domain'] = $domain;
        }
        $payload = array_merge($payload, [
            'backend_version' => get_frame_version(),
            'frontend_version' => $this->frontendVersion(),
            'addons' => $this->installedAddons(),
        ]);
        $canonicalPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $canonicalPayload) {
            throw new RuntimeException('无法编码应用状态同步数据。');
        }

        $applicationInstanceId = $this->applicationInstanceService->applicationInstanceId();
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        $payloadHash = hash('sha256', $canonicalPayload);
        $signaturePayload = implode("\n", [
            self::SIGNATURE_VERSION,
            $applicationInstanceId,
            (string) $timestamp,
            $nonce,
            $payloadHash,
        ]);

        return [
            'contract_version' => self::CONTRACT_VERSION,
            'signature_version' => self::SIGNATURE_VERSION,
            'application_instance_id' => $applicationInstanceId,
            'instance_public_key' => $this->applicationInstanceService->publicKey(),
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'payload_hash' => $payloadHash,
            'signature' => $this->applicationInstanceService->sign($signaturePayload),
            'payload' => $payload,
        ];
    }

    protected function applicationDomain(): string
    {
        if (app()->bound('request')) {
            try {
                $requestHost = $this->normalizeDomain((string) app('request')->getHost());
                if ('' !== $requestHost && 'localhost' !== $requestHost) {
                    return $requestHost;
                }
            } catch (Throwable $exception) {
            }
        }

        $appUrl = (string) config('app.url', '');
        $host = parse_url(false !== strpos($appUrl, '://') ? $appUrl : '//'.$appUrl, PHP_URL_HOST);

        return $this->normalizeDomain((string) $host);
    }

    private function normalizeDomain(string $domain): string
    {
        $normalized = rtrim(strtolower(trim($domain)), '.');
        if ('' === $normalized || strlen($normalized) > 253) {
            return '';
        }
        if (false !== filter_var($normalized, FILTER_VALIDATE_IP)) {
            return '';
        }
        if (!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $normalized)) {
            return '';
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array{status:int, body:string, request_id:string}
     */
    protected function postJson(string $url, array $request): array
    {
        $body = json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $body) {
            throw new RuntimeException('无法编码应用状态同步请求。');
        }

        $handle = curl_init($url);
        if (false === $handle) {
            throw new RuntimeException('无法初始化应用状态同步请求。');
        }
        $responseRequestId = '';
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$responseRequestId): int {
                if (preg_match('/^X-Request-Id:\s*(.+)$/i', $header, $matches)) {
                    $responseRequestId = trim((string) $matches[1]);
                }

                return strlen($header);
            },
        ] + $this->resolveCurlOption($url));

        $responseBody = curl_exec($handle);
        if (!is_string($responseBody)) {
            $message = curl_error($handle);
            $errorNumber = curl_errno($handle);
            curl_close($handle);

            throw new RuntimeException($this->networkErrorMessage($url, $errorNumber, $message));
        }
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return ['status' => $status, 'body' => $responseBody, 'request_id' => $responseRequestId];
    }

    /** @return array<int, array<int, string>> */
    protected function resolveCurlOption(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $ip = trim((string) config('ptadmin.application_sync_host_ip', '61.147.93.222'));
        if (!is_string($host) || '' === $host || '' === $ip || false === filter_var($ip, FILTER_VALIDATE_IP)) {
            return [];
        }

        return [
            CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, is_int($port) ? $port : 443, $ip)],
        ];
    }

    private function networkErrorMessage(string $url, int $errorNumber, string $message): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if (CURLE_COULDNT_RESOLVE_HOST === $errorNumber) {
            return sprintf('无法解析平台域名 %s，请检查服务器 DNS 或 PTADMIN_APPLICATION_SYNC_HOST_IP 配置。', $host);
        }
        if (CURLE_COULDNT_CONNECT === $errorNumber || CURLE_OPERATION_TIMEDOUT === $errorNumber) {
            return sprintf('无法连接平台 %s，请检查服务器外网访问和防火墙配置。', $host);
        }

        return '应用状态同步请求失败：'.$message;
    }

    /** @return array<int, array<string, mixed>> */
    private function installedAddons(): array
    {
        try {
            $addons = Addon::getInstalledAddons();
        } catch (Throwable $exception) {
            $addons = Addon::getAddons();
        }
        if (!is_array($addons)) {
            return [];
        }

        $results = [];
        foreach ($addons as $key => $manifest) {
            if (!is_array($manifest)) {
                continue;
            }
            $code = trim((string) ($manifest['code'] ?? $key));
            if ('' === $code) {
                continue;
            }

            $licenseRequired = true === ($manifest['license_required'] ?? false)
                || self::LICENSE_PROTOCOL === trim((string) ($manifest['license_protocol'] ?? ''));
            $license = $this->licenseSummary($code, $licenseRequired);
            $installation = $this->installationSummary($code);
            $results[$code] = [
                'code' => $code,
                'version' => trim((string) ($manifest['version'] ?? Addon::getAddonVersion($code) ?? '')),
                'enabled' => !((bool) ($manifest['disable'] ?? false)),
                'license' => $license,
                'installation' => $installation,
            ];
        }
        ksort($results);

        return array_values($results);
    }

    /** @return array<string, mixed> */
    private function licenseSummary(string $code, bool $required): array
    {
        $serviceClass = 'PTAdmin\\Addon\\Service\\AddonLicenseService';
        if (!class_exists($serviceClass)) {
            return [
                'required' => $required,
                'state' => $required ? 'unknown' : 'not_required',
            ];
        }

        try {
            $license = app($serviceClass)->status($code);
            $runtime = app($serviceClass)->runtimeStatus($code);
        } catch (Throwable $exception) {
            return [
                'required' => $required,
                'state' => 'unreadable',
                'runtime_state' => 'unknown',
            ];
        }
        if (null === $license) {
            return [
                'required' => $required,
                'state' => $required ? 'missing' : 'not_required',
                'runtime_state' => (string) ($runtime['state'] ?? 'unknown'),
                'reason_code' => (string) ($runtime['reason_code'] ?? ''),
                'grace_started_at' => (int) ($runtime['grace_started_at'] ?? 0),
                'grace_ends_at' => (int) ($runtime['grace_ends_at'] ?? 0),
            ];
        }

        return [
            'required' => $required,
            'state' => true === ($license['is_current_instance'] ?? false) ? 'local_present' : 'instance_mismatch',
            'runtime_state' => (string) ($runtime['state'] ?? 'unknown'),
            'license_id' => (int) ($license['license_id'] ?? 0),
            'activation_status' => (string) ($license['activation_status'] ?? ''),
            'last_verified_at' => (int) ($license['last_verified_at'] ?? 0),
            'valid_until' => (int) ($license['valid_until'] ?? 0),
            'reason_code' => (string) ($license['reason_code'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function installationSummary(string $code): array
    {
        $registryClass = AddonInstallationRegistry::class;
        if (!class_exists($registryClass)) {
            return ['management_scope' => 'legacy_unknown'];
        }

        try {
            $registry = app($registryClass);
            $installation = $registry->get($code);
            $managementScope = method_exists($registry, 'managementScope')
                ? (string) $registry->managementScope($code)
                : $this->legacyManagementScope($installation);
        } catch (Throwable $exception) {
            return ['management_scope' => 'legacy_unknown'];
        }
        if (!is_array($installation)) {
            return ['management_scope' => $managementScope];
        }

        return [
            'management_scope' => $managementScope,
            'version' => (string) ($installation['version'] ?? ''),
            'source' => (string) ($installation['source'] ?? ''),
            'installed_at' => (string) ($installation['installed_at'] ?? ''),
            'addon_version_id' => (int) ($installation['addon_version_id'] ?? 0),
            'package_hash' => (string) ($installation['package_hash'] ?? ''),
            'release_license_policy' => (string) ($installation['release_license_policy'] ?? ''),
            'entitlement_id' => (string) ($installation['entitlement_id'] ?? ''),
            'entitlement_scope' => (string) ($installation['entitlement_scope'] ?? ''),
        ];
    }

    /** @param array<string, mixed>|null $installation */
    private function legacyManagementScope(?array $installation): string
    {
        $source = is_array($installation) ? (string) ($installation['source'] ?? '') : '';
        if ('marketplace' === $source) {
            return 'platform';
        }
        if ('local_package' === $source) {
            return 'local';
        }

        return 'legacy_unknown';
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function normalizeResponse(array $payload): array
    {
        if (self::CONTRACT_VERSION !== (int) ($payload['contract_version'] ?? 0)) {
            throw new RuntimeException('平台应用状态同步协议版本不受支持。');
        }

        $latest = is_array($payload['latest'] ?? null) ? $payload['latest'] : [];
        $addons = [];
        foreach ((array) ($payload['addons'] ?? []) as $addon) {
            if (!is_array($addon) || '' === trim((string) ($addon['code'] ?? ''))) {
                continue;
            }
            $licenseDecision = is_array($addon['license_decision'] ?? null)
                ? $this->applyLicenseDecision($addon['license_decision'], trim((string) $addon['code']))
                : null;
            $addons[] = [
                'code' => trim((string) $addon['code']),
                'installed_version' => trim((string) ($addon['installed_version'] ?? '')),
                'latest_version' => trim((string) ($addon['latest_version'] ?? '')),
                'update_available' => true === ($addon['update_available'] ?? false),
                'authorized' => array_key_exists('authorized', $addon) ? (bool) $addon['authorized'] : null,
                'license_state' => is_array($licenseDecision) ? (string) ($licenseDecision['state'] ?? '') : '',
                'license_reason_code' => is_array($licenseDecision) ? (string) ($licenseDecision['reason_code'] ?? '') : '',
                'license_grace_ends_at' => is_array($licenseDecision) ? (int) ($licenseDecision['grace_ends_at'] ?? 0) : 0,
                'security_alerts' => array_values((array) ($addon['security_alerts'] ?? [])),
            ];
        }

        $advice = [];
        foreach ((array) ($payload['advice'] ?? []) as $item) {
            if (!is_array($item) || !in_array($item['level'] ?? null, ['danger', 'warning', 'info'], true)) {
                continue;
            }
            $advice[] = [
                'id' => (string) ($item['id'] ?? hash('sha256', json_encode($item))),
                'level' => (string) $item['level'],
                'title' => (string) ($item['title'] ?? ''),
                'message' => (string) ($item['message'] ?? ''),
                'action' => $this->normalizeAction($item['action'] ?? null),
            ];
        }

        return [
            'contract_version' => self::CONTRACT_VERSION,
            'synced_at' => (string) ($payload['synced_at'] ?? date(DATE_ATOM)),
            'latest' => [
                'frontend_version' => trim((string) ($latest['frontend_version'] ?? '')),
                'backend_version' => trim((string) ($latest['backend_version'] ?? '')),
            ],
            'addons' => $addons,
            'advice' => $advice,
        ];
    }

    /**
     * @param array<string, mixed> $decision
     * @return array<string, mixed>|null
     */
    private function applyLicenseDecision(array $decision, string $expectedCode): ?array
    {
        if ((string) ($decision['addon_code'] ?? '') !== $expectedCode) {
            throw new RuntimeException(sprintf('插件[%s]的平台运行授权决策编码不匹配。', $expectedCode));
        }
        if ($this->isLocallyManagedAddon($expectedCode)) {
            return null;
        }
        $serviceClass = 'PTAdmin\\Addon\\Service\\AddonLicenseService';
        if (!class_exists($serviceClass)) {
            throw new RuntimeException('当前插件体系不支持平台运行授权决策。');
        }

        return app($serviceClass)->applyRuntimeDecision($decision);
    }

    private function isLocallyManagedAddon(string $code): bool
    {
        $registryClass = AddonInstallationRegistry::class;
        if (!class_exists($registryClass)) {
            return false;
        }

        try {
            $registry = app($registryClass);

            return method_exists($registry, 'managementScope')
                && 'local' === (string) $registry->managementScope($code);
        } catch (Throwable $exception) {
            return false;
        }
    }

    /** @param mixed $action
     *  @return array<string, string>|null
     */
    private function normalizeAction($action): ?array
    {
        if (!is_array($action)) {
            return null;
        }
        $label = trim((string) ($action['label'] ?? ''));
        $url = trim((string) ($action['url'] ?? ''));
        if ('' === $label || !$this->isAllowedActionUrl($url)) {
            return null;
        }

        return [
            'label' => $label,
            'url' => $url,
            'target' => '_blank' === (string) ($action['target'] ?? '') ? '_blank' : '_self',
        ];
    }

    private function isAllowedActionUrl(string $url): bool
    {
        if ('' === $url) {
            return false;
        }
        if ('/' === $url[0] && 0 !== strpos($url, '//')) {
            return true;
        }

        return 'https' === strtolower((string) parse_url($url, PHP_URL_SCHEME));
    }

    private function frontendVersion(): string
    {
        return $this->adminFrontendBuildService->publishedVersion(base_path(), admin_web_prefix());
    }

    /** @return array<string, mixed> */
    private function emptyStatus(): array
    {
        return [
            'status' => 'never',
            'last_attempted_at' => '',
            'last_succeeded_at' => '',
            'next_attempt_at' => '',
            'failure_count' => 0,
            'last_error' => null,
            'response' => [],
        ];
    }

    /** @param array<string, mixed> $status */
    private function write(array $status): void
    {
        $path = $this->statusPath();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('无法创建应用状态缓存目录：%s', $directory));
        }

        $encoded = json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $encoded || false === file_put_contents($path, $encoded.PHP_EOL, LOCK_EX)) {
            throw new RuntimeException(sprintf('无法写入应用状态缓存：%s', $path));
        }
    }

    private function acquireLock(): bool
    {
        $path = $this->lockPath();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }
        if (is_file($path) && (int) filemtime($path) + self::LOCK_TIMEOUT > time()) {
            return false;
        }
        if (is_file($path)) {
            @unlink($path);
        }

        $handle = @fopen($path, 'x');
        if (false === $handle) {
            return false;
        }
        fwrite($handle, (string) time());
        fclose($handle);

        return true;
    }

    private function releaseLock(): void
    {
        if (is_file($this->lockPath())) {
            @unlink($this->lockPath());
        }
    }

    private function errorCode(Throwable $exception): string
    {
        if ($exception->getCode() >= 42200 && $exception->getCode() < 42300) {
            if (preg_match('/\[([A-Z_]+)\]/', $exception->getMessage(), $matches)) {
                return (string) $matches[1];
            }

            return 'INVALID_PAYLOAD';
        }
        return 'PLATFORM_UNAVAILABLE';
    }

    private function statusPath(): string
    {
        return (string) config('ptadmin.application_status_path', storage_path('app/ptadmin/application-status.json'));
    }

    private function lockPath(): string
    {
        return dirname($this->statusPath()).DIRECTORY_SEPARATOR.'application-status.lock';
    }

    private function ttl(): int
    {
        return max(3600, (int) config('ptadmin.application_sync_ttl', 21600));
    }

    /** @param array<string, mixed>|null $status */
    private function shouldAttempt(?array $status = null): bool
    {
        $status = is_array($status) ? $status : $this->read();
        $nextAttemptAt = strtotime((string) ($status['next_attempt_at'] ?? ''));
        if (false !== $nextAttemptAt && $nextAttemptAt > 0) {
            return $nextAttemptAt <= time();
        }

        $lastAttemptedAt = strtotime((string) ($status['last_attempted_at'] ?? ''));
        if ('failed' === (string) ($status['status'] ?? '') && false !== $lastAttemptedAt && $lastAttemptedAt > 0) {
            return $lastAttemptedAt + self::FAILURE_RETRY_SECONDS[0] <= time();
        }

        return $this->isStale($status);
    }

    protected function successJitterSeconds(): int
    {
        $maximum = max(0, (int) config('ptadmin.application_sync_jitter', 1800));

        return $maximum > 0 ? random_int(0, $maximum) : 0;
    }

    private function syncUrl(): string
    {
        return (string) config('ptadmin.application_sync_url', 'https://www.pangtou.com/api-addon/application-sync');
    }
}
