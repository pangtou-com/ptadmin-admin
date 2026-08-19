<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PTAdmin\Admin\Models\User;
use PTAdmin\Admin\Services\CaptchaChallengeService;
use PTAdmin\Admin\Services\PublicRegistrationService;
use PTAdmin\Admin\Services\UserService;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Foundation\Response\AdminResponse;

final class PublicAuthController
{
    public function __construct(
        private CaptchaChallengeService $captchaChallengeService,
        private PublicRegistrationService $registrationService,
        private UserService $userService
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

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        try {
            $login = $this->userService->login($validated, $this->guard());
        } catch (BackgroundException $exception) {
            return AdminResponse::fail($exception->getMessage());
        }
        $user = User::query()->where('username', (string) $validated['username'])->firstOrFail();

        return AdminResponse::success([
            'token' => (string) $login['token'],
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard($this->guard())->user();

        return AdminResponse::success($this->userPayload($user));
    }

    public function logout(): JsonResponse
    {
        Auth::guard($this->guard())->logout();

        return AdminResponse::success(null, '退出成功');
    }

    /** @return array<string, mixed> */
    private function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->getKey(),
            'username' => (string) $user->username,
            'nickname' => (string) $user->nickname,
            'email' => (string) ($user->email ?? ''),
            'mobile' => (string) ($user->mobile ?? ''),
            'avatar' => (string) $user->avatar,
        ];
    }

    private function guard(): string
    {
        return (string) config('ptadmin.public_auth.guard', 'frontend');
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
