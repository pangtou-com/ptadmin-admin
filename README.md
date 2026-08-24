# PTAdmin Admin

`ptadmin/admin` 是 PTAdmin 后台管理内核包。

它负责提供后台认证、授权资源、角色授权、组织扩展能力、后台接口路由、中间件、初始化命令，以及对应的配置、迁移和语言包。`ptadmin/addon` 与 `ptadmin/easy` 已作为强依赖内置在包依赖中，安装 `ptadmin/admin` 后不需要再单独安装这两个包。

## 功能范围

- 后台登录与认证守卫
- 资源授权、角色授权、直接授权
- 租户、组织、部门扩展表结构
- 后台 API 路由与中间件注册
- 后台前端入口路由与运行时配置脚本
- 默认后台资源初始化
- 配置、迁移、语言包、前端静态资源发布

## 安装

```bash
composer require ptadmin/admin
```

## 发布与迁移

发布全部 PTAdmin 资源：

```bash
php artisan vendor:publish --tag=ptadmin --force
```

发布配置：

```bash
php artisan vendor:publish --tag=ptadmin-config --force
```

发布迁移：

```bash
php artisan vendor:publish --tag=ptadmin-migrations --force
```

发布后台前端静态资源：

```bash
php artisan vendor:publish --tag=ptadmin-assets --force
```

执行迁移：

```bash
php artisan migrate
```

## 前端入口

后台前端页面入口与接口入口已拆分：

- `PTADMIN_WEB_PREFIX`：后台页面入口，默认 `admin`
- `PTADMIN_API_PREFIX`：后台接口入口，默认 `system`

运行时配置脚本固定为：

```text
/{PTADMIN_WEB_PREFIX}/ptconfig.js
```

## 初始化命令

初始化创始人账户与默认授权：

```bash
php artisan admin:auth
```

包维护或发布前更新内置后台前端构建资源：

```bash
php artisan admin:fe:pull
```

该命令会写入 `ptadmin/admin` 包内的 `resources/admin-frontend`，不用于 Composer `vendor` 只读的线上宿主。

线上宿主更新后台前端：

```bash
php artisan admin:fe:update
```

该命令把构建包暂存到宿主 `storage/app/ptadmin/frontend/admin-build`，再发布到 `public/{PTADMIN_WEB_PREFIX}`，不会修改 `vendor`。宿主只需保证 `storage` 和后台前端发布目录可写。

拉取项目二开前端模板源码：

```bash
php artisan admin:pf:pull
```

发布项目二开前端构建产物：

```bash
php artisan admin:pf:publish
```

## PTAdmin 应用身份证

PTAdmin 在首次需要采集应用状态时自动生成一份应用身份证，其中包含随机应用实例 ID 和 RSA 密钥对，供平台区分应用并验证状态同步请求。安装完成流程也会提前初始化该文件。默认保存在：

```text
storage/app/ptadmin/ptadmin-application-identity.json
```

可通过 `PTADMIN_APPLICATION_INSTANCE_PATH` 调整路径。应用身份证属于宿主私有运行数据，不应放入公开目录、插件包或代码仓库。保留该文件时，域名、服务器或代码目录变化后仍会被平台识别为同一个应用。

文件缺失或内容、密钥损坏时，PTAdmin 会自动生成新的应用身份证；新 ID 只代表新的采集实例，不影响宿主业务。若目录不可写或运行环境无法生成密钥，本次状态采集会直接跳过，不向后台页面返回身份证错误。

## 场景通知

插件在 `Config/notify.php` 中声明场景、变量、默认渠道和渠道模板，并在安装、升级和卸载生命周期中同步状态：

```php
use PTAdmin\Admin\Notifications\NotificationSceneRegistry;

app(NotificationSceneRegistry::class)->syncAddon(
    'order',
    require __DIR__.'/Config/notify.php'
);

app(NotificationSceneRegistry::class)->disableAddon('order');
```

业务发送时只传场景编码和场景变量。未指定渠道时使用场景的默认渠道，并自动选择当前已安装插件提供的可用实例：

```php
$result = notify()
    ->toUserId($userId)
    ->send('order.shipped', [
        'order_no' => 'A1001',
        'tracking_no' => 'SF123456',
    ], [
        'biz_id' => 'A1001',
        'biz_key' => 'order.shipped.A1001',
        'action_type' => 'route',
        'action_url' => '/orders/A1001',
    ]);

$notificationId = $result->notificationId();
$deliveryCount = $result->deliveryCount();
```

接收人支持模型、ID 和批量 ID。需要覆盖场景默认渠道时使用 `NotificationChannel` 常量：

```php
use PTAdmin\Admin\Notifications\NotificationChannel;

notify()
    ->toAdminIds($adminIds)
    ->channels([NotificationChannel::SITE, NotificationChannel::MAIL])
    ->send('order.shipped', $variables);
```

`admin_notify()` 和 `user_notify()` 保留用于兼容直接传标题、正文的旧调用；新增业务应优先注册场景并使用 `notify()->send()`，让模板、渠道路由和投递日志进入统一管理。

## 测试

包内测试基于 `orchestra/testbench`，独立仓库中可直接执行：

```bash
composer install
composer test
```

## 目录结构

```text
ptadmin/
├── composer.json
├── README.md
├── phpunit.xml.dist
├── config/
├── database/
├── lang/
├── routes/
├── src/
└── tests/
```
