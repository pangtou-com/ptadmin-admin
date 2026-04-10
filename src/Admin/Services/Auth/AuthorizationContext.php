<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use Illuminate\Http\Request;
use PTAdmin\Contracts\Auth\WorkflowServiceInterface;

final class AuthorizationContext
{
    public ?int $tenantId = null;
    public ?int $organizationId = null;
    public ?int $departmentId = null;
    public $target = null;
    public array $payload = [];
    public array $attributes = [];
    public array $workflow = [];

    public static function fromRequest(Request $request): self
    {
        $context = new self();
        $routeParameters = null !== $request->route() ? (array) $request->route()->parameters() : [];
        $context->payload = (array) $request->all();
        $context->attributes = $routeParameters;
        $context->target = self::resolveTarget($routeParameters);
        $context->tenantId = self::extractNullableInt(['tenant_id', 'tenantId'], $context->payload, $context->attributes, $context->target);
        $context->organizationId = self::extractNullableInt(['organization_id', 'organizationId'], $context->payload, $context->attributes, $context->target);
        $context->departmentId = self::extractNullableInt(['department_id', 'departmentId'], $context->payload, $context->attributes, $context->target);
        $context->workflow = self::workflowService()->normalizeMetadata($context->payload, $context->attributes, $context->target);

        return $context;
    }

    public function workflowMetadata(): array
    {
        if ([] === $this->workflow) {
            $this->workflow = self::workflowService()->normalizeMetadata($this->payload, $this->attributes, $this->target);
        }

        return $this->workflow;
    }

    private static function extractNullableInt(array $keys, ...$sources): ?int
    {
        foreach ($sources as $source) {
            if (\is_object($source)) {
                foreach ($keys as $key) {
                    if (!isset($source->{$key}) || null === $source->{$key} || '' === $source->{$key}) {
                        continue;
                    }

                    return (int) $source->{$key};
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

                return (int) $source[$key];
            }
        }

        return null;
    }

    private static function resolveTarget(array $routeParameters)
    {
        foreach ($routeParameters as $value) {
            if (\is_object($value)) {
                return $value;
            }
        }

        return null;
    }

    private static function workflowService(): WorkflowServiceInterface
    {
        return app()->bound(WorkflowServiceInterface::class)
            ? app(WorkflowServiceInterface::class)
            : new WorkflowService();
    }
}
