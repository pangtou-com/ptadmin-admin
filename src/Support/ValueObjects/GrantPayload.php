<?php

declare(strict_types=1);

namespace PTAdmin\Support\ValueObjects;

use Carbon\CarbonInterface;
use PTAdmin\Support\Enums\GrantEffect;

final class GrantPayload
{
    public string $resourceCode;
    public string $effect;
    public array $abilities;
    public ?string $scopeType;
    public $scopeValue;
    public ?array $conditions;
    public int $priority;
    public ?int $expiresAt;

    public function __construct(
        string $resourceCode,
        string $effect = GrantEffect::ALLOW,
        array $abilities = [],
        ?string $scopeType = null,
        $scopeValue = null,
        ?array $conditions = null,
        int $priority = 0,
        ?int $expiresAt = null
    ) {
        $this->resourceCode = $resourceCode;
        $this->effect = $effect;
        $this->abilities = array_values(array_unique($abilities));
        $this->scopeType = $scopeType;
        $this->scopeValue = $scopeValue;
        $this->conditions = $conditions;
        $this->priority = $priority;
        $this->expiresAt = $expiresAt;
    }

    public static function fromArray(array $data): self
    {
        $expiresAt = $data['expiresAt'] ?? $data['expires_at'] ?? null;

        if ($expiresAt instanceof CarbonInterface) {
            $expiresAt = $expiresAt->getTimestamp();
        }

        return new self(
            (string) ($data['resourceCode'] ?? $data['resource_code'] ?? ''),
            (string) ($data['effect'] ?? GrantEffect::ALLOW),
            (array) ($data['abilities'] ?? []),
            isset($data['scopeType']) ? (string) $data['scopeType'] : (isset($data['scope_type']) ? (string) $data['scope_type'] : null),
            $data['scopeValue'] ?? $data['scope_value'] ?? null,
            isset($data['conditions']) ? (array) $data['conditions'] : (isset($data['conditions_json']) ? (array) $data['conditions_json'] : null),
            (int) ($data['priority'] ?? 0),
            null !== $expiresAt ? (int) $expiresAt : null
        );
    }
}
