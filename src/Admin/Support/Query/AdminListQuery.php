<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Support\Query;

use Illuminate\Http\Request;

final class AdminListQuery
{
    /**
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request, bool $paginate = true): array
    {
        return self::fromArray(array_merge($request->query(), $request->all()), $paginate);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function fromArray(array $input, bool $paginate = true): array
    {
        $query = [
            'filters' => self::normalizeItems($input['filters'] ?? []),
            'sorts' => self::normalizeItems($input['sorts'] ?? []),
        ];

        $keyword = self::normalizeScalar($input['keyword'] ?? null);
        if (null !== $keyword && '' !== $keyword) {
            $query['keyword'] = $keyword;
        }

        $keywordFields = self::normalizeKeywordFields($input['keyword_fields'] ?? []);
        if ([] !== $keywordFields) {
            $query['keyword_fields'] = $keywordFields;
        }

        $limit = self::normalizePositiveInt($input['limit'] ?? null);
        if (null !== $limit) {
            $query['limit'] = $limit;
        }

        $page = self::normalizePositiveInt($input['page'] ?? null);
        if (null !== $page) {
            $query['page'] = $page;
        }

        $query['paginate'] = array_key_exists('paginate', $input)
            ? self::normalizeBool($input['paginate'])
            : $paginate
        ;

        return $query;
    }

    /**
     * @param mixed $value
     *
     * @return array<int, mixed>
     */
    private static function normalizeItems($value): array
    {
        $value = self::decodeMaybeJson($value);

        return is_array($value) ? array_values($value) : [];
    }

    /**
     * @param mixed $value
     *
     * @return string[]
     */
    private static function normalizeKeywordFields($value): array
    {
        $value = self::decodeMaybeJson($value);
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)), static function (string $field): bool {
                return '' !== $field;
            });
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($field): ?string {
            if (!is_scalar($field)) {
                return null;
            }

            $field = trim((string) $field);

            return '' === $field ? null : $field;
        }, $value)));
    }

    /**
     * @param mixed $value
     */
    private static function normalizePositiveInt($value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return max((int) $value, 1);
    }

    /**
     * @param mixed $value
     */
    private static function normalizeBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) ((int) $value);
        }

        $value = strtolower(trim((string) $value));

        return !in_array($value, ['', '0', 'false', 'off', 'no'], true);
    }

    /**
     * @param mixed $value
     */
    private static function normalizeScalar($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private static function decodeMaybeJson($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ('' === $trimmed) {
            return [];
        }

        if (!in_array($trimmed[0], ['[', '{'], true)) {
            return $value;
        }

        $decoded = json_decode($trimmed, true);

        return JSON_ERROR_NONE === json_last_error() ? $decoded : [];
    }
}
