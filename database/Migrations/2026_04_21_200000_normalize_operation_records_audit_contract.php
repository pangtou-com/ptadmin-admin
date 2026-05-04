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
        if (!Schema::hasTable('operation_records')) {
            return;
        }

        $sourceTable = 'operation_records';
        $targetTable = 'operation_records_new';

        Schema::dropIfExists($targetTable);
        $this->createAuditTable($targetTable);

        $hasAdminId = Schema::hasColumn($sourceTable, 'admin_id');
        $hasAdminUsername = Schema::hasColumn($sourceTable, 'admin_username');
        $hasNickname = Schema::hasColumn($sourceTable, 'nickname');
        $hasIp = Schema::hasColumn($sourceTable, 'ip');
        $hasUserAgent = Schema::hasColumn($sourceTable, 'user_agent');
        $hasUrl = Schema::hasColumn($sourceTable, 'url');
        $hasTitle = Schema::hasColumn($sourceTable, 'title');
        $hasResourceName = Schema::hasColumn($sourceTable, 'resource_name');
        $hasMethod = Schema::hasColumn($sourceTable, 'method');
        $hasController = Schema::hasColumn($sourceTable, 'controller');
        $hasAction = Schema::hasColumn($sourceTable, 'action');
        $hasTraceId = Schema::hasColumn($sourceTable, 'trace_id');
        $hasTargetType = Schema::hasColumn($sourceTable, 'target_type');
        $hasTargetId = Schema::hasColumn($sourceTable, 'target_id');
        $hasStatus = Schema::hasColumn($sourceTable, 'status');
        $hasRequest = Schema::hasColumn($sourceTable, 'request');
        $hasErrorMessage = Schema::hasColumn($sourceTable, 'error_message');
        $hasResponseCode = Schema::hasColumn($sourceTable, 'response_code');
        $hasResponseTime = Schema::hasColumn($sourceTable, 'response_time');
        $hasCreatedAt = Schema::hasColumn($sourceTable, 'created_at');
        $hasUpdatedAt = Schema::hasColumn($sourceTable, 'updated_at');

        DB::table($sourceTable)
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (
                $targetTable,
                $hasAdminId,
                $hasAdminUsername,
                $hasNickname,
                $hasIp,
                $hasUserAgent,
                $hasUrl,
                $hasTitle,
                $hasResourceName,
                $hasMethod,
                $hasController,
                $hasAction,
                $hasTraceId,
                $hasTargetType,
                $hasTargetId,
                $hasStatus,
                $hasRequest,
                $hasErrorMessage,
                $hasResponseCode,
                $hasResponseTime,
                $hasCreatedAt,
                $hasUpdatedAt
            ): void {
                $adminId = $hasAdminId && isset($row->admin_id) ? (int) $row->admin_id : null;
                $responseCode = $hasResponseCode && isset($row->response_code) ? (int) $row->response_code : 200;
                $status = $this->normalizeStatus($hasStatus ? ($row->status ?? null) : null, $responseCode);

                DB::table($targetTable)->insert([
                    'id' => (int) $row->id,
                    'admin_id' => $adminId ?: null,
                    'admin_username' => $this->normalizeAdminUsername(
                        $hasAdminUsername ? ($row->admin_username ?? null) : null,
                        $adminId
                    ),
                    'nickname' => $this->normalizeString($hasNickname ? ($row->nickname ?? null) : null, 50) ?? '',
                    'ip' => $this->normalizeIp($hasIp ? ($row->ip ?? null) : null),
                    'user_agent' => $this->normalizeString($hasUserAgent ? ($row->user_agent ?? null) : null, 255),
                    'url' => $this->normalizeString($hasUrl ? ($row->url ?? null) : null, 255) ?? '',
                    'title' => $this->normalizeString($hasTitle ? ($row->title ?? null) : null, 255) ?? '',
                    'resource_name' => $this->normalizeString($hasResourceName ? ($row->resource_name ?? null) : null, 150),
                    'method' => $this->normalizeString($hasMethod ? ($row->method ?? null) : null, 20) ?? '',
                    'controller' => $this->normalizeString($hasController ? ($row->controller ?? null) : null, 255) ?? '',
                    'action' => $this->normalizeString($hasAction ? ($row->action ?? null) : null, 100) ?? '',
                    'trace_id' => $this->normalizeString($hasTraceId ? ($row->trace_id ?? null) : null, 100),
                    'target_type' => $this->normalizeString($hasTargetType ? ($row->target_type ?? null) : null, 100),
                    'target_id' => $this->normalizeString($hasTargetId ? ($row->target_id ?? null) : null, 150),
                    'status' => $status,
                    'request' => $this->normalizeText($hasRequest ? ($row->request ?? null) : null),
                    'error_message' => $this->normalizeErrorMessage($hasErrorMessage ? ($row->error_message ?? null) : null, $responseCode),
                    'response_code' => $responseCode,
                    'response_time' => $hasResponseTime && isset($row->response_time) ? (float) $row->response_time : 0,
                    'created_at' => $hasCreatedAt && isset($row->created_at) ? (int) $row->created_at : time(),
                    'updated_at' => $hasUpdatedAt && isset($row->updated_at) ? (int) $row->updated_at : time(),
                ]);
            });

        Schema::drop($sourceTable);
        Schema::rename($targetTable, $sourceTable);
    }

    public function down(): void
    {
    }

    private function createAuditTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('admin_username', 100)->default('');
            $table->string('nickname', 50)->default('');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('url', 255)->default('');
            $table->string('title', 255)->default('');
            $table->string('resource_name', 150)->nullable();
            $table->string('method', 20)->default('');
            $table->string('controller', 255)->default('');
            $table->string('action', 100)->default('');
            $table->string('trace_id', 100)->nullable();
            $table->string('target_type', 100)->nullable();
            $table->string('target_id', 150)->nullable();
            $table->string('status', 50)->default('success');
            $table->text('request')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('response_code')->default(200);
            $table->decimal('response_time', 10, 2)->default(0);
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->index('admin_id', 'idx_operation_records_new_admin_id');
            $table->index('admin_username', 'idx_operation_records_new_admin_username');
            $table->index('status', 'idx_operation_records_new_status');
            $table->index('created_at', 'idx_operation_records_new_created_at');
        });
    }

    private function normalizeStatus($value, int $responseCode): string
    {
        $status = trim((string) $value);

        if ('' !== $status && in_array($status, ['success', 'failed'], true)) {
            return $status;
        }

        return $responseCode >= 400 ? 'failed' : 'success';
    }

    private function normalizeAdminUsername($value, ?int $adminId): string
    {
        $username = $this->normalizeString($value, 100);
        if (null !== $username) {
            return $username;
        }

        if (null === $adminId || $adminId <= 0 || !Schema::hasTable('admins')) {
            return '';
        }

        return $this->normalizeString(DB::table('admins')->where('id', $adminId)->value('username'), 100) ?? '';
    }

    private function normalizeIp($value): ?string
    {
        if (null === $value || '' === (string) $value) {
            return null;
        }

        if (is_numeric($value)) {
            $ip = @long2ip((int) $value);

            return false === $ip ? null : $ip;
        }

        return $this->normalizeString($value, 45);
    }

    private function normalizeErrorMessage($value, int $responseCode): ?string
    {
        $message = $this->normalizeText($value);
        if (null !== $message) {
            return $message;
        }

        return $responseCode >= 400 ? 'legacy_failed' : null;
    }

    private function normalizeText($value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    private function normalizeString($value, int $length): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : mb_substr($value, 0, $length);
    }
};
