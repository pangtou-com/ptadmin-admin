# Settings Facade

本文档定义后台“系统设置 / 插件设置”统一联调协议。

目标：

- 前端统一使用 `pt-render` 渲染
- 系统设置和插件设置统一走 `catalog / section detail / save`
- 插件可以通过 `Config/settings.php` 注册自己的托管配置
- 旧插件 `Config/config.php` 仍可通过 facade 接入，不需要一次性迁移

## 1. 接口清单

后台默认前缀为 `/system`，所以完整接口如下：

- `GET /system/settings/system/catalog`
- `GET /system/settings/system/sections/{sectionKey}`
- `PUT /system/settings/system/sections/{sectionKey}`
- `GET /system/settings/plugins/catalog`
- `GET /system/settings/plugins/{code}/sections/{sectionKey}`
- `PUT /system/settings/plugins/{code}/sections/{sectionKey}`

统一返回：

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

## 2. 系统设置协议

### 2.1 catalog

返回系统设置目录，前端据此渲染左侧导航。

关键字段：

- `scope`
  固定 `system`
- `owner`
  固定表示系统设置根节点
- `sections`
  可打开的配置分组

### 2.2 section detail

返回某个分组的表单 schema 和当前值。

关键字段：

- `section.key`
  分组编码
- `render.engine`
  当前固定为 `pt-render`
- `render.schema`
  可直接交给前端渲染器
- `values`
  当前配置值

### 2.3 save

请求体推荐：

```json
{
  "values": {
    "site_title": "PTAdmin",
    "login_captcha": true
  }
}
```

也兼容扁平提交：

```json
{
  "site_title": "PTAdmin",
  "login_captcha": true
}
```

## 3. 插件设置协议

### 3.1 catalog

返回所有已安装插件中可进入设置中心的条目。

关键字段：

- `owner.code`
  插件编码
- `settings.mode`
  `hosted` / `external_route` / `none`
- `settings.sections`
  仅 `hosted` 模式下返回

### 3.2 section detail / save

仅 `hosted` 模式提供 section 详情和保存。

如果插件是旧版 `Config/config.php` 配置：

- facade 目录中仍显示一个 `basic` section
- 详情和保存仍复用旧 `AddonPlatformService`
- 数据仍落在旧插件配置分组，不会产生第二套配置存储

## 4. 插件注册文件

推荐插件在 `addons/<Plugin>/Config/settings.php` 中声明设置：

```php
<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'mode' => 'hosted',
    'icon' => 'Document',
    'managed_by' => 'system',
    'injection' => [
        'strategy' => 'merge',
    ],
    'cleanup' => [
        'on_uninstall' => 'retain',
    ],
    'sections' => [
        [
            'key' => 'basic',
            'title' => '基础配置',
            'description' => '内容管理基础参数',
            'order' => 10,
            'schema' => [
                'layout' => [
                    'mode' => 'block',
                    'labelWidth' => 140,
                ],
                'fields' => [
                    [
                        'name' => 'site_name',
                        'type' => 'text',
                        'label' => '站点名称',
                        'meta' => [
                            'placeholder' => '请输入站点名称',
                            'expose' => 'public',
                        ],
                    ],
                    [
                        'name' => 'enabled',
                        'type' => 'switch',
                        'label' => '启用状态',
                    ],
                ],
            ],
            'defaults' => [
                'site_name' => 'CMS Demo',
                'enabled' => true,
            ],
        ],
    ],
];
```

也可以直接参考仓库模板：

- [plugin-settings.hosted.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/docs/examples/plugin-settings.hosted.php)
- [plugin-settings.external-route.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/docs/examples/plugin-settings.external-route.php)

### 4.1 最小字段

插件接入 hosted settings 的最小字段为：

- 顶层：
  - `enabled`
  - `mode`
  - `sections`
- section：
  - `key`
  - `title`
  - `schema`
  - `defaults`

建议同时提供：

- `description`
- `order`
- `icon`
- `schema.layout`

如果希望接入规则完整，建议同时声明：

- 顶层：
  - `managed_by`
  - `injection.strategy`
  - `cleanup.on_uninstall`
- 字段：
  - `meta.expose`

### 4.2 字段约束

- `mode`
  当前推荐使用 `hosted`
- `managed_by`
  仅允许 `system` 或 `plugin`
- `injection.strategy`
  仅允许 `merge`、`overwrite`、`skip`
- `cleanup.on_uninstall`
  仅允许 `retain`、`purge`
- `schema`
  必须是前端可直接消费的 `pt-render` schema
- `schema.layout.mode`
  分组布局规则，当前建议 `tab` 或 `block`
- `schema.fields[].type`
  字段类型，直接与前端渲染规则保持一致，例如 `text`、`switch`
- `schema.fields[].meta`
  字段扩展信息，用于前端表单渲染，例如 `placeholder`、`options`、`help`
- `schema.fields[].meta.expose`
  字段暴露等级，当前建议 `public`、`protected`、`private`
- `schema.fields[].meta.required`
  当前后端已对文本类字段做必填校验
