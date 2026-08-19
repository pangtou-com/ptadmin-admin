<?php

declare(strict_types=1);

namespace PTAdmin\Admin\View\Components;

use Illuminate\View\Component;

class Captcha extends Component
{
    public string $id;

    public string $scene;

    public string $challengeUrl;

    public string $refreshUrl;

    public function __construct(
        string $id = 'pt-captcha',
        string $scene = 'frontend.register',
        string $challengeUrl = '/api/captcha/challenge',
        string $refreshUrl = '/api/captcha/challenge/refresh'
    ) {
        $this->id = $id;
        $this->scene = $scene;
        $this->challengeUrl = $challengeUrl;
        $this->refreshUrl = $refreshUrl;
    }

    public function render()
    {
        return view('ptadmin::components.captcha');
    }
}
