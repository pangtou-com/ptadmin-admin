<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_scenes', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 20)->default('addon')->comment('来源类型：system、addon');
            $table->string('source_code', 100)->comment('来源编码');
            $table->string('group_code', 50)->default('general')->comment('业务分组编码');
            $table->string('group_title', 100)->default('常规通知')->comment('业务分组名称');
            $table->string('code', 100)->comment('场景编码');
            $table->string('title', 255);
            $table->string('description', 500)->nullable();
            $table->string('purpose', 30)->default('transactional');
            $table->json('variables')->nullable();
            $table->json('default_channels')->nullable();
            $table->unsignedTinyInteger('enabled')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->unique('code', 'uniq_notification_scenes_code');
            $table->index(['source_type', 'source_code'], 'idx_notification_scenes_source');
        });

        setCommentTable('notification_scenes', '通知场景定义表');

        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scene_id');
            $table->string('channel', 50);
            $table->string('locale', 20)->default('zh-CN');
            $table->string('mode', 20);
            $table->json('config')->nullable();
            $table->unsignedTinyInteger('customized')->default(0);
            $table->unsignedTinyInteger('enabled')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->unique(['scene_id', 'channel', 'locale'], 'uniq_notification_templates_scene_channel_locale');
            $table->index(['channel', 'enabled'], 'idx_notification_templates_channel_enabled');
        });

        setCommentTable('notification_templates', '通知渠道模板表');
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_scenes');
    }
};
