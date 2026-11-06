# PTAdmin Admin

`ptadmin/admin` 是 PTAdmin 的基础 Laravel 应用壳，用于通过 `composer create-project` 创建一个可直接进入安装向导的最小宿主。

仓库只负责 Laravel 应用入口、运行目录、宿主路由与扩展位置；后台认证、权限、Schema、插件生命周期、安装向导和已构建 Admin Shell 统一由 `ptadmin/core` 提供。这里不重复维护 Core 实现，也不再依赖历史 `ptadmin/admin`、`ptadmin/addon`、`ptadmin/schema` 或 Easy 包。

当前基础壳版本为 `v0.2.0`，依赖 `ptadmin/core:^0.2.0`，支持菜单 `children` 层级声明。运行基线为 PHP `>=8.2 <9.0`、Laravel `>=12.68 <13.0`，不延续历史 Admin 内核的版本或旧运行环境支持。

## 创建应用

```bash
composer create-project ptadmin/admin your-project-name "^0.2.0"
cd your-project-name
```

将 Web 根目录指向项目的 `public/`，并确保 PHP-FPM 用户可以写入以下位置：

- `.env`
- `addons/`
- `bootstrap/cache/`
- `public/`
- `storage/`

首次访问任意页面时，Core 会重定向到 `/__ptadmin__/install`。安装向导负责写入环境配置、生成 `APP_KEY`、创建应用身份和随机后台入口、执行迁移并发布 Admin Shell；不要在安装前手工执行 `key:generate` 或 `migrate`。

## 应用边界

- `app/`：宿主业务代码与应用级扩展
- `addons/`：通过正常安装流程获得的插件实例
- `database/`：宿主自己的迁移和数据初始化
- `public/`：Web 根目录及安装后发布的后台、插件前端资源
- `routes/`：宿主公开页面和 API
- `templates/`：基础前台落地页，可由业务应用替换
- `tests/`：宿主启动、安装前隔离和基础路由契约

后台前端默认由 Core 随包提供并发布到 `public/{随机后台前缀}/`。业务应用前端应按应用模块协议发布到该目录的 `modules/app/`，插件前端发布到 `plugins/{code}/`；基础壳不内置 `app.ptadmin.com` 的前端源码工作区。

## 开发与测试

```bash
composer install
composer test
```

安装完成前，Provider、路由和业务构造函数不得读取业务数据库。安装状态只由 `storage/app/ptadmin/install/` 中的文件状态管理；应用业务代码应继续遵守这一启动边界。
