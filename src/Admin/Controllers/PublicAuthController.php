<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PTAdmin\Admin\Models\User;
use PTAdmin\Admin\Services\CaptchaChallengeService;
use PTAdmin\Admin\Services\PublicRegistrationService;
use PTAdmin\Foundation\Response\AdminResponse;

final class PublicAuthController
{
    public function __construct(
        private CaptchaChallengeService $captchaChallengeService,
        private PublicRegistrationService $registrationService
    ) {
    }

    public function captchaChallenge(Request $request): JsonResponse
    {
        return AdminResponse::success($this->captchaChallengeService->create(
            CaptchaChallengeService::SCENE_REGISTER,
            $this->context($request)
        ));
    }

    public function captchaRefresh(Request $request): JsonResponse
    {
        $request->validate(['challenge_id' => 'required|string']);

        return AdminResponse::success($this->captchaChallengeService->refresh(
            (string) $request->input('challenge_id'),
            CaptchaChallengeService::SCENE_REGISTER,
            $this->context($request)
        ));
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:20', Rule::unique(User::class, 'username')],
            'password' => ['required', 'string', 'min:6', 'max:64'],
            'nickname' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:50', Rule::unique(User::class, 'email')],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique(User::class, 'mobile')],
            'captcha' => ['sometimes', 'array'],
        ]);

        $user = $this->registrationService->register(
            array_intersect_key($validated, array_flip(['username', 'password', 'nickname', 'email', 'mobile'])),
            (array) ($validated['captcha'] ?? []),
            $this->context($request)
        );

        return AdminResponse::success([
            'id' => (int) $user->getKey(),
            'username' => (string) $user->username,
            'nickname' => (string) $user->nickname,
        ]);
    }

    /** @return array<string, string> */
    private function context(Request $request): array
    {
        return [
            'ip' => (string) $request->getClientIp(),
            'user_agent' => (string) $request->userAgent(),
        ];
    }
}
