# API Docs

当前目录提供 `Apifox` 可直接导入的接口文档：

- `openapi.yaml`：`OpenAPI 3.0` 文档

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
