<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\User;

final class PendingNotification
{
    /** @var NotificationManager */
    private $manager;

    /** @var NotificationRecipients|null */
    private $recipients;

    /** @var array<int, string> */
    private $channels = [];

    public function __construct(NotificationManager $manager)
    {
        $this->manager = $manager;
    }

    public function toAdmin(Admin $admin): self
    {
        return $this->withRecipients(NotificationRecipients::admin($admin));
    }

    public function toAdminId(int $adminId): self
    {
        return $this->withRecipients(NotificationRecipients::adminId($adminId));
    }

    public function toAdmins(iterable $admins): self
    {
        return $this->withRecipients(NotificationRecipients::admins($admins));
    }

    public function toAdminIds(array $adminIds): self
    {
        return $this->withRecipients(NotificationRecipients::adminIds($adminIds));
    }

    public function toUser(User $user): self
    {
        return $this->withRecipients(NotificationRecipients::user($user));
    }

    public function toUserId(int $userId): self
    {
        return $this->withRecipients(NotificationRecipients::userId($userId));
    }

    public function toUsers(iterable $users): self
    {
        return $this->withRecipients(NotificationRecipients::users($users));
    }

    public function toUserIds(array $userIds): self
    {
        return $this->withRecipients(NotificationRecipients::userIds($userIds));
    }

    public function channel(string $channel): self
    {
        return $this->channels([$channel]);
    }

    public function channels(array $channels): self
    {
        $pending = clone $this;
        $pending->channels = $channels;

        return $pending;
    }

    public function send(string $scene, array $variables = [], array $message = []): NotificationDispatchResult
    {
        return $this->manager->dispatch(NotificationSendRequest::make(
            $scene,
            $this->requireRecipients(),
            $variables,
            $this->channels,
            $message
        ));
    }

    public function sendLegacy(array $message): array
    {
        return $this->manager->dispatchLegacy($this->requireRecipients(), $message);
    }

    private function withRecipients(NotificationRecipients $recipients): self
    {
        $pending = clone $this;
        $pending->recipients = $recipients;

        return $pending;
    }

    private function requireRecipients(): NotificationRecipients
    {
        if (null === $this->recipients) {
            throw new \LogicException('发送通知前必须指定接收人');
        }

        return $this->recipients;
    }
}
