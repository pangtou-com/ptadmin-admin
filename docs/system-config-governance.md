# System Config Governance

本文档用于沉淀 PTAdmin 当前“系统配置管理 / 插件配置托管”的后端规则。

目标：

- 统一系统配置与插件配置的存储模型
- 统一前端表单渲染协议
- 明确哪些配置允许输出到界面，哪些只能宿主内部使用
- 为插件提供集中托管、插件自管、卸载清理的统一约束

## 1. 总体模型

当前配置体系统一使用两张表：

- `system_config_groups`
- `system_configs`

分层约定：

1. 一级分组
   用于业务归类，例如 `system`、`oauth`、`payment`
2. 二级分组
   用于配置页签或配置区块，例如 `basic`、`upload`、`wechat`
3. 配置项
   用于定义具体字段、默认值、当前值、扩展元信息

限制：

- 配置分组最多只允许两级
- `system_configs` 只能挂在二级分组下

## 2. 分组规则

分组模型为 `SystemConfigGroup`。

关键字段：

- `name`
  分组编码
- `title`
  分组标题
- `parent_id`
  `0` 表示一级分组，非 `0` 表示二级分组
- `addon_code`
  空表示系统配置，非空表示插件托管配置
- `intro`
  分组说明
- `extra`
  分组辅助信息

### 2.1 分组 extra 规则

当前建议分组 `extra` 至少支持：

```json
{
  "icon": "Setting",
  "layout": {
    "mode": "block",
    "labelWidth": 140
  }
}
```

当前后端已约束：

- `extra.layout.mode`
  仅允许 `tab` 或 `block`

语义：

- `tab`
  同层只展示当前激活分组，适合配置项较多的场景
- `block`
  默认展开分组内容，适合配置项较少或需要整体浏览的场景

## 3. 字段规则

字段模型为 `SystemConfig`。

关键字段：

- `name`
  字段编码
- `title`
  字段标题
- `type`
  字段类型
- `value`
  当前值
- `default_val`
  默认值
- `intro`
  字段说明
- `extra`
  字段扩展信息

### 3.1 字段 type 规则

字段协议统一使用 `type`，不再以 `component` 作为主协议字段。

当前后端已识别：

- `text`
- `textarea`
- `switch`
- `radio`
- `checkbox`
- `select`
- `json`

存储约定：

- `switch`
  按 `0/1` 保存
- `checkbox`
  按 JSON 保存
- `json`
  按 JSON 保存
- 其他类型
  按字符串保存

兼容说明：

- 对旧插件注册协议，后端仍兼容读取 `component`
- 输出给前端时统一归一化为 `type`

### 3.2 字段 extra 规则

字段 `extra` 当前统一为：

```json
{
  "options": {
    "local": "本地存储",
    "oss": "阿里云 OSS"
  },
  "meta": {
    "placeholder": "请输入站点名称",
    "help": "显示在浏览器标题栏",
    "required": true,
    "min": 3,
    "max": 20,
    "pattern": "/^[a-z0-9_-]+$/",
    "expose": "public"
  }
}
```

说明：

- `options`
  用于 `radio` / `checkbox` / `select` 等枚举字段
- `meta`
  用于前端渲染扩展信息与安全暴露控制

### 3.3 字段 meta 协议

当前建议将字段 `meta` 分成两类理解：

#### 仅渲染扩展

- `placeholder`
- `help`
- `rows`
- `style`

这类字段主要给前端表单渲染器使用。

#### 后端已正式支持并校验

- `expose`
- `required`
- `min`
- `max`
- `pattern`

当前后端行为：

- `expose`
  会参与公开配置输出控制
- `required`
  对文本类字段做非空校验
- `min`
  对文本类字段做最小长度校验
- `max`
  对文本类字段做最大长度校验
- `pattern`
  对文本类字段做正则格式校验

说明：

- 当前强校验范围主要针对 `text`、`textarea`、`password`
- `switch`、`radio`、`select`、`checkbox`、`json` 等类型有各自独立值校验规则

### 3.4 字段暴露规则

当前后端已约束：

- `extra.meta.expose`
  仅允许 `public`、`protected`、`private`

语义建议：

- `public`
  允许输出到宿主前端界面，例如站点标题、站点描述
- `protected`
  允许宿主内部服务读取，但不应直接暴露到公共界面
- `private`
  仅后端管理使用，例如支付密钥、接口密钥、签名私钥

当前公开读取能力：

- `public_system_config()`
- `SystemConfigService::public()`

返回规则：

- 仅返回 `meta.expose = public` 的字段
- 返回格式为完整路径 key，例如 `system.basic.site_title`

## 4. 运行时读取规则

当前推荐读取方式：

```php
system_config('system.basic.site_title')
```

也支持：

