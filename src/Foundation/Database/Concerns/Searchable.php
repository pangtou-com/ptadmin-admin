<?php

declare(strict_types=1);

namespace PTAdmin\Foundation\Database\Concerns;

use Illuminate\Support\Carbon;

trait Searchable
{
    protected $search_fields = [];
    protected $search_scene = [];
    protected $search_ignore = [];
    protected $operator_array = ['in', 'not in', 'between', 'not between'];

    public function scopeSearch($query, array $fields = [], array $data = []): \Illuminate\Database\Eloquent\Builder
    {
        $fields = \count($fields) > 0 ? $fields : $this->search_fields;
        if (0 === \count($fields)) {
            return $query;
        }
        $data = $this->buildSearchData($fields, $data);

        foreach ($data as $value) {
            if (isset($value['fields']) && \is_array($value['fields'])) {
                $this->buildOrWhere($query, $value);

                continue;
            }
            $this->buildWhere($query, $value['field'], $value);
        }

        return $query;
    }

    public function scopeSearchScene($query, string $scene, array $data = [])
    {
        if (!isset($this->search_scene[$scene])) {
            return $query;
        }
        $data = $this->buildSearchData($this->search_scene[$scene], $data);
        foreach ($data as $field => $value) {
            if (isset($value['field']) && \is_array($value['field'])) {
                $this->buildOrWhere($query, $value);

                continue;
            }
            $this->buildWhere($query, $field, $value);
        }

        return $query;
    }

    public static function searchScene(string $scene, array $data = [])
    {
        return self::query()->searchScene($scene, $data);
    }

    public static function search(array $fields = [], array $data = []): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()->search($fields, $data);
    }

    protected function buildSearchData(array $fields = [], array $data = []): array
    {
        if (0 === \count($fields)) {
            return [];
        }
        if (0 === \count($data)) {
            $data = request()->all();
        }
        $results = [];

        foreach ($fields as $key => $field) {
            $table_field = $this->getSearchTableField($key, $field);
            if (null === $table_field) {
                continue;
            }

            $val = $this->buildSearchParams(is_numeric($key) ? $table_field : $key, $field, $data);
            if (null === $val) {
                continue;
            }
            $val['fields'] = isset($field['fields']) && \is_array($field['fields']) ? $field['fields'] : $table_field;
            $val['field'] = $table_field;
            $results[] = $val;
        }

        return $results;
    }

    protected function getSearchTableField($key, $field): ?string
    {
        if (\is_array($field) && isset($field['field']) && \is_string($field['field'])) {
            return $field['field'];
        }
        if (\is_string($field) && is_numeric($key)) {
            return $field;
        }
        if (\is_string($key)) {
            return $key;
        }

        return null;
    }

    protected function getQueryField($tableField, $field)
    {
        if (\is_array($field) && isset($field['query_field'])) {
            return $field['query_field'];
        }

        return $tableField;
    }

    protected function buildSearchParams($key, $field, array $data): ?array
    {
        $query_field = $this->getQueryField($key, $field);
        $value = $data[$query_field] ?? null;
        if (blank($value)) {
            return null;
        }

        [$val, $op] = $this->getFilterValue($value, $field);
        if (null === $val) {
            return null;
        }

        return ['value' => $val, 'op' => $op];
    }

    protected function getFilterValue($value, $field): array
    {
        $op = '=';
        if (\is_array($field) && isset($field['op'])) {
            $op = $field['op'];
        } elseif (\is_string($field) && \in_array($field, $this->getOperator(), true)) {
            $op = $field;
        }
        if (\is_array($field) && isset($field['filter']) && \is_callable($field['filter'])) {
            $value = $field['filter']($value);
        }

        return [$value, $op];
    }

    protected function buildWhere(&$query, $field, $data): void
    {
        if (!isset($data['value']) || \in_array($field, $this->search_ignore, true)) {
            return;
        }
        if (!\is_array($data['value']) && \in_array($data['op'], ['in', 'not in'], true)) {
            $data['value'] = explode(',', $data['value']);
        }
        if (\in_array($data['op'], ['between', 'not between'], true)) {
            $data['value'] = explode(',', $data['value']);
            if (2 !== \count($data['value'])) {
                return;
            }
            foreach ($data['value'] as $k => $v) {
                if (blank($v)) {
                    return;
                }
                if (\is_numeric($v) && mb_strlen((string) $v) > 9) {
                    $data['value'][$k] = Carbon::createFromTimestamp($v)->toDateTimeString();
                }
            }
        }
        switch ($data['op']) {
            case 'in':
                $query->whereIn($field, $data['value']);
                break;
            case 'not in':
                $query->whereNotIn($field, $data['value']);
                break;
            case 'between':
                $query->whereBetween($field, $data['value']);
                break;
            case 'not between':
                $query->whereNotBetween($field, $data['value']);
                break;
            case 'like':
                $query->where($field, 'like', '%'.$data['value'].'%');
                break;
            default:
                $query->where($field, $data['op'], $data['value']);
        }
    }

    protected function buildOrWhere(&$query, $data): void
    {
        if (!isset($data['fields']) || !\is_array($data['fields']) || 0 === \count($data['fields'])) {
            return;
        }
        $query->where(function ($query) use ($data): void {
            foreach ($data['fields'] as $field) {
                if ('like' === $data['op']) {
                    $query->orWhere($field, 'like', '%'.$data['value'].'%');

                    continue;
                }
                $query->orWhere($field, $data['op'], $data['value']);
            }
        });
    }

    protected function getOperator(): array
    {
        return array_merge(['=', '!=', '<>', '>', '<', '>=', '<=', 'like'], $this->operator_array);
    }
}
