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

更新包内置后台前端构建资源：

```bash
php artisan admin:fe:pull
```

更新包内置后台前端构建资源并发布到运行目录：

```bash
php artisan admin:fe:update
```

拉取项目二开前端模板源码：

```bash
php artisan admin:pf:pull
```

发布项目二开前端构建产物：

```bash
php artisan admin:pf:publish
```

## PTAdmin 应用身份证

PTAdmin 首次安装完成时会生成一份应用身份证，其中包含稳定的应用实例 ID 和 RSA 密钥对，供插件市场识别当前应用、绑定已购买插件授权并完成周期签名验证。默认保存在：

```text
storage/app/ptadmin/ptadmin-application-identity.json
```

可通过 `PTADMIN_APPLICATION_INSTANCE_PATH` 调整路径。应用身份证属于宿主私有运行数据，不应放入公开目录、插件包或代码仓库，必须纳入加密备份，并在重新部署、服务器迁移和灾难恢复时随应用一起恢复。保留该文件时，域名、服务器或代码目录变化后仍会被平台识别为同一个应用；文件丢失后生成的新身份证会被识别为新应用，原插件授权需要迁移或重新激活。

安装完成后的运行流程只读取现有身份证；如果文件缺失或损坏，系统会明确报错，不会静默生成新身份并造成已有插件授权漂移。

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
