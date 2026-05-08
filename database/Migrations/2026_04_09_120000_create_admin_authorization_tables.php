<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createAdminRolesTable();
        $this->createAdminResourcesTable();
        $this->createAdminUserRolesTable();
        $this->createAdminGrantsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_grants');
        Schema::dropIfExists('admin_user_roles');
        Schema::dropIfExists('admin_resources');
        Schema::dropIfExists('admin_roles');
    }

    private function createAdminRolesTable(): void
    {
        Schema::create('admin_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->comment('角色编码');
            $table->string('name', 100)->comment('角色名称');
            $table->string('description', 255)->nullable()->comment('角色说明');
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('租户ID');
            $table->string('scope_mode', 30)->nullable()->comment('默认数据范围模式');
            $table->json('scope_value_json')->nullable()->comment('默认数据范围参数');
            $table->unsignedTinyInteger('is_system')->default(0)->comment('是否系统角色');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态');
            $table->integer('sort')->default(0)->comment('排序');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->nullable()->comment('软删时间');

            $table->unique(['tenant_id', 'code'], 'uniq_admin_roles_code_tenant');
            $table->index('status', 'idx_admin_roles_status');
            $table->index('is_system', 'idx_admin_roles_is_system');
        });

        setCommentTable('admin_roles', '后台角色表');
    }

    private function createAdminResourcesTable(): void
    {
        Schema::create('admin_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150)->comment('资源标识');
            $table->string('title', 100)->comment('资源名称');
            $table->string('type', 30)->comment('资源类型');
            $table->string('module', 50)->comment('所属模块');
            $table->string('page_key', 100)->nullable()->comment('页面标识');
            $table->string('addon_code', 50)->nullable()->comment('所属插件编码');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父资源ID');
            $table->unsignedInteger('level')->default(0)->comment('层级');
            $table->string('path', 255)->nullable()->comment('资源路径');
            $table->string('route', 150)->nullable()->comment('路由标识');
            $table->string('icon', 100)->nullable()->comment('图标');
            $table->json('meta_json')->nullable()->comment('扩展信息');
            $table->unsignedTinyInteger('is_nav')->default(0)->comment('是否导航节点');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态');
            $table->integer('sort')->default(0)->comment('排序');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->nullable()->comment('软删时间');

            $table->unique('name', 'uniq_admin_resources_name');
            $table->index('type', 'idx_admin_resources_type');
            $table->index('module', 'idx_admin_resources_module');
            $table->index('page_key', 'idx_admin_resources_page_key');
            $table->index('parent_id', 'idx_admin_resources_parent');
            $table->index('status', 'idx_admin_resources_status');
        });

        setCommentTable('admin_resources', '后台资源表');
    }

    private function createAdminUserRolesTable(): void
    {
        Schema::create('admin_user_roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('租户ID');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');

            $table->unique(['tenant_id', 'user_id', 'role_id'], 'uniq_admin_user_roles');
            $table->index('user_id', 'idx_admin_user_roles_user');
            $table->index('role_id', 'idx_admin_user_roles_role');
        });

        setCommentTable('admin_user_roles', '后台用户角色关系表');
    }

    private function createAdminGrantsTable(): void
    {
        Schema::create('admin_grants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('租户ID');
            $table->string('subject_type', 20)->comment('授权主体类型');
            $table->unsignedBigInteger('subject_id')->comment('授权主体ID');
            $table->unsignedBigInteger('resource_id')->comment('资源ID');
            $table->string('effect', 10)->comment('授权效果');
            $table->json('abilities_json')->comment('能力列表');
            $table->string('scope_type', 30)->nullable()->comment('范围类型');
            $table->json('scope_value_json')->nullable()->comment('范围值');
            $table->json('conditions_json')->nullable()->comment('附加条件');
            $table->integer('priority')->default(0)->comment('优先级');
            $table->unsignedInteger('expires_at')->nullable()->comment('失效时间');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');

            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'idx_admin_grants_subject');
            $table->index('resource_id', 'idx_admin_grants_resource');
            $table->index('effect', 'idx_admin_grants_effect');
            $table->index('expires_at', 'idx_admin_grants_expires_at');
        });

        setCommentTable('admin_grants', '后台授权关系表');
    }
};
