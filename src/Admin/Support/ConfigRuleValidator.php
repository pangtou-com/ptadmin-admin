<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Support;

final class ConfigRuleValidator
{
    /**
     * @return array<int, string>
     */
    public static function allowedFieldTypes(): array
    {
        return ['text', 'textarea', 'switch', 'radio', 'checkbox', 'select', 'json', 'password'];
    }

    public static function isValidFieldType(string $type): bool
    {
        return \in_array(strtolower(trim($type)), self::allowedFieldTypes(), true);
    }

    public static function isValidGroupLayoutMode(string $mode): bool
    {
        return \in_array(strtolower(trim($mode)), ['tab', 'block'], true);
    }

    public static function isValidExposeMode(string $expose): bool
    {
        return \in_array(strtolower(trim($expose)), ['public', 'protected', 'private'], true);
    }

    /**
     * @return array<int, string>
     */
    public static function supportedFieldMetaKeys(): array
    {
        return [
            'placeholder',
            'help',
            'rows',
            'style',
            'expose',
            'required',
            'min',
            'max',
            'pattern',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function validatedFieldMetaKeys(): array
    {
        return ['expose', 'required', 'min', 'max', 'pattern'];
    }

    /**
     * @return array<int, string>
     */
    public static function passthroughFieldMetaKeys(): array
    {
        return ['placeholder', 'help', 'rows', 'style'];
    }

    /**
     * @return array<int, string>
     */
    public static function supportedMetaKeysForFieldType(string $type): array
    {
        return match (strtolower(trim($type))) {
            'text', 'textarea', 'password' => ['placeholder', 'help', 'rows', 'style', 'expose', 'required', 'min', 'max', 'pattern'],
            'switch' => ['help', 'style', 'expose'],
            'radio', 'select' => ['help', 'style', 'expose'],
            'checkbox' => ['help', 'style', 'expose'],
            'json' => ['placeholder', 'help', 'rows', 'style', 'expose'],
            default => self::supportedFieldMetaKeys(),
        };
    }

    public static function supportsMetaKeyForFieldType(string $type, string $key): bool
    {
        return \in_array($key, self::supportedMetaKeysForFieldType($type), true);
    }

    /**
     * @return array<int, string>
     */
    public static function unsupportedMetaKeysForFieldType(string $type, array $meta): array
    {
        $unsupported = [];
        foreach (array_keys($meta) as $key) {
            if (!\is_string($key)) {
                continue;
            }

            $resolvedKey = strtolower(trim($key));
            if ('' === $resolvedKey) {
                continue;
            }

            if (!self::supportsMetaKeyForFieldType($type, $resolvedKey)) {
                $unsupported[] = $resolvedKey;
            }
        }

        return array_values(array_unique($unsupported));
    }

    /**
     * @return array<string, string>
     */
    public static function invalidMetaValueTypes(array $meta): array
    {
        $invalid = [];

        if (array_key_exists('expose', $meta) && !self::isValidExposeMode((string) $meta['expose'])) {
            $invalid['expose'] = 'expose';
        }

        if (array_key_exists('required', $meta) && !self::isBooleanLike($meta['required'])) {
            $invalid['required'] = 'required';
        }

        if (array_key_exists('min', $meta) && !self::isNumericLike($meta['min'])) {
            $invalid['min'] = 'min';
        }

        if (array_key_exists('max', $meta) && !self::isNumericLike($meta['max'])) {
            $invalid['max'] = 'max';
        }

        if (array_key_exists('pattern', $meta) && !\is_string($meta['pattern'])) {
            $invalid['pattern'] = 'pattern';
        }

        return $invalid;
    }

    public static function resolveFieldType(array $field): string
    {
        $type = strtolower(trim((string) ($field['type'] ?? '')));
        if ('' !== $type) {
            return $type;
        }

        return self::mapLegacyComponentToType((string) ($field['component'] ?? ''));
    }

    private static function mapLegacyComponentToType(string $component): string
    {
        return match (strtolower(trim($component))) {
            'switch' => 'switch',
            'textarea' => 'textarea',
            'radio' => 'radio',
            'checkbox' => 'checkbox',
            'select' => 'select',
            'password' => 'password',
            'input', '' => 'text',
            default => 'text',
        };
    }

    /**
     * @param mixed $value
     */
    private static function isBooleanLike($value): bool
    {
        if (\is_bool($value)) {
            return true;
        }

        if (\is_int($value)) {
            return \in_array($value, [0, 1], true);
        }

        if (!\is_string($value)) {
            return false;
        }

        return \in_array(strtolower(trim($value)), ['0', '1', 'true', 'false'], true);
    }

    /**
     * @param mixed $value
     */
    private static function isNumericLike($value): bool
    {
        return \is_int($value) || \is_float($value) || (\is_string($value) && is_numeric(trim($value)));
    }
}
