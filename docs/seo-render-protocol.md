# Frontend SEO Render Protocol

本文档定义宿主层前台 SEO 渲染协议。

目标：

- 前台 SEO 输出能力归宿主统一管理
- CMS 等插件只提供 SEO 上下文，不直接负责最终 `<head>` 输出
- 模板层避免直接依赖 `$page['seo']`、`$page['open_graph']` 这类内部结构
- 后续支持 `helper` 与 `@pt:*` 两种调用方式，但协议只定义一套

## 1. 职责边界

### 1.1 宿主负责

- 定义统一 SEO 上下文协议
- 提供 SEO 渲染 helper / 指令
- 负责最终 `<title>` / `<meta>` / `<link rel="canonical">` / JSON-LD 输出
- 负责模板中的覆盖、追加、替换规则

### 1.2 插件负责

- 提供当前页面 SEO 上下文数据
- 不直接决定宿主模板如何输出 `<head>`
- 不定义宿主层通用 SEO 指令

### 1.3 编译层负责

- 如果使用 `@pt:title()`、`@pt:seo::head()` 这类语法
- 则由模板编译层提供语法支持
- 编译能力可放在 `ptadmin-addon`
- 但协议归属仍属于 `ptadmin`

## 2. SEO 上下文协议

宿主层约定当前页面统一暴露一个 SEO 上下文，推荐字段如下：

```php
[
    'title' => '',
    'keywords' => '',
    'description' => '',
    'canonical' => '',
    'robots' => '',
    'open_graph' => [
        'type' => 'website',
        'title' => '',
        'description' => '',
        'url' => '',
        'image' => '',
        'site_name' => '',
        'locale' => '',
    ],
    'twitter' => [
        'card' => 'summary',
        'title' => '',
        'description' => '',
        'image' => '',
        'url' => '',
    ],
    'structured_data' => [
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
        ],
    ],
]
```

说明：

- `title`
  最终页面标题文本，不包含 `<title>` 标签
- `keywords`
  最终关键词字符串，推荐逗号分隔
- `description`
  最终描述文本
- `canonical`
  最终 canonical URL 或 path
- `robots`
  最终 robots 指令，如 `index,follow`
- `open_graph`
  最终 Open Graph 键值集合
- `twitter`
  最终 Twitter Card 键值集合
- `structured_data`
  最终 JSON-LD 数组，每个元素表示一个 schema 节点

## 3. 宿主渲染入口

宿主层统一定义以下渲染入口：

### 3.1 helper 形式

```php
seo_context()
share_seo_context(array $context, bool $merge = true)
seo_title()
seo_keywords()
seo_description()
seo_canonical()
seo_robots()
seo_social()
seo_jsonld()
```

### 3.2 指令形式

最终推荐模板协议：

```blade
<title>@pt:title()</title>
@pt:seo::head()
```

约定：

- helper 与指令都读取同一份宿主 SEO 上下文
- helper 是底层运行时接口
- 指令只是模板友好语法
- `share_seo_context()` 用于数据提供方向宿主注入 SEO 上下文
- `@pt:title()` 只负责输出 `<title>` 内文本
- `@pt:seo::head()` 负责输出其余全部常规 SEO head 标签

## 4. 默认行为

不传参数时，统一按“当前页面上下文”输出：

```blade
<title>@pt:title()</title>
@pt:seo::head()
```

对应规则：

- `@pt:title()` 输出 title 纯文本
- `@pt:seo::head()` 默认输出：
  - `<meta name="keywords">`
  - `<meta name="description">`
  - `<link rel="canonical">`
  - `<meta name="robots">`
  - `open_graph + twitter`
  - 全部 `structured_data`
- 当某项内容为空时，可不输出对应标签

## 5. 覆盖协议

页面模板允许局部覆盖 SEO 上下文。

推荐提供统一覆盖入口：

```blade
@pt:seo(
    title="活动页标题",
    title_mode="replace",
    keywords="活动,促销",
    keywords_mode="append",
    description="活动页说明",
    description_mode="replace",
    canonical="/activity/2026",
    canonical_mode="replace",
    robots="noindex,follow",
    robots_mode="replace"
)
```

也允许在 `head` 聚合输出时直接覆盖：

```blade
<title>@pt:title("活动页标题", mode="replace")</title>
@pt:seo::head(
    keywords="活动,促销",
    keywords_mode="append",
    description="活动页说明",
    description_mode="replace"
)
```

### 5.1 支持的 mode

- `replace`
  完全替换当前值
- `append`
  追加到当前值后面
- `prepend`
  追加到当前值前面

### 5.2 默认 mode

- `title`
  默认 `replace`
- `description`
  默认 `replace`
- `canonical`
  默认 `replace`
- `robots`
  默认 `replace`
- `keywords`
  默认 `append`
- `structured_data`
  默认 `append`

### 5.3 head 聚合输出控制

`@pt:seo::head()` 支持按项关闭输出：

```blade
<title>@pt:title()</title>
@pt:seo::head(
    with_keywords=false,
    with_robots=false,
    with_social=false
)
```

当前支持：

- `with_keywords`
- `with_description`
- `with_canonical`
- `with_robots`
- `with_social`
- `with_jsonld`

### 5.4 不建议模板直接逐项覆盖的内容

下面这些不建议在模板里逐条拼装：

- `open_graph`
- `twitter`
- `structured_data`

更推荐通过：

- `@pt:seo::head()`
- `@pt:seo(...)`

由宿主统一组装输出。

## 6. 数据提供方接入要求

CMS 等页面数据提供方接入宿主 SEO 协议时，最小要求：

- 返回最终 SEO 上下文数组
- 由宿主注入到当前模板上下文
- 模板只读宿主 SEO 能力，不直接解析插件内部数据结构

推荐做法：

- 插件侧继续保留自己的计算过程
- 但进入宿主模板之前，统一映射到宿主 SEO 上下文
- 推荐通过 `share_seo_context()` 注入

## 7. 与现有 helper 的关系

当前宿主层已存在：

- `seo_title()`
- `seo_keywords()`
- `seo_description()`
- `seo_canonical()`
- `seo_robots()`
- `seo_social()`
- `seo_jsonld()`

这些 helper 视为底层运行时接口。

模板层最终推荐统一使用：

```blade
<title>@pt:title()</title>
@pt:seo::head()
```

## 8. 推荐模板写法

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@pt:title()</title>
    @pt:seo::head()
</head>
```

常见变体：

```blade
<title>@pt:title("活动页标题", mode="replace")</title>
@pt:seo::head(
    keywords="活动,促销",
    keywords_mode="append",
    with_jsonld=false
)
```

## 9. 推荐落地顺序

建议按以下顺序实现：

1. 在 `ptadmin` 中定义统一 SEO 上下文读取与 helper 输出
2. 在宿主运行时中先跑通 helper
3. CMS 适配宿主 SEO 上下文，不再直接输出 `<head>`
4. 在 `ptadmin-addon` 中补 `@pt:title()` / `@pt:seo::head()` / `@pt:seo(...)` 等模板语法支持
5. 宿主模板统一切到指令形式

## 10. 当前结论

本协议确认以下结论：

- SEO 渲染能力归宿主层
- SEO 数据来源可来自 CMS 或其他插件
- `ptadmin` 是协议归属层
- `ptadmin-addon` 只是可能的语法编译层
- 模板不应直接依赖 CMS 的内部 SEO 数据结构
- 模板推荐协议固定为 `<title>@pt:title()</title>` + `@pt:seo::head()`
