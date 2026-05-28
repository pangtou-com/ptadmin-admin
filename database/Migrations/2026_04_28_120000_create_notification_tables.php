<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('audience_type', 20)->default('admin')->comment('接收对象类型：admin、user、mixed');
            $table->string('source_type', 20)->default('system')->comment('来源类型：system、addon');
            $table->string('source_code', 100)->default('system')->comment('来源编码');
            $table->string('category', 50)->default('notice')->comment('消息分类');
            $table->string('level', 20)->default('info')->comment('消息等级');
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->string('action_type', 20)->default('none')->comment('动作类型：none、route、url');
            $table->string('action_url', 500)->nullable();
            $table->string('biz_type', 100)->nullable();
            $table->string('biz_id', 100)->nullable();
            $table->string('biz_key', 150)->nullable()->comment('业务幂等键');
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedInteger('expires_at')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->index(['audience_type', 'category'], 'idx_notification_messages_audience_category');
            $table->index(['source_type', 'source_code'], 'idx_notification_messages_source');
            $table->index(['biz_type', 'biz_id'], 'idx_notification_messages_biz');
            $table->unique('biz_key', 'uniq_notification_messages_biz_key');
        });

        setCommentTable('notification_messages', '通知消息主体表');

        Schema::create('notification_receipts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->string('receiver_type', 20)->comment('接收人类型：admin、user');
            $table->unsignedBigInteger('receiver_id');
            $table->unsignedInteger('read_at')->nullable();
            $table->unsignedInteger('archived_at')->nullable();
            $table->unsignedInteger('deleted_at')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->unique(['notification_id', 'receiver_type', 'receiver_id'], 'uniq_notification_receipts_receiver');
            $table->index(['receiver_type', 'receiver_id', 'deleted_at'], 'idx_notification_receipts_receiver');
            $table->index(['receiver_type', 'receiver_id', 'read_at'], 'idx_notification_receipts_unread');
        });

        setCommentTable('notification_receipts', '通知消息接收状态表');

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('notification_id')->nullable();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->string('receiver_type', 20)->nullable();
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->string('channel', 50);
            $table->string('provider', 100)->nullable();
            $table->string('message_id', 150)->nullable();
            $table->string('batch_id', 150)->nullable();
            $table->string('status', 50)->default('pending');
            $table->unsignedInteger('accepted_at')->nullable();
            $table->unsignedInteger('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->json('raw')->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);

            $table->index(['notification_id', 'channel'], 'idx_notification_deliveries_notification_channel');
            $table->index(['receiver_type', 'receiver_id'], 'idx_notification_deliveries_receiver');
            $table->index(['provider', 'message_id'], 'idx_notification_deliveries_provider_message');
            $table->index('status', 'idx_notification_deliveries_status');
        });

        setCommentTable('notification_deliveries', '通知消息外部投递记录表');
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_receipts');
        Schema::dropIfExists('notification_messages');
    }
};
