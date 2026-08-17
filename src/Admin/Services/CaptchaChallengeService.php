<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Contracts\Captcha\ChallengeStatus;
use PTAdmin\Addon\Contracts\Captcha\Data\ChallengeCreateRequest;
use PTAdmin\Addon\Contracts\Captcha\Data\ChallengeCreateResult;
use PTAdmin\Addon\Contracts\Captcha\Data\ChallengeRefreshRequest;
use PTAdmin\Addon\Contracts\Captcha\Data\ChallengeVerifyRequest;
use PTAdmin\Addon\Contracts\Captcha\Data\ChallengeVerifyResult;
use PTAdmin\Addon\Service\AddonInjectsManage;
use PTAdmin\Foundation\Exceptions\BackgroundException;

/**
 * 管理后台反自动化挑战编排器。
 *
 * 挑战创建后只允许使用缓存中锁定的插件能力和渲染器，避免验证阶段
 * 因配置切换或同编码插件而改变验证目标。
 */
class CaptchaChallengeService
{
    private const SCENE_LOGIN = 'admin.login';
    private const CACHE_PREFIX = 'ptadmin:captcha:challenge:';
    private const RATE_PREFIX = 'ptadmin:captcha:rate:';
    private const VERIFY_LOCK_PREFIX = 'ptadmin:captcha:verify-lock:';
    private const TTL_SECONDS = 180;
    private const MAX_ATTEMPTS = 5;
    private const RATE_WINDOW_SECONDS = 600;
    private const CREATE_RATE_LIMIT = 20;
    private const VERIFY_RATE_LIMIT = 60;

    /** @return array<string, mixed> */
    public function create(string $scene = self::SCENE_LOGIN, array $context = []): array
    {
        if (!$this->enabled($scene)) {
            return ['enabled' => false, 'scene' => $scene];
        }

        $this->assertRateLimit('create', $scene, $context, $this->rateLimit('create'));

        $candidates = $this->candidates();
        $lastError = null;
        foreach ($candidates as $definition) {
            try {
                $result = $this->createWith($definition, $scene, $context);
                $state = $this->store($definition, $scene, $result);

                return ['enabled' => true] + $this->publicResult($state['result']);
            } catch (\Throwable $exception) {
                $lastError = $exception;
            }
        }

        throw new BackgroundException('Captcha provider is unavailable.'.($lastError ? ' '.$lastError->getMessage() : ''));
    }

    /** @return array<string, mixed> */
    public function refresh(string $challengeId, string $scene = self::SCENE_LOGIN, array $context = []): array
    {
        $state = $this->load($challengeId);
        $this->assertState($state, $scene);
        if (($state['status'] ?? 'active') !== 'active' || ($state['expires_at'] ?? 0) <= time()) {
            throw new BackgroundException('Captcha challenge is invalid or expired.');
        }

        try {
            $operations = (array) ($state['definition']['captcha_definition']['operations'] ?? []);
            $result = in_array('refresh', $operations, true)
                ? $this->refreshWith($state, $scene, $context)
                : $this->createWith($state['definition'], $scene, $context);
            $newState = $this->store($state['definition'], $scene, $result);
            Cache::forget($this->cacheKey($challengeId));

            return ['enabled' => true] + $this->publicResult($newState['result']);
        } catch (\Throwable $exception) {
            throw new BackgroundException('Captcha provider refresh failed: '.$exception->getMessage(), 0, $exception);
        }
    }

    /** @return array<string, mixed> */
    public function verify(string $challengeId, string $scene, array $response, array $context = []): array
    {
        if (!$this->enabled($scene)) {
            return ['status' => ChallengeStatus::PASSED, 'reason_code' => 'disabled'];
        }

        try {
            $lock = Cache::lock($this->verifyLockKey($challengeId), 5);
            if (!$lock->get()) {
                return [
                    'status' => ChallengeStatus::LOCKED,
                    'reason_code' => 'verification_in_progress',
                    'retry_after' => 1,
                ];
            }
        } catch (\Throwable $exception) {
            $lock = null;
        }

        try {
            $this->assertRateLimit('verify', $scene, $context, $this->rateLimit('verify'));

            return $this->verifyUnlocked($challengeId, $scene, $response, $context);
        } finally {
            if (null !== $lock) {
                $lock->release();
            }
        }
    }

