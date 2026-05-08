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
        Schema::create('admin_tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->comment('租户编码');
            $table->string('name', 100)->comment('租户名称');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态');
            $table->json('settings_json')->nullable()->comment('租户配置');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->nullable()->comment('删除时间');
            $table->unique('code', 'uniq_admin_tenants_code');
            $table->index('status', 'idx_admin_tenants_status');
            $table->engine = 'InnoDB';
        });
        setCommentTable('admin_tenants', '后台租户表');

        Schema::create('admin_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('租户ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父组织ID');
            $table->string('code', 100)->comment('组织编码');
            $table->string('name', 100)->comment('组织名称');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->json('meta_json')->nullable()->comment('扩展信息');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->nullable()->comment('删除时间');
            $table->unique(['tenant_id', 'code'], 'uniq_admin_organizations_tenant_code');
            $table->index(['tenant_id', 'parent_id'], 'idx_admin_organizations_tree');
            $table->index('status', 'idx_admin_organizations_status');
            $table->engine = 'InnoDB';
        });
        setCommentTable('admin_organizations', '后台组织表');

        Schema::create('admin_departments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('租户ID');
            $table->unsignedBigInteger('organization_id')->default(0)->comment('组织ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父部门ID');
            $table->string('code', 100)->comment('部门编码');
            $table->string('name', 100)->comment('部门名称');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->json('meta_json')->nullable()->comment('扩展信息');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unsignedInteger('deleted_at')->nullable()->comment('删除时间');
            $table->unique(['tenant_id', 'organization_id', 'code'], 'uniq_admin_departments_org_code');
            $table->index(['organization_id', 'parent_id'], 'idx_admin_departments_tree');
            $table->index('status', 'idx_admin_departments_status');
            $table->engine = 'InnoDB';
        });
        setCommentTable('admin_departments', '后台部门表');

        Schema::create('admin_user_org_relations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('租户ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('organization_id')->comment('组织ID');
            $table->unsignedBigInteger('department_id')->nullable()->comment('部门ID');
            $table->unsignedTinyInteger('is_primary')->default(0)->comment('是否主归属');
            $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
            $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            $table->unique(['tenant_id', 'user_id', 'organization_id', 'department_id'], 'uniq_admin_user_org_relations');
            $table->index(['tenant_id', 'user_id'], 'idx_admin_user_org_user');
            $table->index(['organization_id', 'department_id'], 'idx_admin_user_org_org');
            $table->engine = 'InnoDB';
        });
        setCommentTable('admin_user_org_relations', '后台用户组织关系表');
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_org_relations');
        Schema::dropIfExists('admin_departments');
        Schema::dropIfExists('admin_organizations');
        Schema::dropIfExists('admin_tenants');
    }
};
