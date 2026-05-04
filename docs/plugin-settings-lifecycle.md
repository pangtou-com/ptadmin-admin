# Plugin Settings Lifecycle

本文档定义插件配置接入系统设置中心后的生命周期规则。

目标：

- 明确插件配置在安装、初始化、进入设置页、卸载时的行为
- 明确 `managed_by`、`injection.strategy`、`cleanup.on_uninstall` 的执行边界
- 避免插件重复注入、误覆盖、误清理宿主配置

## 1. 参与对象

插件配置生命周期涉及三类对象：

- 插件注册文件
  `addons/<Plugin>/Config/settings.php`
- 插件配置分组
  `system_config_groups`
- 插件配置项
  `system_configs`

当前 hosted 配置落库命名约定：

- 根分组：`addon_{code}`
- section 分组：`addon_{code}_{sectionKey}`

例如：

- 插件 `cms`
- section `basic`

对应：

- `addon_cms`
- `addon_cms_basic`

## 2. 生命周期总览

当前建议将插件配置生命周期分为 6 个阶段：

1. 插件初始化脚手架
2. 插件安装完成
3. 插件首次进入设置中心
4. 插件后续进入设置中心
5. 插件升级或重新发布
6. 插件卸载

## 3. 插件初始化脚手架

当前行为：

- 执行插件初始化命令时，系统会自动补一个 `Config/settings.php` 脚手架
- 仅当目标插件不存在该文件时才生成

当前默认脚手架内容：

- `enabled = true`
- `mode = hosted`
- `managed_by = system`
- `injection.strategy = merge`
- `cleanup.on_uninstall = retain`
- 默认 `basic` section

规则：

- 如果插件已经存在 `Config/settings.php`，宿主不应自动覆盖
- 脚手架只负责提供标准模板，不负责强制注入数据库

## 4. 插件安装完成

当前实现中，安装流程已经支持：

- 识别插件是否声明 `Config/settings.php`
- 在插件目录中保留标准注册文件
- 卸载时根据注册文件执行托管配置清理

当前建议规范：

- 安装完成后，插件配置资源可以按注册规则进入托管体系
- 如果插件未声明设置中心配置，不应创建 hosted 配置资源

推荐口径：

- `mode = none`
  不注入配置资源
- `mode = external_route`
  不创建 hosted section 分组和字段
- `mode = hosted`
  允许进入托管配置注入链路

## 5. 首次进入设置中心

当前已实现的 hosted 配置创建时机是：

- 用户首次请求：
  - `GET /system/settings/plugins/{code}/sections/{sectionKey}`
  - 或 `PUT /system/settings/plugins/{code}/sections/{sectionKey}`

系统会执行：

1. 读取插件 `Config/settings.php`
2. 找到目标 `section`
3. 若 hosted 配置分组不存在，则自动创建
4. 根据 `schema.fields` 建立字段定义
5. 根据 `defaults` 初始化默认值

当前规则：

- 分组和字段按声明同步创建
- 已有字段不会因为再次进入详情页而重置 `value`
- 默认值写入 `default_val`
- 新字段首次创建时，`value` 与 `default_val` 同步
- 如果 `schema.fields` 中存在字段但 `defaults` 未声明，系统会按字段类型补运行时空值

## 6. 后续进入设置中心

对于已经存在的 hosted 配置资源，后续进入设置中心时，会按 `injection.strategy` 分三种行为：

### 6.1 `merge`

- 重新读取插件注册文件
- 同步 section 标题、说明、排序、布局等元信息
- 同步字段标题、类型、说明、`extra.meta`、`extra.options`
- 补齐缺失字段
- 保留已有字段当前值
- 不删除宿主已存在但注册文件已移除的字段

### 6.2 `overwrite`

- 重新读取插件注册文件
- 刷新 section 元信息与字段定义
- 保留仍存在字段的当前值
- 删除宿主中已经存在、但新注册文件不再声明的 hosted 字段

### 6.3 `skip`

- 如果 hosted section 已存在，则不再用新注册定义覆盖宿主现有定义
- section 详情优先返回宿主当前冻结定义
- 保留已有字段当前值

因此当前 `merge / overwrite / skip` 都已经有真实执行语义，不再只是元信息声明。

## 7. `managed_by` 规则

### 7.1 `managed_by = system`

语义：

- 配置定义由插件注册
- 配置保存由系统设置中心负责

当前行为：

- section 详情返回 `meta.editable = true`
- `PUT /system/settings/plugins/{code}/sections/{sectionKey}` 允许保存

### 7.2 `managed_by = plugin`

语义：

- 配置可由宿主展示
- 但保存权归插件自身

当前行为：

- hosted section 详情返回 `meta.editable = false`
- 系统设置中心拒绝保存该 section

推荐使用场景：

- 插件希望借用统一目录结构
- 但配置保存需要触发插件自定义逻辑、远端同步、额外校验

## 8. `injection.strategy` 规则

当前已支持的合法值：

- `merge`
- `overwrite`
- `skip`

### 8.1 `merge`

