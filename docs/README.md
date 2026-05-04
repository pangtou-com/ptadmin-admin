# API Docs

当前目录提供 `Apifox` 可直接导入的接口文档：

- `openapi.yaml`：`OpenAPI 3.0` 文档
- `settings-facade.md`：系统设置 / 插件设置 facade 协议与插件注册说明
- `system-config-governance.md`：系统配置治理规则与插件托管约束
- `plugin-settings-lifecycle.md`：插件配置安装、注入、升级、卸载生命周期规则
- `field-rule-matrix.md`：字段类型与 meta 规则白名单矩阵
- `seo-render-protocol.md`：宿主层前台 SEO 渲染协议
- `examples/plugin-settings.hosted.php`：插件托管设置注册模板
- `examples/plugin-settings.external-route.php`：插件外链设置页注册模板

导入方式：

1. 打开 `Apifox`
2. 选择“导入数据”
3. 选择 `OpenAPI / Swagger`
4. 导入 [openapi.yaml](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/docs/openapi.yaml)

说明：

- 文档默认接口前缀为 `/system`
- 如果宿主项目调整了 `PTADMIN_ROUTE_PREFIX` 或 `app.prefix`，请在 Apifox 中同步修改服务前缀
- 当前文档只收录 JSON 接口，不包含安装页和后台页面渲染路由
- 管理员初始化与授权初始化仅支持命令执行，不提供 HTTP 接口
- 上传接口需要先登录并携带后台登录令牌
