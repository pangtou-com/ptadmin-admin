<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_scene_routes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scene_id');
            $table->string('channel', 50);
            $table->string('addon_code', 100)->nullable();
            $table->string('provider_group', 50);
            $table->string('provider', 100);
            $table->string('instance_code', 100);
            $table->string('dispatch_mode', 20);
            $table->string('strategy', 30)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->unsignedInteger('weight')->default(100);
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedTinyInteger('enabled')->default(1);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->unique(
                ['scene_id', 'channel', 'addon_code', 'provider_group', 'provider', 'instance_code'],
                'uniq_notification_scene_route_instance'
            );
            $table->index(['scene_id', 'channel', 'enabled'], 'idx_notification_scene_routes_scene');
        });
        setCommentTable('notification_scene_routes', '通知场景渠道实例路由表');

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->string('addon_code', 100)->nullable()->after('provider');
            $table->string('provider_group', 50)->nullable()->after('addon_code');
            $table->string('instance_code', 100)->nullable()->after('provider_group');
            $table->unsignedInteger('route_revision')->nullable()->after('instance_code');
            $table->string('strategy', 30)->nullable()->after('route_revision');
            $table->index(
                ['addon_code', 'provider_group', 'provider', 'instance_code'],
                'idx_notification_deliveries_instance'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropIndex('idx_notification_deliveries_instance');
            $table->dropColumn(['addon_code', 'provider_group', 'instance_code', 'route_revision', 'strategy']);
        });

        Schema::dropIfExists('notification_scene_routes');
    }
};
