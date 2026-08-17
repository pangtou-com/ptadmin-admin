<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Tests\TestCase;

final class PTAdminCaptchaChallengeApiTest extends TestCase
{
    public function test_challenge_endpoint_keeps_legacy_login_available_without_a_configured_provider(): void
    {
        $response = $this->getJson('/ptadmin/captcha/challenge');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.scene', 'admin.login');
    }

    public function test_public_registration_challenge_is_disabled_without_registration_configuration(): void
    {
        $response = $this->getJson('/api/captcha/challenge');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.scene', 'frontend.register');
    }
}
