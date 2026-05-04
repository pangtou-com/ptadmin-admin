<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_resources') || !Schema::hasColumn('admin_resources', 'ability_hint_json')) {
            return;
        }

        Schema::table('admin_resources', function (Blueprint $table): void {
            $table->dropColumn('ability_hint_json');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_resources') || Schema::hasColumn('admin_resources', 'ability_hint_json')) {
            return;
        }

        Schema::table('admin_resources', function (Blueprint $table): void {
            $table->json('ability_hint_json')->nullable()->after('icon')->comment('推荐能力');
        });
    }
};
