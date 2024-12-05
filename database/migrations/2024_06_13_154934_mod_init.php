<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModInit extends Migration
{
    public function up(): void
    {
        $table_name = config('easy.table_name');
        if (null === $table_name) {
            return;
        }

        if (isset($table_name['mod'])) {
            Schema::create($table_name['mod'], function (Blueprint $table): void {
                $table->id();
                $table->string('title', 50)->comment('模型表单名称');
                $table->string('table_name', 50)->comment('表名称');
                $table->string('mod_name', 50)->comment('所属类型');
                $table->string('intro', 255)->nullable()->comment('描述信息');
                $table->json('setup')->nullable()->comment('表单构建扩展信息如：style、class等');
                $table->json('extra')->nullable()->comment('扩展信息【部分自定义字段的扩展配置值内容】');
                $table->unsignedTinyInteger('weight')->default(99)->comment('权重');
                $table->unsignedTinyInteger('is_publish')->default(0)->comment('是否发布 0 未发布，1 已发布，在发布状态下无法进行表单配置修改');
                $table->unsignedTinyInteger('status')->default(0)->comment('是否已经使用，0：未使用，1:已使用	');
                $table->unsignedInteger('deleted_at')->nullable()->comment('删除时间');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
        }

        if (isset($table_name['mod_field'])) {
            Schema::create($table_name['mod_field'], function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('mod_id')->default(0);
                $table->string('title', 255)->comment('标题');
                $table->string('subtitle', 255)->nullable()->comment('副标题');
                $table->string('name', 255)->comment('名称');
                $table->string('type', 50)->comment('类型');
                $table->string('default_val', 50)->nullable()->comment('默认值');
                $table->string('tips', 255)->nullable()->comment('描述信息');
                $table->string('intro', 255)->nullable()->comment('描述信息');
                $table->unsignedTinyInteger('is_release')->default(0);
                $table->unsignedTinyInteger('is_search')->default(0);
                $table->unsignedTinyInteger('is_table')->default(0);
                $table->unsignedTinyInteger('is_required')->default(0);
                $table->unsignedTinyInteger('is_system')->default(0)->comment('系统字段，不允许删除和修改');
                $table->json('setup')->nullable()->comment('表单构建扩展信息如：style、class等');
                $table->json('extra')->nullable()->comment('扩展字段信息');
                $table->unsignedTinyInteger('weight')->default(99)->comment('权重');
                $table->unsignedTinyInteger('status')->default(0)->comment('是否已经使用，0：未使用，1:已使用	');
                $table->unsignedInteger('deleted_at')->nullable()->comment('删除时间');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('updated_at')->default(0)->comment('更新时间');
            });
        }

        app('cache')->store('default' !== config('easy.cache.store') ? config('easy.cache.store') : null)->forget(config('easy.cache.key'));
    }

    public function down(): void
    {
        $table_name = config('easy.table_name');
        if (null === $table_name) {
            return;
        }
        if (isset($table_name['mod'])) {
            Schema::dropIfExists($table_name['mod']);
        }
        if (isset($table_name['mod_field'])) {
            Schema::dropIfExists($table_name['mod_field']);
        }
    }
}
