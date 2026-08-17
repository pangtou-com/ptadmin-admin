<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class NotificationSendRequest implements Arrayable, JsonSerializable
{
    /** @var string */
    private $scene;

    /** @var NotificationRecipients */
    private $recipients;

    /** @var array<string, mixed> */
    private $variables;

    /** @var array<int, string> */
    private $channels;

    /** @var array<string, mixed> */
    private $options;

    private function __construct(string $scene, NotificationRecipients $recipients, array $variables, array $channels, array $options)
    {
        $scene = trim($scene);
        if ('' === $scene || mb_strlen($scene) > 100) {
            throw new \InvalidArgumentException('通知场景编码不能为空且不能超过 100 个字符');
        }
        $this->scene = $scene;
        $this->recipients = $recipients;
        $this->variables = $variables;
        $this->channels = self::normalizeChannels($channels);
        $this->options = $options;
    }

    public static function make(string $scene, NotificationRecipients $recipients, array $variables = [], array $channels = [], array $options = []): self
    {
        return new self($scene, $recipients, $variables, $channels, $options);
    }

    public function scene(): string
    {
        return $this->scene;
    }

    public function recipients(): NotificationRecipients
    {
        return $this->recipients;
    }

    public function variables(): array
    {
        return $this->variables;
    }

    public function channels(): array
    {
        return $this->channels;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'scene' => $this->scene,
            'recipients' => $this->recipients->toArray(),
            'variables' => $this->variables,
            'channels' => $this->channels,
            'options' => $this->options,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function normalizeChannels(array $channels): array
    {
        $normalized = [];
        foreach ($channels as $channel) {
            $channel = NotificationChannel::normalize((string) $channel);
            $normalized[$channel] = $channel;
        }

        return array_values($normalized);
    }
}
