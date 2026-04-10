<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use PTAdmin\Admin\Models\AdminTenant;
use PTAdmin\Contracts\Auth\AdminTenantServiceInterface;

class AdminTenantService implements AdminTenantServiceInterface
{
    public function create(array $data): AdminTenant
    {
        $tenant = new AdminTenant();
        $tenant->fill($this->normalizePayload($data));
        $tenant->save();

        return $tenant->refresh();
    }

    public function update(int $id, array $data): AdminTenant
    {
        $tenant = AdminTenant::query()->findOrFail($id);
        $tenant->fill($this->normalizePayload($data, false));
        $tenant->save();

        return $tenant->refresh();
    }

    public function delete(int $id): void
    {
        $tenant = AdminTenant::query()->findOrFail($id);
        $tenant->deleted_at = time();
        $tenant->save();
    }

    public function lists(array $filters = []): array
    {
        return AdminTenant::query()
            ->when(isset($filters['status']), function ($query) use ($filters): void {
                $query->where('status', (int) $filters['status']);
            })
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    private function normalizePayload(array $data, bool $withDefaults = true): array
    {
        $payload = [
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'status' => isset($data['status']) ? (int) $data['status'] : ($withDefaults ? 1 : null),
            'settings_json' => $data['settings_json'] ?? null,
        ];

        if (!$withDefaults) {
            return array_filter($payload, static function ($value): bool {
                return null !== $value;
            });
        }

        return $payload;
    }
}
