# 前端设置中心对接文档

本文档基于当前 `ptadmin` 后端代码真实实现整理，覆盖：
- 系统设置接口
- 插件设置接口
- 统一响应格式
- 表单 schema 协议
- 前端渲染与保存约定

当前接口定义位置：
- [routes/admin.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/routes/admin.php:134)
- [src/Admin/Controllers/SettingsController.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/src/Admin/Controllers/SettingsController.php:14)

## 1. 统一说明

### 1.1 路由前缀

以下文档中的接口路径均以后台管理路由前缀为基础，当前测试环境表现为：

```text
/system/...
```

实际项目中如果后台前缀有调整，请以前端运行环境的 `admin_route_prefix()` 为准。

### 1.2 鉴权

所有设置接口都需要后台登录态，并且要求拥有权限点：

```text
system.config
```

### 1.3 返回格式

成功返回：

```json
{
  "code": 0,
  "message": "success",
  "data": {}
}
```

失败返回：

```json
{
  "code": 10000,
  "message": "错误信息"
}
```

注意：
- HTTP 通常仍然返回 `200`
- 业务是否成功以前端判断 `code === 0` 为准

## 2. 设置中心总设计

设置中心分两类：

1. 系统设置
2. 插件设置

它们都采用统一思路：
- 先拉取目录 `catalog`
- 再按分组拉取详情 `section`
- 最后提交分组值 `save`

## 3. 系统设置接口

### 3.1 获取系统设置目录

`GET /system/settings/system/catalog`

用途：
- 获取系统设置页面左侧导航
- 返回所有可展示的二级配置分组

返回示例：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "scope": "system",
    "owner": {
      "code": "system",
      "name": "系统设置"
    },
    "sections": [
      {
        "key": "basic",
        "title": "基础配置",
        "description": "站点基础配置",
        "icon": "Setting",
        "order": 90,
        "mode": "hosted",
        "render": {
          "engine": "pt-render",
          "version": "1.0"
        }
      },
      {
        "key": "oauth.wechat",
        "title": "第三方登录 / 微信登录",
        "description": "微信授权登录参数",
        "icon": "",
        "order": 100,
        "mode": "hosted",
        "render": {
          "engine": "pt-render",
          "version": "1.0"
        }
      }
    ]
  }
}
```

字段说明：
- `scope`: 固定为 `system`
- `owner.code`: 固定为 `system`
- `sections[].key`: 分组唯一标识，详情/保存接口都用它
- `sections[].key` 规则：
  - 根分组为 `system` 时，直接使用二级分组名，如 `basic`
  - 非 `system` 根分组时，使用 `根分组.二级分组`，如 `oauth.wechat`

### 3.2 获取系统设置分组详情

`GET /system/settings/system/sections/{sectionKey}`

示例：

```text
GET /system/settings/system/sections/basic
GET /system/settings/system/sections/oauth.wechat
```

返回示例：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "scope": "system",
    "owner": {
      "code": "system",
      "name": "系统设置"
    },
    "section": {
      "key": "basic",
      "title": "基础配置",
      "description": "站点基础配置",
      "extra": {
        "icon": "Setting",
        "layout": {
          "mode": "block",
          "labelWidth": 140
        }
      }
    },
    "render": {
      "engine": "pt-render",
      "version": "1.0",
      "schema": {
        "name": "system_basic_settings",
        "title": "基础配置",
        "layout": {
          "mode": "block",
          "labelWidth": 140
        },
        "fields": [
          {
            "name": "site_title",
            "type": "text",
            "label": "站点标题",
            "default": "PTAdmin",
            "comment": "",
            "placeholder": "请输入站点标题",
            "metadata": {
              "placeholder": "请输入站点标题",
              "expose": "public",
              "storage_type": "text"
            }
          },
          {
            "name": "login_captcha",
            "type": "switch",
            "label": "登录验证码",
            "default": 0,
            "comment": "",
            "metadata": {
              "storage_type": "switch"
            }
          }
        ]
      }
    },
    "values": {
      "site_title": "PTAdmin",
      "login_captcha": 1
    },
    "meta": {
      "editable": true
    }
  }
}
```

字段说明：
- `section.extra.layout.mode`: 分组布局建议
  - `tab`: tab 布局，一次只展示一个区域
  - `block`: 区块布局，默认全部展示
- `render.schema`: 前端表单渲染协议
- `values`: 当前已保存值
- `meta.editable`: 是否允许保存，系统设置固定为 `true`

