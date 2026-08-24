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
        Schema::create('admin_dashboard_widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type', 20)->comment('配置主体类型：user 或 role');
            $table->unsignedBigInteger('subject_id')->comment('用户或角色ID');
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('租户ID，空值表示全局配置');
            $table->string('widget_code', 150)->comment('仪表盘组件编码');
            $table->unsignedTinyInteger('enabled')->default(1);
            $table->integer('sort')->default(0);
            $table->text('layout_json')->nullable();
            $table->text('config_json')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->unique(
                ['tenant_id', 'subject_type', 'subject_id', 'widget_code'],
                'uniq_admin_dashboard_widgets_subject'
            );
            $table->index(
                ['subject_type', 'subject_id'],
                'idx_admin_dashboard_widgets_subject'
            );
            $table->index('widget_code', 'idx_admin_dashboard_widgets_widget_code');
        });

        setCommentTable('admin_dashboard_widgets', '仪表盘主体组件配置表');

        $this->copyLegacyWidgets('admin_dashboard_role_widgets', 'role_id', 'role');
        $this->copyLegacyWidgets('admin_dashboard_user_widgets', 'user_id', 'user');

        Schema::dropIfExists('admin_dashboard_user_widgets');
        Schema::dropIfExists('admin_dashboard_role_widgets');
    }

    public function down(): void
    {
        $this->createLegacyWidgetTable('admin_dashboard_role_widgets', 'role_id', '角色默认仪表盘组件表');
        $this->createLegacyWidgetTable('admin_dashboard_user_widgets', 'user_id', '用户仪表盘组件表');

        $this->restoreLegacyWidgets('admin_dashboard_role_widgets', 'role_id', 'role');
        $this->restoreLegacyWidgets('admin_dashboard_user_widgets', 'user_id', 'user');

        Schema::dropIfExists('admin_dashboard_widgets');
    }

    private function copyLegacyWidgets(string $table, string $subjectField, string $subjectType): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->orderBy('id')
            ->chunkById(500, function ($records) use ($subjectField, $subjectType): void {
                $widgets = array();

                foreach ($records as $record) {
                    $widgets[] = [
                        'subject_type' => $subjectType,
                        'subject_id' => (int) $record->{$subjectField},
                        'tenant_id' => null === $record->tenant_id ? null : (int) $record->tenant_id,
                        'widget_code' => (string) $record->widget_code,
                        'enabled' => (int) $record->enabled,
                        'sort' => (int) $record->sort,
                        'layout_json' => $record->layout_json,
                        'config_json' => $record->config_json,
                        'created_at' => (int) $record->created_at,
                        'updated_at' => (int) $record->updated_at,
                    ];
                }

                if ([] !== $widgets) {
                    DB::table('admin_dashboard_widgets')->insert($widgets);
                }
            });
    }

    private function createLegacyWidgetTable(string $table, string $subjectField, string $comment): void
    {
        Schema::create($table, function (Blueprint $tableBlueprint) use ($table, $subjectField): void {
            $tableBlueprint->id();
            $tableBlueprint->unsignedBigInteger($subjectField);
            $tableBlueprint->unsignedBigInteger('tenant_id')->nullable();
            $tableBlueprint->string('widget_code', 150);
            $tableBlueprint->unsignedTinyInteger('enabled')->default(1);
            $tableBlueprint->integer('sort')->default(0);
            $tableBlueprint->text('layout_json')->nullable();
            $tableBlueprint->text('config_json')->nullable();
            $tableBlueprint->unsignedInteger('created_at')->default(0);
            $tableBlueprint->unsignedInteger('updated_at')->default(0);

            $tableBlueprint->unique(
                ['tenant_id', $subjectField, 'widget_code'],
                'admin_dashboard_role_widgets' === $table
                    ? 'uniq_admin_dashboard_role_widgets'
                    : 'uniq_admin_dashboard_user_widgets'
            );
            $tableBlueprint->index(
                $subjectField,
                'admin_dashboard_role_widgets' === $table
                    ? 'idx_admin_dashboard_role_widgets_role_id'
                    : 'idx_admin_dashboard_user_widgets_user_id'
            );
            $tableBlueprint->index(
                'widget_code',
                'admin_dashboard_role_widgets' === $table
                    ? 'idx_admin_dashboard_role_widgets_widget_code'
                    : 'idx_admin_dashboard_user_widgets_widget_code'
            );
        });

        setCommentTable($table, $comment);
    }

    private function restoreLegacyWidgets(string $table, string $subjectField, string $subjectType): void
    {
        if (!Schema::hasTable('admin_dashboard_widgets')) {
            return;
        }

        DB::table('admin_dashboard_widgets')
            ->where('subject_type', $subjectType)
            ->orderBy('id')
            ->chunkById(500, function ($records) use ($table, $subjectField): void {
                $widgets = array();

                foreach ($records as $record) {
                    $widgets[] = [
                        $subjectField => (int) $record->subject_id,
                        'tenant_id' => null === $record->tenant_id ? null : (int) $record->tenant_id,
                        'widget_code' => (string) $record->widget_code,
                        'enabled' => (int) $record->enabled,
                        'sort' => (int) $record->sort,
                        'layout_json' => $record->layout_json,
                        'config_json' => $record->config_json,
                        'created_at' => (int) $record->created_at,
                        'updated_at' => (int) $record->updated_at,
                    ];
                }

                if ([] !== $widgets) {
                    DB::table($table)->insert($widgets);
                }
            });
    }
};
