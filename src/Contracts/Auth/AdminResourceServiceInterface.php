<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

interface AdminResourceServiceInterface
{
    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;

    public function find(int $id);

    public function register(array $definition);

    public function registerBatch(array $definitions): void;

    public function findByName(string $name);

    public function tree(array $filters = []): array;

    public function syncAddonResources(string $addonCode, array $definitions): void;

    public function disableAddonResources(string $addonCode): void;

    public function deleteByAddonCode(string $addonCode): void;
}