### 3.3 保存系统设置分组

`PUT /system/settings/system/sections/{sectionKey}`

推荐提交格式：

```json
{
  "values": {
    "site_title": "PTAdmin Next",
    "login_captcha": 0
  }
}
```

也兼容直接平铺：

```json
{
  "site_title": "PTAdmin Next",
  "login_captcha": 0
}
```

返回示例：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "values": {
      "site_title": "PTAdmin Next",
      "login_captcha": 0
    },
    "meta": {
      "updated_at": "2026-05-01 10:30:00",
      "updated_by": "admin"
    }
  }
}
```

保存规则：
- 支持局部保存，未提交字段保持原值
- 前端建议始终按当前表单所有字段提交，便于状态管理
- 也允许只提交变更字段

## 4. 插件设置接口

插件设置分 4 种情况：

1. `hosted + managed_by=system`
2. `hosted + managed_by=plugin`
3. `external_route`
4. `none`

### 4.1 获取插件设置目录

`GET /system/settings/plugins/catalog`

返回示例：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "scope": "plugin",
    "results": [
      {
        "owner": {
          "code": "cms",
          "name": "内容管理系统"
        },
        "description": "内容管理系统基础能力",
        "icon": "Document",
        "enabled": true,
        "version": "1.0.0",
        "settings": {
          "enabled": true,
          "mode": "hosted",
          "managed_by": "system",
          "path": "",
          "injection": {
            "strategy": "merge"
          },
          "cleanup": {
            "on_uninstall": "retain"
          },
          "sections": [
            {
              "key": "basic",
              "title": "基础配置",
              "description": "内容管理系统基础配置",
              "icon": "",
              "order": 10,
              "mode": "hosted",
              "render": {
                "engine": "pt-render",
                "version": "1.0"
              }
            }
          ]
        }
      }
    ]
  }
}
```

字段说明：
- `results[].owner`: 插件基础信息
- `results[].settings.mode`:
  - `hosted`: 插件配置由设置中心承载
  - `external_route`: 插件自己提供独立设置页面
  - `none`: 不暴露设置，目录中不会出现
- `results[].settings.managed_by`:
  - `system`: 可在设置中心读取和保存
  - `plugin`: 可读取，但不允许由设置中心保存
- `results[].settings.path`:
  - 仅 `external_route` 时有效
  - 前端应直接跳转到这个路径

### 4.2 获取插件分组详情

`GET /system/settings/plugins/{code}/sections/{sectionKey}`

示例：

```text
GET /system/settings/plugins/cms/sections/basic
```

#### 4.2.1 hosted 模式返回

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "scope": "plugin",
    "owner": {
      "code": "cms",
      "name": "内容管理系统"
    },
    "section": {
      "key": "basic",
      "title": "基础配置",
      "description": "内容管理系统基础配置",
      "extra": {
        "managed_by": "system",
        "cleanup": {
          "on_uninstall": "retain"
        }
      }
    },
    "render": {
      "engine": "pt-render",
      "version": "1.0",
      "schema": {
        "name": "cms_basic_settings",
        "title": "基础配置",
        "layout": {
          "mode": "block",
          "labelWidth": 140
        },
        "fields": [
          {
            "name": "site_name",
            "type": "text",
            "label": "站点名称",
            "meta": {
              "placeholder": "请输入站点名称"
            }
          },
          {
            "name": "enabled",
            "type": "switch",
            "label": "启用状态"
          }
        ]
      }
    },
    "values": {
      "site_name": "CMS Demo",
      "enabled": 1
    },
    "meta": {
      "editable": true,
      "supported": true
    }
  }
}
```

#### 4.2.2 legacy 插件兼容模式返回

这类插件没有新的 `Config/settings.php` 托管配置声明，系统会复用插件原有配置读写逻辑，但仍走统一接口。

返回结构和 hosted 一致，前端不需要额外区分：
- 仍然有 `render.schema`
- 仍然有 `values`
- 仍然调用同一个保存接口

#### 4.2.3 external_route 模式

此模式下不要请求分组详情。

如果请求，会返回：

```json
{
  "code": 10000,
  "message": "插件[cms]当前使用 external_route 模式，不提供 hosted settings section"
}
```

前端处理规则：
- 目录接口发现 `mode=external_route`
- 直接跳转 `settings.path`
- 不进入统一表单渲染页

#### 4.2.4 managed_by=plugin 模式

这种模式仍然可以读取详情，但不允许设置中心保存。

表现为：

```json
{
  "meta": {
    "editable": false,
    "supported": true
  }
}
```

前端处理规则：
- 表单可展示
- 保存按钮禁用
- 或显示“该配置由插件自身管理”

### 4.3 保存插件分组配置

`PUT /system/settings/plugins/{code}/sections/{sectionKey}`

请求示例：

```json
{
  "values": {
    "site_name": "CMS Hosted",
    "enabled": 0
  }
}
```

返回示例：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "values": {
      "site_name": "CMS Hosted",
      "enabled": 0
    },
    "meta": {
      "updated_at": "2026-05-01 10:30:00",
      "updated_by": "admin"
    }
  }
}
```

