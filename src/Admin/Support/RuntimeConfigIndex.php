<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Support;

final class RuntimeConfigIndex
{
    private const PLACEHOLDER = '/* __PTADMIN_RUNTIME_CONFIG_SCRIPT__ */';

    public static function prepare(string $html): string
    {
        if (false !== strpos($html, self::PLACEHOLDER)) {
            return $html;
        }

        $prepared = preg_replace(
            '#<script\b[^>]*>(?:(?!</script>).)*__PTADMIN_PTCONFIG_READY__(?:(?!</script>).)*</script>#is',
            self::placeholderBlock(),
            $html,
            1,
            $count
        );
        if (\is_string($prepared) && $count > 0) {
            return $prepared;
        }

        if (false !== strpos($html, '</head>')) {
            return str_replace('</head>', self::placeholderBlock().PHP_EOL.'</head>', $html);
        }

        return $html;
    }

    public static function inject(string $html, string $script): string
    {
        $html = self::prepare($html);
        $script = self::indent(trim($script), 12);
        $placeholder = str_repeat(' ', 12).self::PLACEHOLDER;
        $html = str_replace($placeholder, $script, $html, $count);
        if ($count > 0) {
            return $html;
        }

        return str_replace(self::PLACEHOLDER, trim($script), $html);
    }

    private static function placeholderBlock(): string
    {
        return implode(PHP_EOL, [
            '        <script>',
            '            window.ptconfig = window.ptconfig || {}',
            '            '.self::PLACEHOLDER,
            '            window.__PTADMIN_PTCONFIG_LOADED__ = true',
            '            window.__PTADMIN_PTCONFIG_READY__ = Promise.resolve()',
            '        </script>',
        ]);
    }

    private static function indent(string $script, int $spaces): string
    {
        $prefix = str_repeat(' ', $spaces);

        return $prefix.str_replace("\n", "\n".$prefix, $script);
    }
}
