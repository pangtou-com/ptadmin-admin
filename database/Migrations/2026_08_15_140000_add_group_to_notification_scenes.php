<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_scenes')) {
            return;
        }

        Schema::table('notification_scenes', function (Blueprint $table): void {
            if (!Schema::hasColumn('notification_scenes', 'group_code')) {
                $table->string('group_code', 50)->default('general')->comment('业务分组编码');
            }
            if (!Schema::hasColumn('notification_scenes', 'group_title')) {
                $table->string('group_title', 100)->default('常规通知')->comment('业务分组名称');
            }
        });

        Schema::table('notification_scenes', function (Blueprint $table): void {
            $table->index(
                ['source_type', 'source_code', 'group_code'],
                'idx_notification_scenes_source_group'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_scenes')) {
            return;
        }

        Schema::table('notification_scenes', function (Blueprint $table): void {
            $table->dropIndex('idx_notification_scenes_source_group');
            $table->dropColumn(['group_code', 'group_title']);
        });
    }
};
