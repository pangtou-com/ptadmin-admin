<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 初始化项目数据表.
 */
class Initialization extends Migration
{
    /**
     * 默认后台数据.
     *
     * @var array
     */
    private $menu = [
        ['name' => 'console', 'parent_name' => '0', 'title' => '仪表盘', 'route' => 'console', 'icon' => 'layui-icon layui-icon-console', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'user', 'parent_name' => '0', 'title' => '用户管理', 'route' => null, 'icon' => 'layui-icon layui-icon-table', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'dir', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'user.users', 'parent_name' => 'user', 'title' => '会员列表', 'route' => 'users', 'icon' => null, 'addon_code' => null, 'guard_name' => 'admin', 'paths' => '["user"]', 'controller' => null, 'weight' => '0', 'note' => '', 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system', 'parent_name' => '0', 'title' => '系统管理', 'route' => null, 'icon' => 'layui-icon layui-icon-engine', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'dir', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system.role', 'parent_name' => 'system', 'title' => '系统角色', 'route' => 'roles', 'icon' => '', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system.system', 'parent_name' => 'system', 'title' => '系统管理员', 'route' => 'systems', 'icon' => '', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system.permissions', 'parent_name' => 'system', 'title' => '菜单栏目', 'route' => 'permissions', 'icon' => '', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system.login', 'parent_name' => 'system', 'title' => '登录日志', 'route' => 'system/login', 'icon' => '', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => '查看后端用户的登录日志信息', 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system.operate', 'parent_name' => 'system', 'title' => '操作日志', 'route' => 'operations', 'icon' => '', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => '查看后端管理操作日志信息', 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system.setting', 'parent_name' => 'system', 'title' => '系统配置', 'route' => 'settings', 'icon' => '', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'system.attachments', 'parent_name' => 'system', 'title' => '附件管理', 'route' => 'attachments', 'icon' => null, 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
        ['name' => 'addon', 'parent_name' => '0', 'title' => '插件管理', 'route' => null, 'icon' => 'layui-icon layui-icon-align-left', 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'dir', 'status' => '0', 'is_nav' => '1'],
        ['name' => 'addon.addons', 'parent_name' => 'addon', 'title' => '插件列表', 'route' => 'addons', 'icon' => null, 'addon_code' => null, 'guard_name' => 'admin', 'paths' => null, 'controller' => null, 'weight' => '0', 'note' => null, 'type' => 'nav', 'status' => '1', 'is_nav' => '1'],
    ];

    private $setting = [
        ['id' => '1', 'title' => '基础设置', 'name' => 'base', 'weight' => '99', 'parent_id' => '0', 'intro' => null],
        ['id' => '2', 'title' => '第三方登录设置', 'name' => 'title', 'weight' => '99', 'parent_id' => '0', 'intro' => null],
        ['id' => '3', 'title' => 'QQ登录', 'name' => 'qq_login', 'weight' => '99', 'parent_id' => '2', 'intro' => null, 'fields' => [
            ['title' => 'AppID', 'name' => 'app_id', 'value' => '', 'type' => 'text'],
            ['title' => 'AppSecret', 'name' => 'app_secret', 'value' => '', 'type' => 'text'],
            ['title' => 'ICON', 'name' => 'icon', 'value' => '', 'type' => 'img'],
        ]],
        ['id' => '4', 'title' => '微信登录', 'name' => 'wechat_login', 'weight' => '99', 'parent_id' => '2', 'intro' => null, 'fields' => [
            ['title' => 'AppID', 'name' => 'app_id', 'value' => '', 'type' => 'text'],
            ['title' => 'AppSecret', 'name' => 'app_secret', 'value' => '', 'type' => 'text'],
            ['title' => 'ICON', 'name' => 'icon', 'value' => '', 'type' => 'img'],
        ]],
        ['id' => '5', 'title' => '站点设置', 'name' => 'website', 'weight' => '99', 'parent_id' => '1', 'intro' => null, 'fields' => [
            ['title' => '站点状态', 'name' => 'website_status', 'value' => 'PTAdmin 建站工具', 'type' => 'text'],
            ['title' => '站点名称', 'name' => 'website_title', 'value' => 'PTAdmin 建站工具', 'type' => 'text'],
            ['title' => 'LOGO', 'name' => 'website_logo', 'value' => '', 'type' => 'radio', 'extra' => ['options' => ['关闭', '启用']]],
            ['title' => '关键词', 'name' => 'website_keyword', 'value' => '', 'type' => 'text'],
            ['title' => '描述', 'name' => 'website_description', 'value' => '', 'type' => 'text'],
        ]],
        ['id' => '6', 'title' => '第三方登录', 'name' => 'oauth', 'weight' => '0', 'parent_id' => '0', 'intro' => ''],
        ['id' => '7', 'title' => 'QQ登录', 'name' => 'qq_login', 'weight' => '0', 'parent_id' => '6', 'intro' => 'qq登录第三方授权', 'fields' => [
            ['title' => 'AppID', 'name' => 'app_id', 'value' => '', 'type' => 'text'],
            ['title' => 'AppSecret', 'name' => 'app_secret', 'value' => '', 'type' => 'text'],
            ['title' => 'ICON', 'name' => 'icon', 'value' => '', 'type' => 'img'],
        ]],
        ['id' => '8', 'title' => '微博登录', 'name' => 'weibo_login', 'weight' => '0', 'parent_id' => '6', 'intro' => null, 'fields' => [
            ['title' => 'AppID', 'name' => 'app_id', 'value' => '', 'type' => 'text'],
            ['title' => 'AppSecret', 'name' => 'app_secret', 'value' => '', 'type' => 'text'],
            ['title' => 'ICON', 'name' => 'icon', 'value' => '', 'type' => 'img'],
        ]],
    ];

    public function up(): void
    {
        $this->create_addons_table();
        $this->create_attachment_table();
        $this->create_settings_table();
        $this->create_system_table();
        $this->create_user_table();
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('setting_groups');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('operation_records');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('systems');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_bind_platforms');
        Schema::dropIfExists('user_money');
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('user_verifies');
    }

    /**
     * 创建附件表.
     */
    private function create_attachment_table(): void
    {
        if (!Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 255)->comment('文件标题');
                $table->char('md5', 32)->comment('文件MD5')->index();
                $table->string('mime', 50)->comment('文件类型');
                $table->string('suffix', 10)->comment('文件后缀');
                $table->string('driver', 50)->comment('存储驱动');
                $table->string('groups', 20)->comment('分组存储');
                $table->bigInteger('size')->unsigned()->default(0)->comment('文件大小，单位字节');
                $table->string('path', 255)->comment('存储路径');
                $table->unsignedInteger('quote')->default(0)->comment('引用次数');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('attachments').'` COMMENT = "附件表"');
        }
    }

    /**
     * 创建系统配置表.
     */
    private function create_settings_table(): void
    {
        if (!Schema::hasTable('setting_groups')) {
            Schema::create('setting_groups', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 255)->comment('文件标题');
                $table->string('name', 255)->comment('文件标题');
                $table->unsignedTinyInteger('weight')->default(99)->comment('配置权重');
                $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
                $table->string('intro', 255)->nullable()->comment('环境提示信息');
                $table->string('addon_code', 255)->nullable()->comment('插件编码');
                $table->unsignedTinyInteger('status')->default(1)->comment('是否启用');
                $table->unsignedInteger('deleted_at')->nullable()->comment('是否删除');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('setting_groups').'` COMMENT = "配置分组"');
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 255)->comment('配置标题');
                $table->string('name', 255)->comment('配置名称');
                $table->unsignedBigInteger('setting_group_id')->default(0)->comment('所属分类');
                $table->unsignedTinyInteger('weight')->default(99)->comment('配置权重');
                $table->string('type', 20)->comment('配置类型');
                $table->string('intro', 255)->nullable()->comment('配置说明');
                $table->json('extra')->nullable()->comment('扩展参数');
                $table->string('value', 255)->nullable()->comment('配置的值');
                $table->string('default_val', 100)->nullable()->comment('配置默认值');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('settings').'` COMMENT = "配置表"');
        }
    }

    /**
     * 创建系统管理相关表，菜单、权限、管理员等.
     */
    private function create_system_table(): void
    {
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id')->default(0);
                $table->unsignedBigInteger('model_id')->default(0);
                $table->string('model_type', 255);
            });
            DB::statement('ALTER TABLE `'.get_table_name('model_has_permissions').'` COMMENT = "用户所关联权限"');
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id')->default(0);
                $table->unsignedBigInteger('model_id')->default(0);
                $table->string('model_type', 255);
            });
            DB::statement('ALTER TABLE `'.get_table_name('model_has_roles').'` COMMENT = "用户所关联角色"');
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('role_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id')->default(0);
                $table->unsignedBigInteger('role_id')->default(0);
                $table->unique(['permission_id', 'role_id'], 'uk_index');
                $table->index('role_id', 'idx_role_id');
            });
            DB::statement('ALTER TABLE `'.get_table_name('role_has_permissions').'` COMMENT = "角色关联权限表"');
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('parent_name', 255)->default(0)->nullable()->comment('父级名称');
                $table->string('name', 255)->comment('权限名称，验证使用');
                $table->string('title', 255)->comment('权限标题，展示使用');
                $table->string('route', 255)->nullable()->comment('路由地址');
                $table->string('component', 255)->nullable()->comment('组件地址前后端分离开发使用');
                $table->string('icon', 50)->nullable()->comment('图标');
                $table->json('paths')->nullable()->comment('节点完整路径');
                $table->string('addon_code', 50)->nullable()->comment('插件编码');
                $table->string('guard_name', 50)->nullable()->comment('分组名称');
                $table->string('controller', 255)->nullable()->comment('控制器信息');
                $table->unsignedTinyInteger('weight')->default(0)->comment('权重');
                $table->string('note', 255)->nullable()->comment('备注信息');
                $table->char('type', 4)->comment('规则类型 dir = 菜单目录 ｜ nav = 菜单项目（只有这种类型才会有组件内容） | btn 按钮权限 | link 外部链接地址	');
                $table->unsignedTinyInteger('status')->default(1)->comment('状态');
                $table->unsignedTinyInteger('is_nav')->default(1)->comment('是否显示为导航');
                $table->unsignedInteger('deleted_at')->nullable()->comment('是否删除');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
                // $table->unique('name', 'uk_name');
            });
            DB::statement('ALTER TABLE `'.get_table_name('permissions').'` COMMENT = "权限表"');
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
                $table->string('guard_name', 50)->nullable()->comment('分组名称');
                $table->string('name', 255)->comment('角色名称，用于权限验证的名称，英文	');
                $table->string('title', 255)->comment('角色名称');
                $table->string('note', 255)->nullable()->comment('备注信息');
                $table->unsignedTinyInteger('status')->default(1)->comment('状态');
                $table->unsignedInteger('deleted_at')->nullable()->comment('是否删除');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('roles').'` COMMENT = "角色表"');
        }

        if (!Schema::hasTable('systems')) {
            Schema::create('systems', function (Blueprint $table): void {
                $table->id();
                $table->string('username', 20)->nullable()->comment('管理员账户名称');
                $table->string('nickname', 20)->comment('昵称（用于文章发布）');
                $table->string('password', 255)->comment('登录密码');
                $table->string('mobile', 30)->nullable()->comment('手机号码');
                $table->string('email', 255)->nullable()->comment('邮箱');
                $table->string('avatar', 255)->nullable()->comment('头像地址');
                $table->unsignedInteger('login_at')->default(0)->comment('最近登录地址');
                $table->string('login_ip', 50)->nullable()->comment('登录IP');
                $table->unsignedTinyInteger('is_founder')->default(0)->comment('是否为创始人');
                $table->unsignedTinyInteger('status')->default(1)->comment('状态');
                $table->unsignedInteger('creator_id')->default(0)->comment('创建人ID');
                $table->unsignedInteger('updater_id')->default(0)->comment('更新人ID');
                $table->unsignedInteger('deleted_at')->nullable()->comment('是否删除');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('systems').'` COMMENT = "管理后台人员表"');
        }

        if (!Schema::hasTable('system_logs')) {
            Schema::create('system_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('system_id')->default(0)->comment('用户ID');
                $table->unsignedInteger('login_at')->default(0)->comment('登录时间');
                $table->unsignedInteger('login_ip')->default(0)->comment('登录IP');
                $table->unsignedTinyInteger('status')->default(1)->comment('状态');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('system_logs').'` COMMENT = "用户登录日志表"');
        }

        if (!Schema::hasTable('operation_records')) {
            Schema::create('operation_records', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('system_id')->default(0)->comment('创建时间');
                $table->string('nickname', 100)->nullable()->comment('openid');
                $table->unsignedInteger('ip')->default(0)->comment('访问IP');
                $table->string('url', 500)->comment('访问路径');
                $table->string('title', 100)->comment('请求名称');
                $table->string('method', 50)->comment('请求方法');
                $table->string('controller', 255)->comment('请求控制器');
                $table->string('action', 50)->comment('执行方法');
                $table->string('request', 500)->nullable()->comment('请求参数');
                $table->string('response', 500)->nullable()->comment('响应结果');
                $table->integer('response_code')->comment('响应状态码');
                $table->unsignedDecimal('response_time')->comment('响应状态码');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('operation_records').'` COMMENT = "操作记录表"');
        }
    }

    private function create_addons_table(): void
    {
        if (!Schema::hasTable('addons')) {
            Schema::create('addons', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 255)->comment('插件名称');
                $table->string('code', 255)->comment('插件编码');
                $table->string('version', 10)->comment('当前版本');
                $table->string('framework', 10)->comment('依赖框架版本');
                $table->string('author', 255)->comment('插件作者');
                $table->string('intro', 255)->comment('插件介绍信息');
                $table->string('email', 50)->nullable()->comment('作者邮箱');
                $table->string('homepage', 255)->nullable()->comment('作者主页');
                $table->string('docs', 255)->nullable()->comment('文档地址');
                $table->unsignedTinyInteger('is_upload')->default(0)->comment('0、未上传；1、已上传');
                $table->unsignedTinyInteger('is_local')->default(0)->comment('安装方式，0、本地安装，1、云端安装');
                $table->unsignedTinyInteger('enabled')->default(0)->comment('0、未启动；1、启动	');
                $table->unsignedTinyInteger('develop')->default(0)->comment('是否为开发模式，通过自身创建的插件设置为开发模式');
                $table->json('extra')->nullable()->comment('插件配置信息');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('addons').'` COMMENT = "插件表"');
        }
    }

    /**
     * 创建用户表.
     */
    private function create_user_table(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('username', 20)->nullable()->comment('管理员账户名称');
                $table->string('nickname', 20)->comment('昵称（用于文章发布）');
                $table->string('password', 255)->comment('登录密码');
                $table->char('salt', 4)->comment('密码加盐');
                $table->string('mobile', 30)->nullable()->comment('手机号码');
                $table->string('email', 255)->nullable()->comment('邮箱');
                $table->string('avatar', 255)->nullable()->comment('头像地址');
                $table->unsignedTinyInteger('level')->default(0)->comment('用户等级');
                $table->unsignedTinyInteger('gender')->default(0)->comment('性别：0：未知，1：男，2:女');
                $table->unsignedInteger('birthday')->default(0)->comment('生日');
                $table->string('bio', 255)->nullable()->comment('签名');
                $table->unsignedDecimal('money', 10)->default(0)->comment('余额');
                $table->unsignedInteger('score')->default(0)->comment('积分');
                $table->unsignedInteger('login_days')->default(0)->comment('连续登录天数');
                $table->unsignedInteger('max_login_days')->default(0)->comment('最大连续登录天数');
                $table->unsignedInteger('pre_at')->default(0)->comment('上次登录时间');
                $table->unsignedInteger('last_at')->default(0)->comment('最新登录时间');
                $table->unsignedInteger('login_ip')->default(0)->comment('最新登录IP');
                $table->unsignedInteger('join_ip')->default(0)->comment('加入IP');
                $table->unsignedInteger('join_at')->default(0)->comment('加入时间');
                $table->unsignedTinyInteger('status')->default(1)->comment('状态');
                $table->unsignedInteger('deleted_at')->nullable()->comment('是否删除');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('users').'` COMMENT = "用户表"');
        }

        if (!Schema::hasTable('user_bind_platforms')) {
            Schema::create('user_bind_platforms', function (Blueprint $table): void {
                $table->id();
                $table->string('source', 20)->comment('授权所属来源');
                $table->string('open_id', 30)->comment('openid');
                $table->string('union_id', 30)->comment('UnionId')->index();
                $table->unsignedInteger('user_id')->default(0)->comment('创建时间');
                $table->string('nickname', 255)->comment('昵称');
                $table->string('avatar', 255)->nullable()->comment('头像	');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
                $table->unique(['source', 'open_id', 'union_id'], 'uk_index');
            });
            DB::statement('ALTER TABLE `'.get_table_name('user_bind_platforms').'` COMMENT = "用户第三方绑定表,如微信，小程序，PC"');
        }

        if (!Schema::hasTable('user_money')) {
            Schema::create('user_money', function (Blueprint $table): void {
                $table->id();
                $table->unsignedDecimal('money', 10)->default(0)->comment('变动金额');
                $table->unsignedDecimal('m_before', 10)->default(0)->comment('变动前');
                $table->unsignedDecimal('m_after', 10)->default(0)->comment('变动后');
                $table->unsignedTinyInteger('type')->default(0)->comment('变化类型：0：减少，1:增加');
                $table->string('scene', 20)->nullable()->comment('使用场景：如订单消费，退款等，具体根据配置');
                $table->string('intro', 255)->nullable()->comment('描述信息');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->unsignedInteger('target_id')->default(0)->comment('关联变动ID');
                $table->string('target_module', 100)->comment('关联模块');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('user_money').'` COMMENT = "用户金额变化表"');
        }

        if (!Schema::hasTable('user_tokens')) {
            Schema::create('user_tokens', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('ip')->default(0)->comment('最新使用的IP地址');
                $table->string('guard_name', 32)->nullable()->comment('守护分组');
                $table->unsignedBigInteger('target_id')->default(0);
                $table->string('target_type', 255)->nullable();
                $table->string('token', 64)->nullable();
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->unsignedInteger('last_used_at')->default(0)->comment('最新使用时间');
                $table->unsignedInteger('expires_at')->default(0)->comment('自定义过期时间，可每个token单独设置过期时间，单位为秒');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('user_tokens').'` COMMENT = "用户授权信息表"');
        }

        if (!Schema::hasTable('user_verifies')) {
            Schema::create('user_verifies', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('user_id')->default(0)->comment('记录用户ID，部分场景是需要的	');
                $table->string('target', 255)->comment('验证目标，如邮箱，手机等');
                $table->unsignedTinyInteger('type')->default(0)->comment('验证码类型：0 = 邮件验证，1 = 短信验证');
                $table->unsignedTinyInteger('scene')->default(0)->comment('使用场景：0=注册验证，1、登录验证，2 = 找回密码等配置');
                $table->json('send_param')->nullable()->comment('发送参数内容');
                $table->unsignedTinyInteger('verify_num')->default(0)->comment('验证次数');
                $table->unsignedTinyInteger('send_status')->default(0)->comment('发送状态');
                $table->unsignedTinyInteger('status')->default(0)->comment('是否已经使用，0：未使用，1:已使用	');
                $table->unsignedInteger('ip')->default(0)->comment('最新使用的IP地址');
                $table->unsignedInteger('send_at')->default(0)->comment('发送时间');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
            DB::statement('ALTER TABLE `'.get_table_name('user_verifies').'` COMMENT = "验证消息发送"');
        }
    }
}
