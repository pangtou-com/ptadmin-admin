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

namespace PTAdmin\Admin\Services\Install\Pipe;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PTAdmin\Admin\Support\Concerns\FormatInstallOutput;

class ConfigEnv
{
    use FormatInstallOutput;

    public function handle($data, \Closure $next): void
    {
        if (!$this->ensureRuntimeDirectories()) {
            return;
        }
        if (!$this->ensureDatabaseExists($data)) {
            return;
        }
        if (!$this->checkDatabaseConnection($data)) {
            return;
        }
        $data = $this->prepareEnvFilePayload($data);
        if (null === $data) {
            return;
        }

        $next($data);
    }

    /**
     * 安装流程会执行多个 artisan 命令，先确保 Laravel 运行时目录存在。
     */
    private function ensureRuntimeDirectories(): bool
    {
        $directories = [
            base_path('bootstrap/cache'),
            storage_path('logs'),
        ];

        foreach ($directories as $directory) {
            try {
                if (!File::isDirectory($directory)) {
                    File::ensureDirectoryExists($directory, 0755, true);
                }
            } catch (\Throwable $throwable) {
                $this->error('运行目录准备失败: '.$throwable->getMessage());

                return false;
            }

            if (!is_writable($directory)) {
                $this->error(sprintf('目录不可写: %s', $directory));

                return false;
            }
        }

        $this->process('运行目录检查完成');

        return true;
    }

    /**
     * 先准备环境配置内容，延后到安装收尾阶段再真正写入 .env，
     * 避免开发态服务因为 .env 变更中断当前安装请求。
     */
    private function prepareEnvFilePayload($data): ?array
    {
        $content = config('ptadmin-install.env_example', []);
        $envPath = base_path('.env');
        $results = [];

        foreach ($content as $key => $value) {
            if (is_numeric($key)) {
                $results[] = '' !== $value ? '# '.$value : '';

                continue;
            }
            $k = strtolower($key);
            if ('APP_KEY' === $key) {
                $value = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
                $results[] = "{$key}={$value}";

                continue;
            }
            if ('APP_SYSTEM_PREFIX' === $key) {
                if (!isset($data[$k]) || blank($data[$k])) {
                    $data[$k] = Str::random(8);
                }
            }
            $value = $data[$k] ?? $value;
            $results[] = "{$key}={$value}";
        }

        try {
            $data['__install_env_path'] = $envPath;
            $data['__install_env_content'] = implode(PHP_EOL, $results).PHP_EOL;
            $this->process('准备配置文件');
        } catch (\Throwable $e) {
            $this->error('准备配置文件失败: '.$e->getMessage());

            return null;
        }

        return $data;
    }

    /**
     * 连接数据库.
     *
     * @param $data
     *
     * @return bool
     */
    private function checkDatabaseConnection($data): bool
    {
        $connection = $data['db_connection'];
        $settings = [
            'driver' => $connection,
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'],
            'prefix' => $data['db_prefix'] ?? '',
        ];
        $database = config('database');
        $database['default'] = $connection;
        $database['prefix'] = $data['db_prefix'] ?? '';
        $database['connections'] = array_merge($database['connections'], [
            $connection => $settings,
        ]);
        config(['database' => $database]);
        $this->process('测试数据库链接');
        app('db')->purge();

        try {
            app('db')->connection()->getPdo();
        } catch (\Exception $e) {
            $this->error('数据库链接失败: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * 数据库创建.
     * TODO  需要在考虑兼容多种数据库的情况，目前只支持mysql.
     *
     * @param $data
     *
     * @return bool
     */
    private function ensureDatabaseExists($data): bool
    {
        $character = config('database.connections.mysql.charset', 'utf8');
        $collation = config('database.connections.mysql.collation', 'utf8_unicode_ci');
        $dsn = "mysql:host={$data['db_host']};charset={$character}";
        $tables = config('ptadmin-install.tables', []);

        try {
            $pdo = new \PDO($dsn, $data['db_username'], $data['db_password']);
            $stmt = $pdo->prepare('SHOW DATABASES LIKE :dbname');
            $stmt->execute([':dbname' => $data['db_database']]);
            // 校验数据库状态，在进行安装的时候需要的是一个空的数据库
            if (false !== $stmt->fetch()) {
                $this->info('数据库已存在，正在检测数据表是否有重复...');
                // 增加数据表判断，查看是否存在冲突的数据表
                $stmt = $pdo->prepare('SELECT table_name from information_schema.tables WHERE  table_schema = :dbname');
                $stmt->execute([':dbname' => $data['db_database']]);
                $isAllow = true;
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if (\in_array($this->tableName($data['db_prefix'], $row['table_name']), $tables, true)) {
                        $this->error("数据库中已存在表【{$row['table_name']}】");
                        $isAllow = false;
                    }
                }
                if (!$isAllow) {
                    $this->error('数据库创建失败,请删除数据库中的数据表或准备空数据库后重新安装');

                    return false;
                }
                $this->process('数据表检测已完成');

                return true;
            }
            $sql = "CREATE DATABASE IF NOT EXISTS `{$data['db_database']}` DEFAULT CHARACTER SET {$character} DEFAULT COLLATE {$collation}";
            $this->process('创建数据库...');
            $pdo->query($sql);
            $pdo = null;
            $this->process('数据库创建完成');
        } catch (\Exception $exception) {
            $this->error('数据库链接失败: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * 取消数据表名称前缀参数.
     *
     * @param $db_prefix
     * @param $tableName
     *
     * @return mixed|string
     */
    private function tableName($db_prefix, $tableName)
    {
        if (Str::startsWith($tableName, $db_prefix)) {
            return Str::replaceFirst($db_prefix, '', $tableName);
        }

        return $tableName;
    }
}
