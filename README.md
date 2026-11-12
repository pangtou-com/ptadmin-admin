# PTAdmin Admin

`ptadmin/admin` 是 PTAdmin 的基础 Laravel 应用壳，用于通过 `composer create-project` 创建一个可直接进入安装向导的最小宿主。

仓库只负责 Laravel 应用入口、运行目录、宿主路由与扩展位置；后台认证、权限、Schema、插件生命周期、安装向导和已构建 Admin Shell 统一由 `ptadmin/core` 提供。这里不重复维护 Core 实现，也不再依赖历史 `ptadmin/admin`、`ptadmin/addon`、`ptadmin/schema` 或 Easy 包。

当前基础壳版本为 `v0.11.0`，依赖 `ptadmin/core:^0.11.0`，锁定 Core `v0.11.0`，配套官方 Shell `0.11.0`，支持版本检查、安全公告以及符合条件的 Core 和 Shell 在线更新。官方版本目录通过固定 HTTPS 接口读取，无需配置发行签名公钥。运行基线为 PHP `>=8.2 <9.0`、Laravel `>=12.68 <13.0`，不延续历史 Admin 内核的版本或旧运行环境支持。

0.11.0 提供通知批量发送、投递查询与认证回执接口。现有宿主升级时需备份并执行新增通知投递字段迁移，短信插件使用支持 Core 0.11.x 的版本。

## 创建应用

```bash
composer create-project ptadmin/admin your-project-name "0.11.0"
cd your-project-name
```

将 Web 根目录指向项目的 `public/`，并确保 PHP-FPM 用户可以写入以下位置：

- `.env`
- `addons/`
- `bootstrap/cache/`
- `public/`
- `storage/`

首次安装未指定后台入口时，生成首位为字母的 12 位随机小写字母和数字组合，不再添加固定的 `admin_` 前缀。安装失败重试和已有安装继续使用已保存的入口。

首次访问任意页面时，Core 会重定向到 `/__ptadmin__/install`。安装向导负责写入环境配置、生成 `APP_KEY`、创建应用身份和随机后台入口、执行迁移并发布 Admin Shell；不要在安装前手工执行 `key:generate` 或 `migrate`。

安装向导默认使用简体中文，语言区域为 `zh_CN`，与 Core 中文语言包目录一致。可通过 `APP_LOCALE` 显式选择语言，例如 `en`；`APP_FALLBACK_LOCALE` 默认是 `en`。已有应用若仍配置为 `zh-cn`，应改为 `zh_CN`，并在使用配置缓存时执行 `php artisan config:clear`；无需重新安装数据库。

## 应用边界

0.10.0 使用 Core 的 `PTAdmin\User\Models\User` 和 `UserIdentityInterface`；会员表、默认身份实现和失效事件由 Core 统一提供，基础壳不重复定义模型或建表迁移。宿主 `config/auth.php` 显式配置前台 `web` guard、`users` provider 及密码重置 broker，provider 使用 Core `User`。此配套调整随 0.10.0 交付。

宿主可在 `config/auth.php` 替换 `web` 对应 provider 的用户模型，或通过容器绑定替换 Core 会员接口，所有插件复用同一个实现。安装向导只创建后台创始人，不创建默认会员；本次不包含前台注册、登录页面。已有会员表与数据由迁移保留，使用自定义结构的宿主需提供适配器。

- `app/`：宿主业务代码与应用级扩展
- `addons/`：通过正常安装流程获得的插件实例
- `database/`：宿主自己的迁移和数据初始化
- `public/`：Web 根目录及安装后发布的后台、插件前端资源
- `routes/`：宿主公开页面和 API
- `templates/`：基础前台落地页，可由业务应用替换
- `tests/`：宿主启动、安装前隔离和基础路由契约

后台前端默认由 Core 随包提供并发布到 `public/{随机后台前缀}/`。业务应用前端应按应用模块协议发布到该目录的 `modules/app/`，插件前端发布到 `plugins/{code}/`；基础壳不内置 `app.ptadmin.com` 的前端源码工作区。

## 安装后的更新

创始人可在后台“系统信息 → 版本与更新”检查版本。有更高的兼容版本且环境允许时，点击“更新至 X.Y.Z”，确认后在任务中心查看进度。Core 更新前须完成数据库备份；更新期间应用会进入维护状态，成功后刷新后台。

Core 在线更新需要可执行的 PHP CLI、Composer 和相应目录写权限。可通过 `PTADMIN_UPDATE_PHP_BINARY`、`PTADMIN_COMPOSER_PATH` 指定服务器已有程序路径。Git/CI 交付、自定义 Shell 或受限环境继续使用项目部署流程，不通过调整权限或版本号绕过检查。

本版安装的是 Core 正式包携带的 Shell `0.11.0`，包含新版系统信息界面、官网维护和版本详情入口，并仅在存在安全公告时显示提醒。安装产物与官方 Shell 构建包使用同一文件摘要，避免宿主预览已更新而新安装仍使用旧界面。重新安装最新版后，没有更高版本时不显示更新按钮；验证升级流程须使用更高的正式目标版本。

## 开发与测试

```bash
composer install
composer test
```

安装完成前，Provider、路由和业务构造函数不得读取业务数据库。安装状态只由 `storage/app/ptadmin/install/` 中的文件状态管理；应用业务代码应继续遵守这一启动边界。
