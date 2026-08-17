<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

final class ResolvedNotification
{
    /** @var array<string, mixed> */
    private $message;

    /** @var array<int, array<string, mixed>> */
    private $channels;

    public function __construct(array $message, array $channels)
    {
        $this->message = $message;
        $this->channels = $channels;
    }

    public function message(): array
    {
        return $this->message;
    }

    public function channels(): array
    {
        return $this->channels;
    }
}