推荐作为默认策略。

语义：

- 缺失分组则创建
- 缺失字段则创建
- 已有字段定义按最新注册信息同步
- 已有字段当前值保留

适用场景：

- 大多数普通插件配置
- 宿主已经存在线上值，不允许被插件发布覆盖

### 8.2 `overwrite`

当前行为：

- 已有 hosted 配置定义按插件注册信息刷新
- 当前值仍保留，除非字段本身已被移除

适用场景：

- 插件配置协议发生结构升级
- 宿主明确接受字段定义重建

风险：

- 如果实现成“覆盖当前值”，容易误伤线上配置
- 因此建议只覆盖定义，不直接覆盖 `value`

### 8.3 `skip`

当前行为：

- 如果宿主已经存在 hosted 配置资源，则跳过后续定义覆盖
- 如果宿主没有 hosted 配置资源，首次仍会创建

适用场景：

- 宿主已接管该插件配置结构
- 插件只提供默认参考，不希望后续发布持续干预宿主定义

## 9. 字段变更规则

插件升级后，字段定义可能发生变化。

建议遵循：

### 9.1 新增字段

- 允许自动创建
- 默认值写入 `default_val`
- 若为新字段，初始化当前值

### 9.2 修改字段标题、说明、布局、meta

- 允许同步覆盖配置定义
- 不影响现有 `value`

### 9.3 删除字段

当前建议：

- 不自动物理删除已有配置项
- 先视为“插件注册中已不再声明”

原因：

- 自动删除会导致历史值不可恢复
- 宿主可能仍需要回滚插件版本

更稳妥的方式：

- 后续如需支持，可增加专门的“清理废弃字段”维护动作

## 10. `external_route` 生命周期规则

对于：

- `mode = external_route`

当前规则：

- 设置中心目录可以展示插件设置入口
- 不创建 hosted section 分组
- 不提供 hosted section detail/save 接口
- 前端跳转到插件声明的 `path`

推荐约束：

- `managed_by` 应默认为 `plugin`
- `injection.strategy` 建议使用 `skip`
- `cleanup.on_uninstall` 只描述插件自有配置清理策略，不影响 hosted 分组

## 11. `none` 生命周期规则

对于：

- `mode = none`

当前建议：

- 插件不进入设置中心目录
- 不创建 hosted 配置资源
- 不暴露 facade 配置详情接口

适用场景：

- 无配置插件
- 配置不通过后台管理
- 配置完全依赖代码、环境变量或外部服务

## 12. legacy 插件生命周期规则

对于只提供：

- `Config/config.php`

的 legacy 插件：

当前兼容行为：

- facade 中自动视为 `basic` section
- 仍复用旧 `AddonPlatformService` 配置链路
- 不强制迁移到注册式 hosted settings

建议：

- 新插件一律使用 `Config/settings.php`
- 旧插件逐步迁移，不要求一次性切换

## 13. 卸载清理规则

当前已支持：

- `cleanup.on_uninstall = retain`
- `cleanup.on_uninstall = purge`

### 13.1 retain

语义：

- 卸载插件时保留 `addon_code = {code}` 的 hosted 配置资源

适用场景：

- 未来可能重新安装插件并复用原配置
- 配置属于长期业务资产

### 13.2 purge

语义：

- 卸载插件时删除该插件关联的全部 hosted 配置分组和字段

当前已实现行为：

1. 卸载前读取插件设置注册信息
2. 卸载插件
3. 若 `cleanup.on_uninstall = purge`
4. 删除 `addon_code = {code}` 关联的全部 `system_config_groups`
5. 同时删除这些分组下的全部 `system_configs`

## 14. 卸载依赖校验规则

当前平台层会先做一次依赖校验：

- 如果仍有其他插件依赖当前插件
- 且用户未显式 `force`
- 则拒绝卸载

这样做的目的：

- 保留原有“被依赖插件不可随意卸载”的语义
- 避免底层卸载动作在依赖校验阶段误伤配置生命周期

## 15. 推荐执行顺序

对于 hosted 插件，推荐生命周期口径如下：

1. 插件初始化时生成 `Config/settings.php` 标准模板
2. 插件安装后保留注册文件，不强制覆盖宿主已有配置
3. 首次进入设置中心时按注册信息创建 hosted 配置资源
4. 后续进入设置中心时同步定义，保留当前值
5. 插件卸载时按 `cleanup.on_uninstall` 决定保留或清理 hosted 配置

## 16. 当前结论

当前插件配置生命周期规则可以收敛为：

1. 插件配置是否进入设置中心由 `mode` 决定
2. 插件配置是否允许系统中心保存由 `managed_by` 决定
3. 插件 hosted 配置的创建时机是首次访问 section
4. hosted 配置更新遵循“更新定义、保留值”的原则
5. 插件卸载时是否清理 hosted 配置由 `cleanup.on_uninstall` 决定
6. `merge` 应作为默认注入策略
7. `overwrite` 应优先理解为覆盖定义，而不是覆盖线上值
8. `skip` 适合宿主已接管配置结构的场景
