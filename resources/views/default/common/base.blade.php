<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title')</title>
    <link rel="icon" href="{{_asset("ptadmin/images/logo.png")}}">
    <meta name="keywords" content="@yield('keywords')" />
    <meta name="description" content="@yield('description')" />
    <link rel="stylesheet" href="{{_asset('static/font/iconfont.css')}}" />
    <link rel="stylesheet" href="{{_asset('ptadmin/bin/css/layui.css')}}" />
    <link rel="stylesheet" href="{{_asset('static/index.css')}}" />
</head>
<body>
<div class="header-box">
    <div class="container-xxl">
        <div class="logo">
            <img src="{{_asset("ptadmin/images/logo_white.png")}}" alt="" />
        </div>
        <div class="navigation-box">
            <ul class="content">
                <li class="item"><a href="/member/center">个人中心</a></li>
                <li class="item">
                    <a href="/" class="avatar-box">
                        <div class="avatar">
                            <img src="{{_asset('ptadmin/images/avatar.png')}}" alt="PTAdmin">
                        </div>
                    </a>
                    <ul class="sub-navs">
                        <li class="sub-item">
                            <a href="/member/login"><i class="iconfont icon-response-fill"></i>登录</a>
                        </li>
                        <li class="sub-item">
                            <a href="/member/register"><i class="iconfont icon-customer-fill"></i>注册</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
@yield('content')

<div class="footer-box">All Rights Reserved. 专业的后台管理系统.  渝ICP备19003576号-2</div>
</body>
</html>
@yield('script')