如果插件声明 `managed_by=plugin`，会返回失败：

```json
{
  "code": 10000,
  "message": "插件[cms]当前由插件自身管理配置，不允许通过系统设置中心保存"
}
```

## 5. schema 渲染协议

### 5.1 核心原则

后端已经统一使用 `type`，不再以 `component` 作为主协议。

前端渲染时应以：

```json
field.type
```

作为组件映射依据。

注意：
- 返回数据中理论上不应再依赖 `component`
- 老数据如果内部存在兼容映射，后端也会尽量归一化为 `type`

### 5.2 支持的字段类型

当前后端允许的标准字段类型：

```text
text
textarea
switch
radio
checkbox
select
json
password
```

前端建议映射：
- `text` -> 输入框
- `textarea` -> 多行输入框
- `switch` -> 开关
- `radio` -> 单选组
- `checkbox` -> 多选组
- `select` -> 下拉选择
- `json` -> JSON 编辑器或多行文本 JSON 编辑框
- `password` -> 密码输入框

### 5.3 字段公共结构

字段示例：

```json
{
  "name": "site_title",
  "type": "text",
  "label": "站点标题",
  "default": "PTAdmin",
  "comment": "站点显示标题",
  "options": [],
  "meta": {
    "placeholder": "请输入站点标题",
    "expose": "public"
  },
  "metadata": {
    "placeholder": "请输入站点标题",
    "expose": "public",
    "storage_type": "text"
  }
}
```

前端建议优先读取：
- `name`
- `type`
- `label`
- `options`
- `meta`

兼容建议：
- 系统设置接口里部分字段元数据会平铺到字段顶层，同时也存在 `metadata`
- 插件托管设置更多是保留在 `meta`
- 前端渲染层建议统一做一次字段归一化：
  - `fieldMeta = field.meta || field.metadata || {}`

### 5.4 options 结构

用于 `radio`、`checkbox`、`select`：

```json
[
  { "label": "公开", "value": "public" },
  { "label": "私有", "value": "private" }
]
```

### 5.5 layout 结构

分组级布局定义在：

```json
render.schema.layout
```

常见示例：

```json
{
  "mode": "block",
  "labelWidth": 140
}
```

支持值：
- `mode=tab`
- `mode=block`

前端建议：
- `block`: 当前分组字段全部渲染
- `tab`: 如果 schema 内未来扩展了多块子区域，可按 tab 展示；当前最小实现可先按普通单表单渲染

## 6. values 值类型约定

后端返回时已经做过运行时类型归一化，前端可直接使用：

- `text` / `textarea` / `password` -> `string`
- `switch` -> `0 | 1`
- `radio` -> 通常是 `string` 或 `number`
- `select` -> 通常是 `string` 或 `number`
- `checkbox` -> `array`
- `json` -> `array`

注意：
- `switch` 当前推荐按 `0/1` 处理，不要依赖布尔值
- 插件默认值里写 `true/false`，接口返回时也会归一成 `1/0`

## 7. 保存提交规则

### 7.1 推荐请求体

统一推荐：

```json
{
  "values": {
    "field_a": "xxx",
    "field_b": 1
  }
}
```

### 7.2 支持局部提交

后端只更新本次提交的字段，未提交字段保持原值。

因此以下两种都合法：

完整提交：

```json
{
  "values": {
    "site_name": "CMS Hosted",
    "enabled": 1
  }
}
```

局部提交：

```json
{
  "values": {
    "site_name": "CMS Hosted"
  }
}
```

### 7.3 类型要求

前端保存时建议严格按字段类型提交：

- `switch`: `0` / `1` 或 `true` / `false`
- `radio` / `select`: 单值
- `checkbox`: 数组
- `json`: 数组，或合法 JSON 字符串
- `text` / `textarea` / `password`: 标量字符串

## 8. 字段校验规则

