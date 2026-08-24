<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class DashboardWidgetActionDefinition
{
    /** @var array<string, mixed> */
    private array $definition;

    public function __construct(string $code, string $label, string $type = 'request')
    {
        $this->definition = array('code' => $code, 'label' => $label, 'type' => $type);
    }

    public function target(string $target): self { $this->definition['target'] = $target; return $this; }
    public function confirm(string $text): self { $this->definition['confirm_text'] = $text; return $this; }
    /** @param array<string, mixed> $schema */
    public function payloadSchema(array $schema): self { $this->definition['payload_schema'] = $schema; return $this; }
    /** @param array<string, mixed> $meta */
    public function meta(array $meta): self { $this->definition['meta'] = $meta; return $this; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_merge(array(
            'target' => '',
            'confirm_text' => '',
            'payload_schema' => array(),
            'meta' => array(),
        ), $this->definition);
    }
}
