<?php

declare(strict_types=1);

return [
    'login' => [
        'fail' => '登录失败，账户密码错误',
        'limit' => '登录失败，账户被锁定',
        'attempt' => '尝试登录失败，请 :seconds 秒后再试',
    ],
    'no_login' => '未登录',
    '404' => '路由地址错误',
    '500' => '服务器错误',
    '403' => '无权访问',
    '401' => '未授权',
];
