<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255)->default('');
            $table->string('md5', 32)->default('');
            $table->string('mime', 100)->default('');
            $table->string('suffix', 20)->default('');
            $table->string('driver', 50)->default('public');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path', 255)->default('');
            $table->string('groups', 50)->default('default');
            $table->unsignedInteger('quote')->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->unsignedInteger('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
