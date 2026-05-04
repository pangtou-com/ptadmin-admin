<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Support\Query;

final class BuilderQueryApplier
{
    /**
     * @param mixed $builder
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     */
    public function apply($builder, array $query = [], array $options = []): void
    {
        $allowedFilters = $this->normalizeFieldSet($options['allowed_filters'] ?? null);
        $allowedSorts = $this->normalizeFieldSet($options['allowed_sorts'] ?? null);

        foreach ((array) ($query['filters'] ?? []) as $filter) {
            if (!is_array($filter) || !isset($filter['field'])) {
                continue;
            }

            $field = (string) $filter['field'];
            if (!$this->isAllowedField($field, $allowedFilters)) {
                continue;
            }

            $this->applyFilter($builder, $field, $this->normalizeOperator((string) ($filter['operator'] ?? '=')), $filter['value'] ?? null);
        }

        $this->applyKeyword($builder, $query, $options);
        $this->applySorts($builder, $query, $options, $allowedSorts);
        $this->applyLimit($builder, $query);
    }

    /**
     * @param mixed $builder
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     *
     * @return mixed
     */
    public function fetch($builder, array $query = [], array $options = [])
    {
        $this->apply($builder, $query, $options);

        $paginate = array_key_exists('paginate', $query) && $this->normalizeBool($query['paginate']);
        if ($paginate) {
            $perPage = $this->normalizePositiveInt($query['limit'] ?? null) ?? (int) ($options['default_limit'] ?? 15);
            $page = $this->normalizePositiveInt($query['page'] ?? null) ?? 1;

            return $builder->paginate($perPage, ['*'], 'page', $page);
        }

        return $builder->get();
    }

    /**
     * @param mixed $builder
     * @param mixed $value
     */
    private function applyFilter($builder, string $field, string $operator, $value): void
    {
        switch ($operator) {
            case '=':
            case '!=':
            case '<>':
            case '>':
            case '>=':
            case '<':
            case '<=':
                $builder->where($field, $operator, $value);

                return;

            case 'like':
                $value = (string) $value;
                if ('' !== $value && false === strpos($value, '%') && false === strpos($value, '_')) {
                    $value = '%'.$value.'%';
                }

                $builder->where($field, 'like', $value);

                return;

            case 'in':
                if (is_array($value)) {
                    $builder->whereIn($field, $value);
                }

                return;

            case 'between':
                if (is_array($value) && 2 === count($value)) {
                    $builder->whereBetween($field, array_values($value));
                }

                return;

            case 'null':
                $builder->whereNull($field);

                return;

            case 'not_null':
                $builder->whereNotNull($field);

                return;
        }
    }

    private function normalizeOperator(string $operator): string
    {
        $operator = strtolower(trim($operator));

        switch ($operator) {
            case 'eq':
                return '=';

            case 'neq':
                return '!=';

            case 'gt':
                return '>';

            case 'gte':
                return '>=';

            case 'lt':
                return '<';

            case 'lte':
                return '<=';

            default:
                return $operator;
        }
    }

    /**
     * @param mixed $builder
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     */
    private function applyKeyword($builder, array $query, array $options): void
    {
        $keyword = trim((string) ($query['keyword'] ?? ''));
        if ('' === $keyword) {
            return;
        }

        $fields = $query['keyword_fields'] ?? ($options['keyword_fields'] ?? []);
        $allowedKeywordFields = $this->normalizeFieldSet($options['allowed_keyword_fields'] ?? null);
        $fields = array_values(array_filter((array) $fields, function ($field) use ($allowedKeywordFields): bool {
            return is_string($field) && $this->isAllowedField($field, $allowedKeywordFields);
        }));

        if ([] === $fields) {
            return;
        }

        $builder->where(function ($queryBuilder) use ($fields, $keyword): void {
            foreach ($fields as $index => $field) {
                $method = 0 === $index ? 'where' : 'orWhere';
                $queryBuilder->{$method}($field, 'like', '%'.$keyword.'%');
            }
        });
    }

    /**
     * @param mixed $builder
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     * @param array<string, true>|null $allowedSorts
     */
    private function applySorts($builder, array $query, array $options, ?array $allowedSorts): void
    {
        $applied = false;
        foreach ((array) ($query['sorts'] ?? []) as $sort) {
            if (!is_array($sort) || !isset($sort['field'])) {
                continue;
            }

            $field = (string) $sort['field'];
            if (!$this->isAllowedField($field, $allowedSorts)) {
                continue;
            }

            $builder->orderBy($field, 'desc' === strtolower((string) ($sort['direction'] ?? 'asc')) ? 'desc' : 'asc');
            $applied = true;
        }

        if ($applied) {
            return;
        }

        foreach ($this->normalizeDefaultOrder($options['default_order'] ?? null, $allowedSorts) as $field => $direction) {
            $builder->orderBy($field, $direction);
        }
    }

    /**
     * @param mixed $builder
     * @param array<string, mixed> $query
     */
    private function applyLimit($builder, array $query): void
    {
        $paginate = array_key_exists('paginate', $query) && $this->normalizeBool($query['paginate']);
        if ($paginate) {
            return;
        }

        $limit = $this->normalizePositiveInt($query['limit'] ?? null);
        if (null === $limit) {
            return;
        }

        $page = $this->normalizePositiveInt($query['page'] ?? null) ?? 1;
        if ($page > 1) {
            $builder->forPage($page, $limit);

            return;
        }

        $builder->limit($limit);
    }

    /**
     * @param mixed $defaultOrder
     * @param array<string, true>|null $allowedSorts
     *
     * @return array<string, string>
     */
    private function normalizeDefaultOrder($defaultOrder, ?array $allowedSorts): array
    {
        if (!is_array($defaultOrder)) {
            return [];
        }

        $normalized = [];
        foreach ($defaultOrder as $field => $direction) {
            if (!is_string($field) || !$this->isAllowedField($field, $allowedSorts)) {
                continue;
            }

            $normalized[$field] = 'desc' === strtolower((string) $direction) ? 'desc' : 'asc';
        }

        return $normalized;
    }

    /**
     * @param mixed $fields
     *
     * @return array<string, true>|null
     */
    private function normalizeFieldSet($fields): ?array
    {
        if (!is_array($fields) || [] === $fields) {
            return null;
        }

        $normalized = [];
        foreach ($fields as $field) {
            if (!is_string($field) || !$this->isSafeField($field)) {
                continue;
            }

            $normalized[$field] = true;
        }

        return [] === $normalized ? null : $normalized;
    }

    private function isAllowedField(string $field, ?array $allowedFields): bool
    {
        if (!$this->isSafeField($field)) {
            return false;
        }

        return null === $allowedFields || isset($allowedFields[$field]);
    }

    private function isSafeField(string $field): bool
    {
        return 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $field);
    }

    /**
     * @param mixed $value
     */
    private function normalizePositiveInt($value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return max((int) $value, 1);
    }

    /**
     * @param mixed $value
     */
    private function normalizeBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) ((int) $value);
        }

        return !in_array(strtolower(trim((string) $value)), ['', '0', 'false', 'off', 'no'], true);
    }
}
