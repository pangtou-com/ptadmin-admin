<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class NotificationTemplateRenderer
{
    /**
     * @return array<int, string>
     */
    public function placeholders(?string $template): array
    {
        if (null === $template || '' === $template) {
            return [];
        }

        preg_match_all('/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/', $template, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public function render(?string $template, array $variables, string $format = NotificationTemplateFormat::TEXT): ?string
    {
        if (null === $template) {
            return null;
        }

        $rendered = preg_replace_callback(
            '/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/',
            function (array $matches) use ($variables, $format): string {
                $value = $this->stringify($variables[$matches[1]] ?? null);

                return NotificationTemplateFormat::HTML === $format
                    ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    : $value;
            },
            $template
        );

        if (null === $rendered) {
            throw new \RuntimeException('通知模板渲染失败');
        }
        if (NotificationTemplateFormat::JSON === $format) {
            json_decode($rendered, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \RuntimeException('通知 JSON 模板渲染结果无效：'.json_last_error_msg());
            }
        }

        return $rendered;
    }

    /**
     * @param mixed $value
     */
    private function stringify($value): string
    {
        if (null === $value) {
            return '';
        }
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $encoded) {
            throw new \RuntimeException('通知模板变量无法转换为字符串：'.json_last_error_msg());
        }

        return $encoded;
    }
}
