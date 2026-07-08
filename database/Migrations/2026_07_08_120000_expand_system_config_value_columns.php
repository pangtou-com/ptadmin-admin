<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_configs')) {
            return;
        }

        $this->alterValueColumns('text');
    }

    public function down(): void
    {
        if (!Schema::hasTable('system_configs')) {
            return;
        }

        $this->alterValueColumns('string');
    }

    private function alterValueColumns(string $type): void
    {
        $driver = DB::getDriverName();
        if ('sqlite' === $driver) {
            return;
        }

        if (\in_array($driver, ['mysql', 'mariadb'], true)) {
            $columnType = 'text' === $type ? 'TEXT' : 'VARCHAR(500)';
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `value` %s NULL, MODIFY `default_val` %s NULL',
                $this->mysqlIdentifier('system_configs'),
                $columnType,
                $columnType
            ));

            return;
        }

        if ('pgsql' === $driver) {
            $columnType = 'text' === $type ? 'TEXT' : 'VARCHAR(500)';
            DB::statement(sprintf(
                'ALTER TABLE "%s" ALTER COLUMN "value" TYPE %s, ALTER COLUMN "default_val" TYPE %s',
                $this->pgsqlIdentifier('system_configs'),
                $columnType,
                $columnType
            ));
        }
    }

    private function mysqlIdentifier(string $name): string
    {
        return str_replace('`', '``', DB::getTablePrefix().$name);
    }

    private function pgsqlIdentifier(string $name): string
    {
        return str_replace('"', '""', DB::getTablePrefix().$name);
    }
};