- `schema.fields[].meta.min`
  当前后端已对文本类字段做最小长度校验
- `schema.fields[].meta.max`
  当前后端已对文本类字段做最大长度校验
- `schema.fields[].meta.pattern`
  当前后端已对文本类字段做正则格式校验
- `defaults`
  用于初始化托管配置项；未提交字段时，系统保留现值；未声明 default 的字段会按类型补空值
- `key`
  同一插件内必须唯一

### 4.3 必填 / 推荐 / 禁止

#### 必填

- 顶层：
  - `enabled`
  - `mode`
  - `sections`
- `hosted` mode 的每个 section：
  - `key`
  - `title`
  - `schema`
  - `defaults`
- `schema.fields[]` 的每个字段：
  - `name`
  - `type`
  - `label`

#### 推荐

- 顶层：
  - `icon`
  - `managed_by`
  - `injection.strategy`
  - `cleanup.on_uninstall`
- section：
  - `description`
  - `icon`
  - `order`
  - `schema.layout.mode`
- 字段：
  - `meta.placeholder`
  - `meta.help`
  - `meta.expose`
  - `meta.required`
  - `meta.min`
  - `meta.max`
  - `meta.pattern`

#### 禁止

- 在 hosted schema 中继续把 `component` 当作主协议字段
- 在 `defaults` 中声明未在 `schema.fields[]` 中出现的字段
- 在同一插件内复用重复的 `section.key`
- 在同一 section 内复用重复的 `fields[].name`
- 在 `public` 字段中放置密钥、令牌、私钥等敏感信息
- 给字段声明当前类型不支持的 `meta`
- 给 `required/min/max/pattern/expose` 传入非法值类型

### 4.4 模式建议

推荐按下面规则选模式：

- `hosted`
  插件希望复用宿主统一配置 UI、统一存储、统一读取
- `external_route`
  插件已有复杂设置页，需要完全自定义交互和行为
- `none`
  插件没有设置中心入口，或配置完全不由后台维护

`managed_by` 推荐规则：

- `hosted + system`
  作为默认组合
- `hosted + plugin`
  仅用于“需要展示但不允许系统中心保存”的场景
- `external_route + plugin`
  作为默认组合

### 4.5 布局与字段规则

分组辅助信息建议至少包含：

- `description`
- `icon`
- `order`
- `schema.layout.mode`

布局规则建议：

- `tab`
  同层分组只展示当前激活项，适合配置项较多且需要切换查看
- `block`
  默认展开全部字段，适合配置项较少或需要整体浏览

字段定义规则建议：

- 使用 `schema.fields[]`
- 使用 `type` 表达字段语义，不再使用 `component`
- 使用 `meta` 承载前端渲染扩展信息

注入策略当前已生效：

- `merge`
  更新定义，保留当前值，不删除旧字段
- `overwrite`
  更新定义，保留仍存在字段当前值，并删除已移除字段
- `skip`
  若 section 已存在，则冻结宿主当前定义，不再被新注册覆盖

当前后端已正式校验的 `meta` 字段：

- `expose`
- `required`
- `min`
- `max`
- `pattern`

当前主要作为渲染扩展透传的 `meta` 字段：

- `placeholder`
- `help`
- `rows`
- `style`

后端当前会优先按 `schema.fields[].type` 推断存储类型：

- `text`
- `textarea`
- `switch`
- `radio`
- `checkbox`
- `select`
- `json`
- `password`

其中：

- `switch` 会按布尔值保存
- `radio` 和 `select` 如果声明了 options，保存值必须在允许选项中
- `checkbox` 和 `json` 会按数组 / JSON 保存
- `checkbox` 如果声明了 options，提交值必须是数组且成员在允许选项中
- `text`、`textarea`、`password` 当前会执行 `required/min/max/pattern` 校验
- 未识别类型默认按文本保存
- 为兼容旧插件，后端仍会兜底识别 `component`，但返回给前端时会归一化为 `type`

### 4.6 多 section 建议

如果插件设置较复杂，建议按“可独立保存”的粒度拆分 section，例如：

- `basic`
- `security`
- `storage`
- `notify`

每个 section 都应该满足：

- 有明确标题
- 能独立保存
- 字段数不要过大，避免一个表单承载多个业务域

## 5. 后端最小落库约定

注册式 hosted settings：

- 根分组：`addon_{code}`
- section 分组：`addon_{code}_{sectionKey}`

旧版 `Config/config.php`：

- 根分组：`addon_{code}`
- section 分组：`basic`

这两条链路当前并存，方便渐进迁移。

## 6. 联调建议

建议按下面顺序联调：

1. 先打通 `GET /system/settings/plugins/catalog`
2. 再确认 `GET /system/settings/plugins/{code}/sections/{sectionKey}` 返回的 `render.schema` 可被 `pt-render` 直接渲染
3. 最后联调 `PUT` 保存，并确认保存后 `values` 回读一致

如果插件暂时还没有 `Config/settings.php`，但已经有 `Config/config.php`，可以先走 legacy 兼容链路接入设置中心。
