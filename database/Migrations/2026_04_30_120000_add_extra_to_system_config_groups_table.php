<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_config_groups') || Schema::hasColumn('system_config_groups', 'extra')) {
            return;
        }

        Schema::table('system_config_groups', function (Blueprint $table): void {
            $table->json('extra')->nullable()->after('intro');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('system_config_groups') || !Schema::hasColumn('system_config_groups', 'extra')) {
            return;
        }

        Schema::table('system_config_groups', function (Blueprint $table): void {
            $table->dropColumn('extra');
        });
    }
};
