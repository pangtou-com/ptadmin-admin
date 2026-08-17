<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\User;
use PTAdmin\Admin\Services\NotificationService;

final class NotificationManager
{
    /** @var NotificationService */
    private $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function pending(): PendingNotification
    {
        return new PendingNotification($this);
    }

    public function toAdmin(Admin $admin): PendingNotification
    {
        return $this->pending()->toAdmin($admin);
    }

    public function toAdminId(int $adminId): PendingNotification
    {
        return $this->pending()->toAdminId($adminId);
    }

    public function toAdmins(iterable $admins): PendingNotification
    {
        return $this->pending()->toAdmins($admins);
    }

    public function toAdminIds(array $adminIds): PendingNotification
    {
        return $this->pending()->toAdminIds($adminIds);
    }

    public function toUser(User $user): PendingNotification
    {
        return $this->pending()->toUser($user);
    }

    public function toUserId(int $userId): PendingNotification
    {
        return $this->pending()->toUserId($userId);
    }

    public function toUsers(iterable $users): PendingNotification
    {
        return $this->pending()->toUsers($users);
    }

    public function toUserIds(array $userIds): PendingNotification
    {
        return $this->pending()->toUserIds($userIds);
    }

    public function dispatch(NotificationSendRequest $request): NotificationDispatchResult
    {
        return $this->service->dispatch($request);
    }

    public function dispatchLegacy(NotificationRecipients $recipients, array $message): array
    {
        return $this->service->send($recipients->type(), $recipients->ids(), $message);
    }
}
