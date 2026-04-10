<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use PTAdmin\Contracts\Auth\WorkflowServiceInterface;
use PTAdmin\Support\Enums\SubjectType;

class WorkflowService implements WorkflowServiceInterface
{
    public function normalizeMetadata(array $payload = [], array $attributes = [], $target = null): array
    {
        $action = $this->extractNullableString(['workflow_action', 'action', 'transition', 'node_action'], $payload, $attributes, $target);
        $allowedActions = $this->extractStringList(['workflow_allowed_actions', 'workflow_actions', 'allowed_actions'], $payload, $attributes, $target);
        $assigneeIds = $this->extractIntList(['workflow_assignee_ids', 'workflow_operator_ids', 'assignee_ids', 'operator_ids'], $payload, $attributes, $target);
        $locked = $this->extractNullableBool(['workflow_locked'], $payload, $attributes, $target);

        return [
            'locked' => $locked ?? false,
            'action' => $action,
            'allowed_actions' => $allowedActions,
            'assignee_ids' => $assigneeIds,
        ];
    }

    public function hasSignals(array $workflow): bool
    {
        return (bool) ($workflow['locked'] ?? false)
            || null !== ($workflow['action'] ?? null)
            || [] !== (array) ($workflow['allowed_actions'] ?? [])
            || [] !== (array) ($workflow['assignee_ids'] ?? []);
    }

    public function requestedAction(array $workflow, string $ability): string
    {
        return null !== ($workflow['action'] ?? null) && '' !== (string) $workflow['action']
            ? (string) $workflow['action']
            : $ability;
    }

    public function assignedToSubject(array $workflow, $subject): bool
    {
        $assigneeIds = (array) ($workflow['assignee_ids'] ?? []);
        if ([] === $assigneeIds) {
            return true;
        }

        $userId = $this->resolveSubjectUserId($subject);

        return null !== $userId && \in_array($userId, $assigneeIds, true);
    }

    private function resolveSubjectUserId($subject): ?int
    {
        if (\is_array($subject) && (($subject['type'] ?? null) === SubjectType::USER) && isset($subject['id'])) {
            return (int) $subject['id'];
        }

        if (\is_numeric($subject)) {
            return (int) $subject;
        }

        if (!\is_object($subject) || !isset($subject->id)) {
            return null;
        }

        return \in_array(class_basename($subject), ['Role', 'AdminRole'], true)
            ? null
            : (int) $subject->id;
    }

    private function extractNullableString(array $keys, ...$sources): ?string
    {
        foreach ($sources as $source) {
            if (\is_object($source)) {
                foreach ($keys as $key) {
                    if (!isset($source->{$key}) || null === $source->{$key} || '' === $source->{$key}) {
                        continue;
                    }

                    return (string) $source->{$key};
                }

                continue;
            }

            if (!\is_array($source)) {
                continue;
            }

            foreach ($keys as $key) {
                if (!array_key_exists($key, $source) || null === $source[$key] || '' === $source[$key]) {
                    continue;
                }

                return (string) $source[$key];
            }
        }

        return null;
    }

    private function extractNullableBool(array $keys, ...$sources): ?bool
    {
        foreach ($sources as $source) {
            $value = $this->extractRawValue($keys, $source);
            if (null === $value || '' === $value) {
                continue;
            }

            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    private function extractStringList(array $keys, ...$sources): array
    {
        $resolved = [];

        foreach ($sources as $source) {
            $value = $this->extractRawValue($keys, $source);
            if (null === $value || '' === $value) {
                continue;
            }

            $values = \is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                if (null === $item || '' === $item) {
                    continue;
                }

                $resolved[] = (string) $item;
            }
        }

        return array_values(array_unique($resolved));
    }

    private function extractIntList(array $keys, ...$sources): array
    {
        $resolved = [];

        foreach ($sources as $source) {
            $value = $this->extractRawValue($keys, $source);
            if (null === $value || '' === $value) {
                continue;
            }

            $values = \is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                if (null === $item || '' === $item) {
                    continue;
                }

                $resolved[] = (int) $item;
            }
        }

        return array_values(array_unique($resolved));
    }

    private function extractRawValue(array $keys, $source)
    {
        if (\is_object($source)) {
            foreach ($keys as $key) {
                if (isset($source->{$key}) && null !== $source->{$key} && '' !== $source->{$key}) {
                    return $source->{$key};
                }
            }

            return null;
        }

        if (!\is_array($source)) {
            return null;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && null !== $source[$key] && '' !== $source[$key]) {
                return $source[$key];
            }
        }

        return null;
    }
}
