<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PTAdmin') }}</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #172033; background: #f4f7fb; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 32px 20px; }
        main { width: min(760px, 100%); padding: 48px; border: 1px solid #dfe7f2; border-radius: 8px; background: #fff; box-shadow: 0 24px 70px rgba(24, 55, 96, .12); }
        .brand { display: flex; align-items: center; gap: 12px; color: #1857c9; font-weight: 800; letter-spacing: .02em; }
        .brand img { width: 40px; height: 40px; }
        .badge { display: inline-flex; margin-top: 32px; padding: 7px 11px; border-radius: 999px; color: #17643a; background: #e9f8ef; font-size: 13px; font-weight: 700; }
        h1 { margin: 18px 0 14px; font-size: 48px; line-height: 1.08; letter-spacing: 0; }
        p { margin: 0; color: #5a667a; font-size: 17px; line-height: 1.8; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 18px; border-radius: 8px; color: #fff; background: #1857c9; font-weight: 700; text-decoration: none; }
        .button.secondary { color: #29405f; background: #eef3f9; }
        .button:focus-visible { outline: 3px solid #f0b429; outline-offset: 3px; }
        dl { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin: 34px 0 0; }
        dl div { padding: 16px; border-radius: 8px; background: #f7f9fc; }
        dt { color: #7a879a; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
        dd { margin: 6px 0 0; font-weight: 700; overflow-wrap: anywhere; }
        @media (max-width: 600px) { main { padding: 32px 24px; } h1 { font-size: 36px; } dl { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <div class="brand">
        <img src="{{ asset('logo.svg') }}" alt="PTAdmin">
        <span>PTAdmin Base Application</span>
    </div>
    <div class="badge">安装已完成</div>
    <h1>基础应用已就绪</h1>
    <p>当前宿主已接入 PTAdmin Core。你可以进入随机后台入口开始配置资源、安装插件，或在这个 Laravel 应用中继续开发自己的页面、接口和应用模块。</p>
    <div class="actions">
        <a class="button" href="{{ $adminUrl }}">进入管理后台</a>
        <a class="button secondary" href="https://docs.pangtou.com/" target="_blank" rel="noreferrer">查看文档</a>
    </div>
    <dl>
        <div><dt>Core</dt><dd>{{ $coreVersion }}</dd></div>
        <div><dt>Application</dt><dd>{{ config('app.name', 'PTAdmin') }}</dd></div>
    </dl>
</main>
</body>
</html>
