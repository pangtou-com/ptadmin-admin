<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;

class AdminFixCommand extends Command
{
    protected $signature = 'admin:fix';

    protected $description = '修复 PTAdmin 运行所需目录和文件权限';

    public function handle(): int
    {
        $paths = config('ptadmin.fix_paths', []);
        if (!\is_array($paths) || [] === $paths) {
            $this->warn('No PTAdmin fix paths configured.');

            return 0;
        }

        $fixed = 0;
        $skipped = 0;
        $failed = [];

        foreach ($paths as $name => $definition) {
            $definition = $this->normalizeDefinition($definition);
            $path = trim((string) ($definition['path'] ?? ''));
            if ('' === $path) {
                ++$skipped;
                continue;
            }

            try {
                $result = $this->fixPath((string) $name, $path, $definition);
                if (null === $result) {
                    ++$skipped;
                    continue;
                }

                $fixed += $result;
            } catch (\Throwable $throwable) {
                $failed[] = sprintf('%s: %s', (string) $name, $throwable->getMessage());
            }
        }

        foreach ($failed as $message) {
            $this->error($message);
        }

        $this->info(sprintf('PTAdmin fix completed. fixed=%d skipped=%d failed=%d', $fixed, $skipped, \count($failed)));

        return [] === $failed ? 0 : 1;
    }

    /**
     * @param mixed $definition
     * @return array<string, mixed>
     */
    private function normalizeDefinition($definition): array
    {
        if (\is_string($definition)) {
            return ['path' => $definition];
        }

        return \is_array($definition) ? $definition : [];
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function fixPath(string $name, string $path, array $definition): ?int
    {
        $type = (string) ($definition['type'] ?? 'directory');
        $create = (bool) ($definition['create'] ?? ('file' !== $type));
        $recursive = (bool) ($definition['recursive'] ?? ('file' !== $type));
        $directoryMode = $this->normalizeMode(
            $definition['directory_mode'] ?? config('ptadmin.fix_directory_mode', '0775'),
            0775
        );
        $fileMode = $this->normalizeMode(
            $definition['file_mode'] ?? config('ptadmin.fix_file_mode', '0664'),
            0664
        );

        if ('file' === $type) {
            if (!is_file($path)) {
                return null;
            }

            return $this->chmodPath($path, $fileMode, $name);
        }

        if (!is_dir($path)) {
            if (!$create) {
                return null;
            }

            if (!@mkdir($path, $directoryMode, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Unable to create directory: %s', $path));
            }
        }

        $targetPath = is_link($path) ? realpath($path) : $path;
        if (!\is_string($targetPath) || '' === $targetPath) {
            throw new \RuntimeException(sprintf('Unable to resolve path: %s', $path));
        }

        return $this->chmodDirectory($targetPath, $directoryMode, $fileMode, $recursive, $name);
    }

    private function normalizeMode($mode, int $default): int
    {
        if (\is_int($mode)) {
            return $mode;
        }

        $mode = trim((string) $mode);
        if (preg_match('/^0?[0-7]{3,4}$/', $mode)) {
            return octdec($mode);
        }

        return $default;
    }

    private function chmodDirectory(
        string $path,
        int $directoryMode,
        int $fileMode,
        bool $recursive,
        string $name
    ): int {
        $fixed = $this->chmodPath($path, $directoryMode, $name);
        if (!$recursive) {
            return $fixed;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                continue;
            }

            $fixed += $this->chmodPath(
                $item->getPathname(),
                $item->isDir() ? $directoryMode : $fileMode,
                $name
            );
        }

        return $fixed;
    }

    private function chmodPath(string $path, int $mode, string $name): int
    {
        if (@chmod($path, $mode)) {
            return 1;
        }

        throw new \RuntimeException(sprintf('Unable to chmod %s path: %s', $name, $path));
    }
}
