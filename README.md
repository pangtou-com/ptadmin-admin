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

发布配置：

```bash
php artisan vendor:publish --provider="PTAdmin\\Admin\\Providers\\PTAdminServiceProvider" --tag=ptadmin-config
```

发布迁移：

```bash
php artisan vendor:publish --provider="PTAdmin\\Admin\\Providers\\PTAdminServiceProvider" --tag=ptadmin-migrations
```

发布后台前端静态资源：

```bash
php artisan vendor:publish --provider="PTAdmin\\Admin\\Providers\\PTAdminServiceProvider" --tag=ptadmin-assets
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

初始化授权角色与资源：

```bash
php artisan admin:auth-bootstrap
```

初始化创始人账户与默认授权：

```bash
php artisan admin:init
```

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
