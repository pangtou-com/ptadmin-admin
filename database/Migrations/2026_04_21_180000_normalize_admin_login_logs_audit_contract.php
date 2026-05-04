<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PTAdmin\Admin\Models\AdminLoginLog;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_login_logs')) {
            return;
        }

        $sourceTable = 'admin_login_logs';
        $targetTable = 'admin_login_logs_new';

        Schema::dropIfExists($targetTable);
        $this->createAuditTable($targetTable);

        $hasAdminId = Schema::hasColumn($sourceTable, 'admin_id');
        $hasLoginAccount = Schema::hasColumn($sourceTable, 'login_account');
        $hasLoginAt = Schema::hasColumn($sourceTable, 'login_at');
        $hasLoginIp = Schema::hasColumn($sourceTable, 'login_ip');
        $hasStatus = Schema::hasColumn($sourceTable, 'status');
        $hasReason = Schema::hasColumn($sourceTable, 'reason');
        $hasUserAgent = Schema::hasColumn($sourceTable, 'user_agent');
        $hasCreatedAt = Schema::hasColumn($sourceTable, 'created_at');
        $hasUpdatedAt = Schema::hasColumn($sourceTable, 'updated_at');

        DB::table($sourceTable)
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (
                $targetTable,
                $hasAdminId,
                $hasLoginAccount,
                $hasLoginAt,
                $hasLoginIp,
                $hasStatus,
                $hasReason,
                $hasUserAgent,
                $hasCreatedAt,
                $hasUpdatedAt
            ): void {
                $adminId = $hasAdminId && isset($row->admin_id) ? (int) $row->admin_id : null;
                $status = $this->normalizeStatus($hasStatus ? ($row->status ?? null) : null);
                $loginAt = $hasLoginAt && isset($row->login_at) ? (int) $row->login_at : 0;

                DB::table($targetTable)->insert([
                    'id' => (int) $row->id,
                    'admin_id' => $adminId ?: null,
                    'login_account' => $this->normalizeLoginAccount(
                        $hasLoginAccount ? ($row->login_account ?? null) : null,
                        $adminId
                    ),
                    'login_at' => $loginAt,
                    'login_ip' => $this->normalizeIp($hasLoginIp ? ($row->login_ip ?? null) : null),
                    'status' => $status,
                    'reason' => $this->normalizeReason($hasReason ? ($row->reason ?? null) : null, $status),
                    'user_agent' => $this->normalizeUserAgent($hasUserAgent ? ($row->user_agent ?? null) : null),
                    'created_at' => $hasCreatedAt && isset($row->created_at) ? (int) $row->created_at : $loginAt,
                    'updated_at' => $hasUpdatedAt && isset($row->updated_at) ? (int) $row->updated_at : $loginAt,
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
            $table->string('login_account', 100)->default('');
            $table->unsignedInteger('login_at')->default(0);
            $table->string('login_ip', 45)->nullable();
            $table->string('status', 50)->default(AdminLoginLog::STATUS_FAILED);
            $table->string('reason', 100)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedInteger('created_at')->default(0);
            $table->unsignedInteger('updated_at')->default(0);
            $table->index('admin_id', 'idx_admin_login_logs_new_admin_id');
            $table->index('login_account', 'idx_admin_login_logs_new_login_account');
            $table->index('status', 'idx_admin_login_logs_new_status');
        });
    }

    private function normalizeStatus($value): string
    {
        if (null === $value || '' === (string) $value) {
            return AdminLoginLog::STATUS_FAILED;
        }

        if (is_numeric($value)) {
            return (int) $value === 1 ? AdminLoginLog::STATUS_SUCCESS : AdminLoginLog::STATUS_FAILED;
        }

        $status = trim((string) $value);
        $allowed = [
            AdminLoginLog::STATUS_SUCCESS,
            AdminLoginLog::STATUS_INVALID_CREDENTIALS,
            AdminLoginLog::STATUS_USER_NOT_FOUND,
            AdminLoginLog::STATUS_DISABLED,
            AdminLoginLog::STATUS_CAPTCHA_INVALID,
            AdminLoginLog::STATUS_BLOCKED,
            AdminLoginLog::STATUS_FAILED,
        ];

        return in_array($status, $allowed, true) ? $status : AdminLoginLog::STATUS_FAILED;
    }

    private function normalizeReason($value, string $status): string
    {
        $reason = trim((string) $value);
        if ('' !== $reason) {
            return mb_substr($reason, 0, 100);
        }

        return AdminLoginLog::STATUS_SUCCESS === $status ? 'legacy_success' : 'legacy_failed';
    }

    private function normalizeUserAgent($value): ?string
    {
        $userAgent = trim((string) $value);

        return '' === $userAgent ? null : mb_substr($userAgent, 0, 255);
    }

    private function normalizeLoginAccount($value, ?int $adminId): string
    {
        $loginAccount = trim((string) $value);
        if ('' !== $loginAccount) {
            return mb_substr($loginAccount, 0, 100);
        }

        if (null === $adminId || $adminId <= 0 || !Schema::hasTable('admins')) {
            return '';
        }

        $username = DB::table('admins')->where('id', $adminId)->value('username');

        return mb_substr(trim((string) $username), 0, 100);
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

        return mb_substr(trim((string) $value), 0, 45);
    }
};
