<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Auth;

interface WorkflowServiceInterface
{
    public function normalizeMetadata(array $payload = [], array $attributes = [], $target = null): array;

    public function hasSignals(array $workflow): bool;

    public function requestedAction(array $workflow, string $ability): string;

    public function assignedToSubject(array $workflow, $subject): bool;
}
