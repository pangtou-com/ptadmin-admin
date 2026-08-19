@once('ptadmin-captcha-browser-runtime')
    <script src="{{ asset('vendor/ptadmin/captcha/captcha.browser.js') }}"></script>
@endonce

<pt-captcha {{ $attributes->merge([
    'id' => $id,
    'scene' => $scene,
    'challenge-url' => $challengeUrl,
    'refresh-url' => $refreshUrl,
]) }}></pt-captcha>
