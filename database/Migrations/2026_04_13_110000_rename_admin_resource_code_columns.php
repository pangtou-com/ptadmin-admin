<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_resources')) {
            return;
        }

        $hasCode = Schema::hasColumn('admin_resources', 'code');
        $hasName = Schema::hasColumn('admin_resources', 'name');
        $hasTitle = Schema::hasColumn('admin_resources', 'title');

        if ($hasCode && $hasName && !$hasTitle) {
            Schema::table('admin_resources', function (Blueprint $table): void {
                $table->renameColumn('name', 'title');
            });

            Schema::table('admin_resources', function (Blueprint $table): void {
                $table->renameColumn('code', 'name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_resources')) {
            return;
        }

        $hasCode = Schema::hasColumn('admin_resources', 'code');
        $hasName = Schema::hasColumn('admin_resources', 'name');
        $hasTitle = Schema::hasColumn('admin_resources', 'title');

        if (!$hasCode && $hasName && $hasTitle) {
            Schema::table('admin_resources', function (Blueprint $table): void {
                $table->renameColumn('name', 'code');
            });

            Schema::table('admin_resources', function (Blueprint $table): void {
                $table->renameColumn('title', 'name');
            });
        }
    }
};
