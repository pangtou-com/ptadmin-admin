# Field Rule Matrix

本文档定义系统配置与插件托管配置当前正式支持的字段协议白名单。

目标：

- 明确支持的 `type`
- 明确支持的 `meta`
- 明确每种 `type` 对应可用的 `meta`
- 明确哪些规则只是透传，哪些已由后端强校验

## 1. 支持的字段类型

当前正式支持：

- `text`
- `textarea`
- `password`
- `switch`
- `radio`
- `select`
- `checkbox`
- `json`

兼容说明：

- 历史 `component` 仍可被后端识别并归一化为 `type`
- 对外协议统一只使用 `type`

## 2. 支持的 meta 键

当前白名单分两类：

### 2.1 后端强校验

- `expose`
- `required`
- `min`
- `max`
- `pattern`

### 2.2 渲染透传

- `placeholder`
- `help`
- `rows`
- `style`

说明：

- “强校验”表示后端保存值时会参与校验
- “渲染透传”表示后端保留并下发给前端，但当前不做通用约束判断

## 3. 类型与 meta 对应矩阵

### 3.1 `text`

支持：

- `placeholder`
- `help`
- `style`
- `expose`
- `required`
- `min`
- `max`
- `pattern`

后端规则：

- 必须提交标量值
- `required/min/max/pattern` 生效

### 3.2 `textarea`

支持：

- `placeholder`
- `help`
- `rows`
- `style`
- `expose`
- `required`
- `min`
- `max`
- `pattern`

后端规则：

- 必须提交标量值
- `required/min/max/pattern` 生效

### 3.3 `password`

支持：

- `placeholder`
- `help`
- `style`
- `expose`
- `required`
- `min`
- `max`
- `pattern`

后端规则：

- 必须提交标量值
- `required/min/max/pattern` 生效

## 4. 枚举类字段

### 4.1 `radio`

支持：

- `help`
- `style`
- `expose`

额外依赖：

- `options`

后端规则：

- 如果声明了 `options`，保存值必须在允许选项中

### 4.2 `select`

支持：

- `help`
- `style`
- `expose`

额外依赖：

- `options`

后端规则：

- 如果声明了 `options`，保存值必须在允许选项中

### 4.3 `checkbox`

支持：

- `help`
- `style`
- `expose`

额外依赖：

- `options`

后端规则：

- 保存值必须是数组
- 如果声明了 `options`，数组成员必须都在允许选项中

## 5. 结构类字段

### 5.1 `switch`

支持：

- `help`
- `style`
- `expose`

后端规则：

- 仅支持布尔值或 `0/1`

### 5.2 `json`

支持：

- `placeholder`
- `help`
- `rows`
- `style`
- `expose`

后端规则：

- 仅支持数组
- 或可解析为数组的 JSON 字符串

## 6. 当前不建议的组合

虽然部分组合不会立即报错，但当前不建议这样使用：

- 给 `switch` 配 `pattern`
- 给 `radio/select/checkbox` 配 `min/max`
- 给 `json` 配 `required/min/max/pattern`
- 给敏感字段配置 `expose = public`

## 7. 当前推荐用法

### 文本字段

适合：

- `required`
- `min`
- `max`
- `pattern`
- `placeholder`
- `help`

### 枚举字段

适合：

- `options`
- `help`
- `style`

### 敏感字段

建议：

- `type = password` 或 `text`
- `meta.expose = private`

## 8. 事实来源

当前白名单规则以代码为准，核心定义集中在：

- [ConfigRuleValidator.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/src/Admin/Support/ConfigRuleValidator.php)
- [SystemConfigService.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/src/Admin/Services/SystemConfigService.php)
- [SettingsRegistryService.php](/Users/fangwei/projectPTAdmin/ptadmin.pangtou.com/ptadmin/src/Admin/Services/Settings/SettingsRegistryService.php)
