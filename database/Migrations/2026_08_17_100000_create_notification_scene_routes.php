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
        if (Schema::hasTable('notification_scene_routes')) {
            $this->upgradeLegacyRoutes();
        } else {
            $this->createRoutesTable();
        }
        setCommentTable('notification_scene_routes', '通知场景渠道实例路由表');

        $this->upgradeDeliveries();
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropIndex('idx_notification_deliveries_instance');
            $table->dropColumn(['addon_code', 'provider_group', 'instance_code', 'route_revision', 'strategy']);
        });

        Schema::dropIfExists('notification_scene_routes');
    }

    private function createRoutesTable(): void
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
    }

    private function upgradeLegacyRoutes(): void
    {
        if (!Schema::hasColumn('notification_scene_routes', 'profile_id')) {
            return;
        }

        Schema::table('notification_scene_routes', function (Blueprint $table): void {
            $table->string('addon_code', 100)->nullable()->after('channel');
            $table->string('provider_group', 50)->nullable()->after('addon_code');
            $table->string('provider', 100)->nullable()->after('provider_group');
            $table->string('instance_code', 100)->nullable()->after('provider');
        });

        foreach (DB::table('notification_scene_routes')->get() as $route) {
            $reference = $this->legacyProfileReference((int) $route->profile_id);
            DB::table('notification_scene_routes')->where('id', $route->id)->update($reference);
        }

        Schema::table('notification_scene_routes', function (Blueprint $table): void {
            $table->dropUnique('uniq_notification_scene_route_profile');
            $table->dropColumn('profile_id');
            $table->string('provider_group', 50)->nullable(false)->change();
            $table->string('provider', 100)->nullable(false)->change();
            $table->string('instance_code', 100)->nullable(false)->change();
            $table->unique(
                ['scene_id', 'channel', 'addon_code', 'provider_group', 'provider', 'instance_code'],
                'uniq_notification_scene_route_instance'
            );
        });
    }

    private function upgradeDeliveries(): void
    {
        if (!Schema::hasTable('notification_deliveries')) {
            return;
        }

        $needsInstanceIndex = !Schema::hasColumn('notification_deliveries', 'instance_code');
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            if (!Schema::hasColumn('notification_deliveries', 'addon_code')) {
                $table->string('addon_code', 100)->nullable()->after('provider');
            }
            if (!Schema::hasColumn('notification_deliveries', 'provider_group')) {
                $table->string('provider_group', 50)->nullable()->after('addon_code');
            }
            if (!Schema::hasColumn('notification_deliveries', 'instance_code')) {
                $table->string('instance_code', 100)->nullable()->after('provider_group');
            }
            if (!Schema::hasColumn('notification_deliveries', 'route_revision')) {
                $table->unsignedInteger('route_revision')->nullable()->after('instance_code');
            }
            if (!Schema::hasColumn('notification_deliveries', 'strategy')) {
                $table->string('strategy', 30)->nullable()->after('route_revision');
            }
        });

        if (Schema::hasColumn('notification_deliveries', 'profile_id')) {
            foreach (DB::table('notification_deliveries')->whereNotNull('profile_id')->get() as $delivery) {
                $reference = $this->legacyProfileReference((int) $delivery->profile_id);
                unset($reference['enabled']);
                if (null !== $delivery->provider && '' !== $delivery->provider) {
                    unset($reference['provider']);
                }
                DB::table('notification_deliveries')->where('id', $delivery->id)->update($reference);
            }
        }

        if ($needsInstanceIndex) {
            Schema::table('notification_deliveries', function (Blueprint $table): void {
                $table->index(
                    ['addon_code', 'provider_group', 'provider', 'instance_code'],
                    'idx_notification_deliveries_instance'
                );
            });
        }
    }

    /** @return array<string, mixed> */
    private function legacyProfileReference(int $profileId): array
    {
        $profile = Schema::hasTable('notification_channel_profiles')
            ? DB::table('notification_channel_profiles')->where('id', $profileId)->first()
            : null;
        if (null === $profile) {
            return [
                'addon_code' => null,
                'provider_group' => 'notify',
                'provider' => 'legacy_profile',
                'instance_code' => 'legacy-profile-'.$profileId,
                'enabled' => 0,
            ];
        }

        return [
            'addon_code' => $profile->addon_code ?: null,
            'provider_group' => $profile->group ?: 'notify',
            'provider' => $profile->capability ?: 'legacy_profile',
            'instance_code' => $profile->code ?: 'legacy-profile-'.$profileId,
            'enabled' => (int) $profile->enabled,
        ];
    }
};