```php
SystemConfigService::value('system.basic.site_title')
SystemConfigService::group('oauth')
public_system_config()
```

规则：

- 业务代码优先通过 helper / service 读取
- 不建议业务代码直接查询 `system_configs`
- 读取默认走缓存

## 5. Settings Facade 规则

统一设置门面分为两类：

- 系统设置
- 插件设置

接口协议见：

- [settings-facade.md](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/docs/settings-facade.md)

这里仅强调治理边界。

### 5.1 系统设置职责

系统设置接口负责：

- 获取系统配置目录
- 获取指定 section 的 schema 和 values
- 保存当前 section 的配置值

系统设置管理接口负责：

- 分组 CRUD
- 字段 CRUD
- 分组和字段元信息维护

## 6. 插件配置托管规则

插件配置允许纳入系统配置中心统一管理。

目标：

- 避免插件各自定义配置读取方式
- 统一管理 UI 和存储协议
- 统一输出和权限控制

### 6.1 插件配置入口

插件推荐在：

- `addons/<Plugin>/Config/settings.php`

中声明托管配置。

支持的顶层关键字段：

- `enabled`
- `mode`
- `managed_by`
- `injection`
- `cleanup`
- `icon`
- `sections`

### 6.2 插件配置模式

当前支持：

- `hosted`
  由系统设置中心承载 schema、存储和保存
- `external_route`
  插件自行提供配置页面路由，设置中心仅做目录跳转
- `none`
  插件不接入设置中心

### 6.3 插件配置管理归属

当前支持：

- `managed_by = system`
- `managed_by = plugin`

语义：

- `system`
  插件配置由系统设置中心统一保存
- `plugin`
  插件配置由插件自身处理，设置中心可展示但不允许保存 hosted section

当前后端行为：

- `managed_by = plugin` 且 `mode = hosted`
  section 详情中的 `meta.editable = false`
- 对该 section 的保存请求会被拒绝

### 6.4 插件配置注入策略

当前支持：

- `injection.strategy = merge`
- `injection.strategy = overwrite`
- `injection.strategy = skip`

建议语义：

- `merge`
  按插件声明补齐系统托管分组和字段，保留已有值
- `overwrite`
  按插件声明覆盖已有定义元信息
- `skip`
  若已存在宿主配置资源则跳过注入

当前系统已完成的是规则归一化与元信息落库，后续插件安装流可继续围绕该策略细化注入细节。

### 6.5 插件卸载清理策略

当前支持：

- `cleanup.on_uninstall = retain`
- `cleanup.on_uninstall = purge`

语义：

- `retain`
  卸载插件时保留托管配置
- `purge`
  卸载插件时同步删除对应的配置分组和配置项

当前后端行为：

- 卸载插件后，若策略为 `purge`，会删除 `addon_code = {code}` 关联的全部配置资源

### 6.6 托管配置落库约定

注册式 hosted settings 当前约定：

- 根分组：`addon_{code}`
- section 分组：`addon_{code}_{sectionKey}`

例如插件 `cms` 的 `basic` section：

- root group: `addon_cms`
- section group: `addon_cms_basic`

字段定义来自：

- `sections[].schema.fields`

默认值来自：

- `sections[].defaults`

## 7. Legacy 插件兼容规则

如果插件未提供 `Config/settings.php`，但提供了：

- `Config/config.php`

则当前仍按 legacy 插件处理。

兼容行为：

- facade 目录中仍会生成一个 `basic` section
- 详情和保存仍复用旧通用配置链路
- 不要求旧插件立即迁移到注册式托管配置

## 8. 前后端协作规则

前端消费协议时应遵循：

- 使用 `render.schema` 进行渲染
- 使用 `type` 判断字段组件
- 使用 `meta` 读取占位符、帮助文案、展示控制等扩展信息
- 使用 `section.extra.layout.mode` 或 `render.schema.layout.mode` 处理分组展示方式

后端维护规则时应遵循：

- schema 只负责描述渲染协议，不直接混入业务值
- values 只承载当前字段值
- 安全暴露规则必须通过 `meta.expose` 明确声明

## 9. 当前结论

当前系统配置治理规则已经明确为：

1. 配置分组最多两级
2. 配置统一通过 `SystemConfigGroup` / `SystemConfig` 存储
3. 前端渲染字段统一使用 `type`
4. 分组布局统一通过 `extra.layout.mode` / `schema.layout.mode` 描述
5. 字段扩展信息统一通过 `meta` 描述
6. 对外暴露能力统一通过 `meta.expose` 控制
7. 插件配置允许进入系统配置中心统一托管
8. 插件可声明系统托管或插件自管
9. 插件可声明卸载时保留或清理托管配置
10. legacy 插件配置仍保留兼容链路