    /** @return array<string, mixed> */
    private function verifyUnlocked(string $challengeId, string $scene, array $response, array $context = []): array
    {
        $state = $this->load($challengeId);
        if (null === $state) {
            return ['status' => ChallengeStatus::EXPIRED, 'reason_code' => 'challenge_not_found'];
        }
        if ($scene !== ($state['scene'] ?? '')) {
            return ['status' => ChallengeStatus::PAYLOAD_INVALID, 'reason_code' => 'scene_mismatch'];
        }
        if (($state['expires_at'] ?? 0) <= time()) {
            $this->markTerminal($challengeId, $state, ChallengeStatus::EXPIRED);

            return ['status' => ChallengeStatus::EXPIRED, 'reason_code' => 'challenge_expired'];
        }
        if (($state['status'] ?? 'active') !== 'active') {
            return ['status' => ChallengeStatus::LOCKED, 'reason_code' => 'challenge_already_used'];
        }
        if (($state['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            $this->markTerminal($challengeId, $state, ChallengeStatus::LOCKED);

            return ['status' => ChallengeStatus::LOCKED, 'reason_code' => 'too_many_attempts'];
        }

        $result = $this->stateResult($state);
        $schema = (array) ($result->get('response_schema', []) ?? []);
        $payloadError = $this->validateResponse($response, $schema);
        if (null !== $payloadError) {
            ++$state['attempts'];
            if ($state['attempts'] >= self::MAX_ATTEMPTS) {
                $state['status'] = ChallengeStatus::LOCKED;
            }
            Cache::put($this->cacheKey($challengeId), $state, max(1, (int) $state['expires_at'] - time()));

            if (($state['status'] ?? 'active') === ChallengeStatus::LOCKED) {
                return ['status' => ChallengeStatus::LOCKED, 'reason_code' => 'too_many_attempts'];
            }

            return [
                'status' => ChallengeStatus::PAYLOAD_INVALID,
                'reason_code' => $payloadError,
            ];
        }

        try {
            $result = Addon::executeInjectForAddon(
                (string) $state['addon_code'],
                'captcha',
                (string) $state['code'],
                (new ChallengeVerifyRequest([
                    'challenge_id' => $challengeId,
                    'scene' => $scene,
                    'response' => $response,
                    'private_state' => (array) ($state['private_state'] ?? []),
                    'client_context' => $context,
                ]))->toArray(),
                'verify'
            );
            $result = $this->normalizeVerifyResult($result);
        } catch (\Throwable $exception) {
            return ['status' => ChallengeStatus::PROVIDER_UNAVAILABLE, 'reason_code' => 'provider_error'];
        }

        if ($result->passed()) {
            $this->markTerminal($challengeId, $state, ChallengeStatus::PASSED);
        } elseif (in_array($result->status(), [ChallengeStatus::REJECTED, ChallengeStatus::PAYLOAD_INVALID], true)) {
            ++$state['attempts'];
            if ($state['attempts'] >= self::MAX_ATTEMPTS) {
                $state['status'] = ChallengeStatus::LOCKED;
            }
            Cache::put($this->cacheKey($challengeId), $state, max(1, (int) $state['expires_at'] - time()));

            if (($state['status'] ?? 'active') === ChallengeStatus::LOCKED) {
                return ['status' => ChallengeStatus::LOCKED, 'reason_code' => 'too_many_attempts'];
            }
        }

        return $result->toArray();
    }

    public function enabled(string $scene = self::SCENE_LOGIN): bool
    {
        if (self::SCENE_LOGIN !== $scene) {
            return false;
        }

        try {
            $switch = config('ptadmin.captcha.login_enabled');
            if (null === $switch) {
                $switch = system_config('security.login_captcha', null);
                if (null === $switch) {
                    $switch = system_config('basic.login_captcha', 1);
                }
            }
        } catch (\Throwable $exception) {
            $switch = 1;
        }

        return (bool) $switch && '' !== $this->configuredProvider();
    }

    /** @return array<int, array<string, mixed>> */
    private function candidates(): array
    {
        $definitions = array_values(array_filter(
            AddonInjectsManage::getInstance()->getDefinitionsByGroup('captcha'),
            static function (array $definition): bool {
                $protocol = $definition['captcha_definition'] ?? [];
                return is_array($protocol)
                    && in_array('create', (array) ($protocol['operations'] ?? []), true)
                    && in_array('verify', (array) ($protocol['operations'] ?? []), true);
            }
        ));
        $configured = $this->configuredProvider();
        if ('' !== $configured) {
            $configuredDefinitions = array_values(array_filter($definitions, static function (array $definition) use ($configured): bool {
                return self::providerKey($definition) === $configured;
            }));
            if (0 === count($configuredDefinitions)) {
                return [];
            }
        }
        usort($definitions, static function (array $left, array $right) use ($configured): int {
            return (self::providerKey($left) === $configured ? 0 : 1) <=> (self::providerKey($right) === $configured ? 0 : 1);
        });

        return $definitions;
    }

    /** @param array<string, mixed> $definition */
    private function createWith(array $definition, string $scene, array $context): ChallengeCreateResult
    {
        $result = Addon::executeInjectForAddon(
            (string) $definition['addon_code'],
            'captcha',
            (string) $definition['code'],
            (new ChallengeCreateRequest([
                'scene' => $scene,
                'locale' => app()->getLocale(),
                'client_context' => $context,
            ]))->toArray(),
            'create'
        );
        $result = $this->normalizeCreateResult($result);
        if ('' === $result->challengeId()) {
            throw new BackgroundException('Captcha provider returned an empty challenge id.');
        }

        $protocol = (array) $definition['captcha_definition'];
        $data = $result->toArray();
        if ('' === $result->type()) {
            $data['type'] = (string) ($protocol['type'] ?? '');
        }
        if ([] === (array) $data['renderer']) {
            $data['renderer'] = (array) ($protocol['renderer'] ?? []);
        }
        if ([] === (array) $data['response_schema']) {
            $data['response_schema'] = (array) ($protocol['response_schema'] ?? []);
        }

        return new ChallengeCreateResult($data);
    }

    /** @param array<string, mixed> $state */
    private function refreshWith(array $state, string $scene, array $context): ChallengeCreateResult
    {
        $result = Addon::executeInjectForAddon(
            (string) $state['addon_code'],
            'captcha',
            (string) $state['code'],
            (new ChallengeRefreshRequest([
                'challenge_id' => $state['challenge_id'],
                'scene' => $scene,
                'private_state' => (array) ($state['private_state'] ?? []),
                'client_context' => $context,
            ]))->toArray(),
            'refresh'
        );

        $result = $this->normalizeCreateResult($result);
        $protocol = (array) ($state['definition']['captcha_definition'] ?? []);
        $data = $result->toArray();
        if ('' === $result->type()) {
            $data['type'] = (string) ($protocol['type'] ?? '');
        }
        if ([] === (array) $data['renderer']) {
            $data['renderer'] = (array) ($protocol['renderer'] ?? []);
        }
        if ([] === (array) $data['response_schema']) {
            $data['response_schema'] = (array) ($protocol['response_schema'] ?? []);
        }

        return new ChallengeCreateResult($data);
    }

    /** @return array<string, mixed> */
    private function store(array $definition, string $scene, ChallengeCreateResult $result): array
    {
        $expiresAt = strtotime((string) $result->get('expires_at', '')) ?: time() + self::TTL_SECONDS;
        $expiresAt = min($expiresAt, time() + self::TTL_SECONDS);
        $data = $result->toArray();
        $data['expires_at'] = date(DATE_ATOM, $expiresAt);
        $result = new ChallengeCreateResult($data);
        $state = [
            'challenge_id' => $result->challengeId(),
            'scene' => $scene,
            'addon_code' => (string) $definition['addon_code'],
            'code' => (string) $definition['code'],
            'definition' => $definition,
            'private_state' => $result->privateState(),
            'result' => $result,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'status' => 'active',
        ];
        Cache::put($this->cacheKey($result->challengeId()), $state, max(1, $expiresAt - time()));

        return $state;
    }

    /** @return array<string, mixed>|null */
    private function load(string $challengeId): ?array
    {
        $state = Cache::get($this->cacheKey($challengeId));
        return is_array($state) ? $state : null;
    }

    /** @param array<string, mixed>|null $state */
    private function assertState(?array $state, string $scene): void
    {
        if (null === $state || $scene !== ($state['scene'] ?? '')) {
            throw new BackgroundException('Captcha challenge is invalid or expired.');
        }
    }

    /** @param array<string, mixed> $state */
    private function markTerminal(string $challengeId, array $state, string $status): void
    {
        $state['status'] = $status;
        Cache::put($this->cacheKey($challengeId), $state, 60);
    }

    private function configuredProvider(): string
    {
        try {
            $provider = trim((string) config('ptadmin.captcha.provider', ''));
            if ('' === $provider) {
                $provider = trim((string) system_config('security.login_captcha_provider', ''));
            }
        } catch (\Throwable $exception) {
            return '';
        }
        if ('' === $provider) {
            return '';
        }
        if (false !== strpos($provider, ':')) {
            return $provider;
        }

        $matches = array_values(array_filter(
            AddonInjectsManage::getInstance()->getDefinitionsByGroup('captcha'),
            static function (array $definition) use ($provider): bool {
                return (string) ($definition['code'] ?? '') === $provider;
            }
        ));

        return 1 === count($matches) ? self::providerKey($matches[0]) : $provider;
    }

    /** @param array<string, mixed> $definition */
    private static function providerKey(array $definition): string
    {
        return (string) ($definition['addon_code'] ?? '').':'.(string) ($definition['code'] ?? '');
    }

    private function cacheKey(string $challengeId): string
    {
        return self::CACHE_PREFIX.hash('sha256', $challengeId);
    }

    private function normalizeCreateResult($result): ChallengeCreateResult
    {
        return $result instanceof ChallengeCreateResult ? $result : new ChallengeCreateResult((array) $result);
    }

    private function normalizeVerifyResult($result): ChallengeVerifyResult
    {
        return $result instanceof ChallengeVerifyResult ? $result : new ChallengeVerifyResult((array) $result);
    }

    /** @param array<string, mixed> $state */
    private function stateResult(array $state): ChallengeCreateResult
    {
        $result = $state['result'] ?? [];

        return $result instanceof ChallengeCreateResult
            ? $result
            : new ChallengeCreateResult(is_array($result) ? $result : []);
    }

    private function verifyLockKey(string $challengeId): string
    {
        return self::VERIFY_LOCK_PREFIX.hash('sha256', $challengeId);
    }

    private function rateLimit(string $operation): int
    {
        $default = 'create' === $operation ? self::CREATE_RATE_LIMIT : self::VERIFY_RATE_LIMIT;
        $configured = config('ptadmin.captcha.'.($operation.'_rate_limit'));

        return is_numeric($configured) && (int) $configured > 0 ? (int) $configured : $default;
    }

    /** @param array<string, mixed> $context */
    private function assertRateLimit(string $operation, string $scene, array $context, int $limit): void
    {
        // HTTP 请求地址是可信来源；上下文中的 ip 只作为无请求环境的回退值。
        $identity = '';
        if (app()->bound('request')) {
            $identity = trim((string) request()->getClientIp());
        }
        if ('' === $identity) {
            $identity = trim((string) ($context['ip'] ?? ''));
        }
        if ('' === $identity) {
            $identity = 'anonymous';
        }

        $key = self::RATE_PREFIX.hash('sha256', $operation.'|'.$scene.'|'.$identity);
        try {
            if (Cache::add($key, 1, self::RATE_WINDOW_SECONDS)) {
                return;
            }
            $count = Cache::increment($key);
            if (false !== $count && $count > $limit) {
                throw new BackgroundException('Captcha rate limit exceeded.');
            }
        } catch (BackgroundException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            // A cache driver without atomic counters must not make login unavailable.
        }
    }

    /** @param array<string, mixed> $response @param array<string, mixed> $schema */
    private function validateResponse(array $response, array $schema): ?string
    {
        if ([] === $schema) {
            return null;
        }
        if ('object' === ($schema['type'] ?? null) && [] === $response) {
            return 'response_required';
        }

        foreach ((array) ($schema['required'] ?? []) as $field) {
            if (!array_key_exists((string) $field, $response)) {
                return 'response_'.(string) $field.'_required';
            }
        }
        foreach ((array) ($schema['properties'] ?? []) as $field => $definition) {
            if (!array_key_exists((string) $field, $response) || !is_array($definition)) {
                continue;
            }
            $value = $response[$field];
            $type = (string) ($definition['type'] ?? '');
            $valid = 'string' === $type ? is_string($value)
                : ('number' === $type || 'integer' === $type ? is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))
                : ('array' === $type ? is_array($value) : ('object' === $type ? is_array($value) : true)));
            if (!$valid) {
                return 'response_'.(string) $field.'_invalid';
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function publicResult(ChallengeCreateResult $result): array
    {
        return $result->publicData();
    }
}