### 8.1 系统设置保存校验

系统设置保存时会校验：

- `switch` 必须是布尔兼容值
- `radio/select` 必须在 `options` 允许值中
- `checkbox` 必须是数组，且每项必须在 `options` 中
- `json` 必须是数组或可解析的 JSON 字符串
- `text/textarea/password` 会校验 `required/min/max/pattern`

### 8.2 插件注册 schema 校验

插件设置 schema 在后端注册时也会校验：

- 分组最多两级
- 字段 `type` 必须在允许列表中
- `meta` 只能使用允许字段
- 不同 `type` 只允许对应的 `meta`
- `defaults` 里的字段必须在 `schema.fields` 中声明

这部分前端通常不用参与，但有助于理解为什么某些插件目录接口会直接失败。

## 9. expose 暴露级别

字段元信息中可能包含：

```json
{
  "expose": "public"
}
```

支持值：
- `public`
- `protected`
- `private`

含义：
- `public`: 可安全输出到前台界面
- `protected`: 受限输出
- `private`: 仅系统内部使用，不应向公开前端输出

对设置中心管理页来说：
- 这三个级别都可以出现在管理后台表单中
- 但如果你在做“站点公开配置下发”，只能使用 `public`

当前后端已有公开读取能力：
- [src/Admin/Services/SystemConfigService.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/src/Admin/Services/SystemConfigService.php:153)

## 10. 前端页面建议对接流程

### 10.1 系统设置页

1. 调用 `GET /system/settings/system/catalog`
2. 用 `sections` 构建左侧菜单
3. 默认选中第一个 `section.key`
4. 调用 `GET /system/settings/system/sections/{sectionKey}`
5. 用 `render.schema + values` 渲染表单
6. 点击保存后调用 `PUT /system/settings/system/sections/{sectionKey}`

### 10.2 插件设置页

1. 调用 `GET /system/settings/plugins/catalog`
2. 按插件分组展示 `results`
3. 对每个插件判断 `settings.mode`

处理规则：
- `hosted`: 可进入统一详情页
- `external_route`: 直接跳转 `settings.path`
- `none`: 目录里不会出现

进入 hosted 详情后：

1. 取插件 `code` 和分组 `section.key`
2. 调用 `GET /system/settings/plugins/{code}/sections/{sectionKey}`
3. 用 `render.schema + values` 渲染
4. 判断 `meta.editable`
5. 若可编辑，再调用保存接口

## 11. 前端必须处理的边界场景

### 11.1 请求成功但业务失败

后端很多异常会返回：
- HTTP 200
- `code != 0`

前端必须统一拦截：

```ts
if (resp.code !== 0) {
  throw new Error(resp.message || '请求失败')
}
```

### 11.2 external_route 插件

不要请求 section 详情，直接跳转。

### 11.3 managed_by=plugin

详情可以打开，但保存按钮必须禁用。

### 11.4 部分插件可能没有 icon

`icon` 可能为空字符串，前端要有默认图标兜底。

### 11.5 section.key 不是永远单段

系统设置里要支持：
- `basic`
- `oauth.wechat`

不要假设它永远没有点号。

## 12. 推荐的前端类型定义

```ts
type SettingsApiResp<T> = {
  code: number
  message: string
  data?: T
}

type SettingsSectionField = {
  name: string
  type: 'text' | 'textarea' | 'switch' | 'radio' | 'checkbox' | 'select' | 'json' | 'password'
  label: string
  default?: unknown
  comment?: string
  options?: Array<{ label: string; value: string | number }>
  meta?: Record<string, unknown>
  metadata?: Record<string, unknown>
}

type SettingsSectionDetail = {
  scope: 'system' | 'plugin'
  owner: {
    code: string
    name: string
  }
  section: {
    key: string
    title: string
    description?: string
    extra?: Record<string, unknown>
  }
  render: {
    engine: 'pt-render'
    version: '1.0'
    schema: {
      name?: string
      title?: string
      layout?: Record<string, unknown>
      fields: SettingsSectionField[]
    }
  }
  values: Record<string, unknown>
  meta: {
    editable: boolean
    supported?: boolean
  }
}
```

## 13. 最终对接建议

前端实现时建议额外做一层 `normalizeFieldSchema(field)`，统一处理：
- `field.type`
- `field.meta || field.metadata`
- `field.options`
- `switch` 的 `0/1` 与布尔组件映射

这样系统设置和插件设置就能复用同一套表单渲染器，不需要维护两套协议。
