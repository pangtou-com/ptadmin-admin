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
        Schema::create('admin_dashboard_role_widgets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('widget_code', 150);
            $table->unsignedTinyInteger('enabled')->default(1);
            $table->integer('sort')->default(0);
            $table->text('layout_json')->nullable();
            $table->text('config_json')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->unique(['tenant_id', 'role_id', 'widget_code'], 'uniq_admin_dashboard_role_widgets');
            $table->index('role_id', 'idx_admin_dashboard_role_widgets_role_id');
            $table->index('widget_code', 'idx_admin_dashboard_role_widgets_widget_code');
        });

        $this->commentTable('admin_dashboard_role_widgets', '角色默认仪表盘组件表');
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_dashboard_role_widgets');
    }

    private function commentTable(string $table, string $comment): void
    {
        if ('mysql' !== DB::getDriverName()) {
            return;
        }

        DB::statement('ALTER TABLE `'.get_table_name($table).'` COMMENT = "'.$comment.'"');
    }
};
