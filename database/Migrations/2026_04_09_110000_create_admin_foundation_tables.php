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
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->createOperationRecordsTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('system_config_groups');
        Schema::dropIfExists('operation_records');
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('admin_login_logs');
        Schema::dropIfExists('admins');
    }

    private function createAdminsTable(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 20)->nullable();
            $table->string('nickname', 20)->default('');
            $table->string('password', 255);
            $table->string('mobile', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('avatar', 255)->nullable();
            $table->unsignedInteger('login_at')->default(0);
            $table->string('login_ip', 50)->nullable();
            $table->unsignedTinyInteger('is_founder')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();

            $table->index('username', 'idx_admins_username');
            $table->index('is_founder', 'idx_admins_is_founder');
            $table->index('status', 'idx_admins_status');
        });

        $this->commentTable('admins', '后台管理员表');
    }

    private function createAdminLoginLogsTable(): void
    {
        Schema::create('admin_login_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedInteger('login_at')->default(0);
            $table->unsignedInteger('login_ip')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->index('admin_id', 'idx_admin_login_logs_admin_id');
        });

        $this->commentTable('admin_login_logs', '后台管理员登录日志表');
    }

    private function createUserTokensTable(): void
    {
        Schema::create('user_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('guard_name', 50);
            $table->string('token', 64);
            $table->unsignedInteger('ip')->default(0);
            $table->unsignedInteger('expires_at')->default(0);
            $table->unsignedInteger('last_used_at')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->index(['target_type', 'target_id'], 'idx_user_tokens_target');
            $table->index('guard_name', 'idx_user_tokens_guard_name');
        });

        $this->commentTable('user_tokens', '访问令牌表');
    }

    private function createOperationRecordsTable(): void
    {
        Schema::create('operation_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->default(0);
            $table->string('nickname', 50)->default('');
            $table->unsignedInteger('ip')->default(0);
            $table->string('url', 255)->default('');
            $table->string('title', 255)->default('');
            $table->string('method', 20)->default('');
            $table->string('controller', 255)->default('');
            $table->string('action', 100)->default('');
            $table->text('request')->nullable();
            $table->text('response')->nullable();
            $table->text('sql_param')->nullable();
            $table->unsignedSmallInteger('response_code')->default(200);
            $table->decimal('response_time', 10, 2)->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->index('admin_id', 'idx_operation_records_admin_id');
            $table->index('created_at', 'idx_operation_records_created_at');
        });

        $this->commentTable('operation_records', '操作日志表');
    }

    private function createSystemConfigGroupsTable(): void
    {
        Schema::create('system_config_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->string('name', 32);
            $table->unsignedInteger('weight')->default(0);
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('addon_code', 50)->nullable();
            $table->string('intro', 255)->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();

            $table->index(['parent_id', 'status'], 'idx_system_config_groups_parent_status');
            $table->unique(['addon_code', 'name'], 'uniq_system_config_groups_addon_name');
        });

        $this->commentTable('system_config_groups', '系统配置分组表');
    }

    private function createSystemConfigsTable(): void
    {
        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->string('name', 32);
            $table->unsignedBigInteger('system_config_group_id');
            $table->unsignedInteger('weight')->default(0);
            $table->string('type', 20)->default('text');
            $table->string('intro', 255)->nullable();
            $table->text('extra')->nullable();
            $table->text('value')->nullable();
            $table->text('default_val')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();

            $table->index(['system_config_group_id', 'weight'], 'idx_system_configs_group_weight');
            $table->unique(['system_config_group_id', 'name'], 'uniq_system_configs_group_name');
        });

        $this->commentTable('system_configs', '系统配置项表');
    }

    private function commentTable(string $table, string $comment): void
    {
        if ('mysql' !== DB::getDriverName()) {
            return;
        }

        DB::statement('ALTER TABLE `'.get_table_name($table).'` COMMENT = "'.$comment.'"');
    }
};
