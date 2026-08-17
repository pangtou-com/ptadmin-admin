<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\DB;
use PTAdmin\Addon\Contracts\Captcha\ChallengeStatus;
use PTAdmin\Admin\Models\User;
use PTAdmin\Foundation\Exceptions\BackgroundException;

final class PublicRegistrationService
{
    public function __construct(
        private UserService $userService,
        private CaptchaChallengeService $captchaChallengeService
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $captcha
     * @param array<string, mixed> $context
     */
    public function register(array $data, array $captcha = [], array $context = []): User
    {
        if (1 !== (int) system_config('security.is_register', 1)) {
            throw new BackgroundException('User registration is disabled.');
        }

        $this->verifyCaptcha($captcha, $context);

        return DB::transaction(function () use ($data): User {
            return $this->userService->register($data);
        });
    }

    /** @param array<string, mixed> $captcha @param array<string, mixed> $context */
    private function verifyCaptcha(array $captcha, array $context): void
    {
        if (!$this->captchaChallengeService->enabled(CaptchaChallengeService::SCENE_REGISTER)) {
            return;
        }

        $challengeId = trim((string) ($captcha['challenge_id'] ?? ''));
        $response = $captcha['response'] ?? [];
        if ('' === $challengeId || !is_array($response)) {
            throw new BackgroundException('Registration captcha payload is invalid.');
        }

        $result = $this->captchaChallengeService->verify($challengeId, CaptchaChallengeService::SCENE_REGISTER, $response, $context);
        if (ChallengeStatus::PASSED !== ($result['status'] ?? null)) {
            throw new BackgroundException('Registration captcha verification failed.');
        }
    }
}
