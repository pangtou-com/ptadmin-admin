<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\NotificationMessage;
use PTAdmin\Admin\Models\NotificationReceipt;
use PTAdmin\Admin\Support\Query\BuilderQueryApplier;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class NotificationService
{
    private NotificationDeliveryService $deliveryService;

    public function __construct(NotificationDeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    public function sendToAdmin(int $adminId, array $message): array
    {
        return $this->send(NotificationReceipt::RECEIVER_ADMIN, [$adminId], $message);
    }

    public function sendToAdmins(array $adminIds, array $message): array
    {
        return $this->send(NotificationReceipt::RECEIVER_ADMIN, $adminIds, $message);
    }

    public function sendToUser(int $userId, array $message): array
    {
        return $this->send(NotificationReceipt::RECEIVER_USER, [$userId], $message);
    }

    public function sendToUsers(array $userIds, array $message): array
    {
        return $this->send(NotificationReceipt::RECEIVER_USER, $userIds, $message);
    }

    public function send(string $receiverType, array $receiverIds, array $message): array
    {
        $receiverType = $this->normalizeReceiverType($receiverType);
        $receiverIds = $this->normalizeReceiverIds($receiverIds);
        if ([] === $receiverIds) {
            throw new BackgroundException('通知接收人不能为空');
        }

        $now = time();
        [$notification, $dispatchReceipts] = DB::transaction(function () use ($receiverType, $receiverIds, $message, $now): array {
            $notification = $this->findOrCreateMessage($receiverType, $message, $now);
            $dispatchReceipts = [];

            foreach ($receiverIds as $receiverId) {
                /** @var NotificationReceipt $receipt */
                $receipt = NotificationReceipt::query()->firstOrCreate([
                    'notification_id' => (int) $notification->id,
                    'receiver_type' => $receiverType,
                    'receiver_id' => $receiverId,
                ], [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($receipt->wasRecentlyCreated) {
                    $dispatchReceipts[] = $receipt;
                }
            }

            return [$notification, $dispatchReceipts];
        });

        $channels = $this->normalizeChannels($message['channels'] ?? []);
        foreach ($dispatchReceipts as $receipt) {
            $this->deliveryService->dispatchForReceipt($notification, $receipt, $channels);
        }

        return $this->messageToArray($notification);
    }

    public function pageForAdmin(Admin $admin, array $query = []): array
    {
        return $this->pageForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id, $query);
    }

    public function pageForReceiver(string $receiverType, int $receiverId, array $query = []): array
    {
        $query['paginate'] = true;
        $query = $this->normalizeQueryFields($query);
        $builder = $this->receiverMessageQuery($receiverType, $receiverId);

        $results = (new BuilderQueryApplier())->fetch(
            $builder,
            $query,
            [
                'allowed_filters' => ['notification_messages.id', 'notification_messages.category', 'notification_messages.level', 'notification_messages.source_type', 'notification_messages.source_code', 'notification_messages.biz_type', 'notification_messages.biz_id', 'notification_receipts.read_at', 'notification_receipts.archived_at', 'notification_messages.created_at'],
                'allowed_sorts' => ['notification_messages.id', 'notification_messages.created_at', 'notification_receipts.read_at'],
                'allowed_keyword_fields' => ['notification_messages.title', 'notification_messages.content', 'notification_messages.source_code', 'notification_messages.biz_type', 'notification_messages.biz_id'],
                'keyword_fields' => ['notification_messages.title', 'notification_messages.content', 'notification_messages.source_code', 'notification_messages.biz_type', 'notification_messages.biz_id'],
                'default_order' => ['notification_messages.id' => 'desc'],
            ]
        );

        $data = $results->toArray();
        $data['data'] = array_map([$this, 'formatJoinedMessage'], $data['data'] ?? []);

        return $data;
    }

    public function latestForAdmin(Admin $admin, int $limit = 10): array
    {
        return $this->latestForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id, $limit);
    }

    public function latestForReceiver(string $receiverType, int $receiverId, int $limit = 10): array
    {
        return $this->receiverMessageQuery($receiverType, $receiverId)
            ->orderBy('notification_messages.id', 'desc')
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->map(function ($row): array {
                return $this->formatJoinedMessage($row);
            })
            ->all();
    }

    public function detailForAdmin(Admin $admin, int $id): array
    {
        return $this->detailForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id, $id);
    }

    public function detailForReceiver(string $receiverType, int $receiverId, int $id): array
    {
        $row = $this->receiverMessageQuery($receiverType, $receiverId)
            ->where('notification_messages.id', $id)
            ->first();

        if (null === $row) {
            throw new BackgroundException(__('ptadmin::background.data_not_found'));
        }

        return $this->formatJoinedMessage($row);
    }

    public function unreadSummaryForAdmin(Admin $admin): array
    {
        return $this->unreadSummaryForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id);
    }

    public function categoriesForAdmin(Admin $admin): array
    {
        return $this->categoriesForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id);
    }

    public function categoriesForReceiver(string $receiverType, int $receiverId): array
    {
        $base = $this->receiverBaseQuery($receiverType, $receiverId);
        $totalMap = (clone $base)
            ->selectRaw($this->rawq('notification_messages', 'category').', count(*) as aggregate')
            ->groupBy(DB::raw($this->rawq('notification_messages', 'category')))
            ->pluck('aggregate', 'category')
            ->map(static function ($value): int {
                return (int) $value;
            })
            ->all();
        $unreadMap = (clone $base)
            ->whereNull('notification_receipts.read_at')
            ->selectRaw($this->rawq('notification_messages', 'category').', count(*) as aggregate')
            ->groupBy(DB::raw($this->rawq('notification_messages', 'category')))
            ->pluck('aggregate', 'category')
            ->map(static function ($value): int {
                return (int) $value;
            })
            ->all();

        $results = [[
            'code' => 'all',
            'title' => '全部',
            'total' => array_sum($totalMap),
            'unread' => array_sum($unreadMap),
            'sort' => 0,
        ]];

        foreach ($this->categoryDefinitions() as $definition) {
            $code = $definition['code'];
            $results[] = [
                'code' => $code,
                'title' => $definition['title'],
                'total' => (int) ($totalMap[$code] ?? 0),
                'unread' => (int) ($unreadMap[$code] ?? 0),
                'sort' => $definition['sort'],
            ];
        }

        return $results;
    }

    public function unreadSummaryForReceiver(string $receiverType, int $receiverId): array
    {
        $base = $this->receiverBaseQuery($receiverType, $receiverId)
            ->whereNull('notification_receipts.read_at');

        $total = (clone $base)->count();
        $levels = (clone $base)
            ->selectRaw($this->rawq('notification_messages', 'level').', count(*) as aggregate')
            ->groupBy(DB::raw($this->rawq('notification_messages', 'level')))
            ->pluck('aggregate', 'level')
            ->map(static function ($value): int {
                return (int) $value;
            })
            ->all();
        $categories = (clone $base)
            ->selectRaw($this->rawq('notification_messages', 'category').', count(*) as aggregate')
            ->groupBy(DB::raw($this->rawq('notification_messages', 'category')))
            ->pluck('aggregate', 'category')
            ->map(static function ($value): int {
                return (int) $value;
            })
            ->all();

        return [
            'total' => (int) $total,
            'levels' => $levels,
            'categories' => $categories,
        ];
    }

    public function markReadForAdmin(Admin $admin, int $id): void
    {
        $this->markReadForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id, $id);
    }

    public function markReadForReceiver(string $receiverType, int $receiverId, int $id): void
    {
        $updated = NotificationReceipt::query()
            ->where('notification_id', $id)
            ->where('receiver_type', $this->normalizeReceiverType($receiverType))
            ->where('receiver_id', $receiverId)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->update([
                'read_at' => time(),
                'updated_at' => time(),
            ]);

        if ($updated > 0) {
            return;
        }

        $exists = NotificationReceipt::query()
            ->where('notification_id', $id)
            ->where('receiver_type', $this->normalizeReceiverType($receiverType))
            ->where('receiver_id', $receiverId)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            throw new BackgroundException(__('ptadmin::background.data_not_found'));
        }
    }

    public function markAllReadForAdmin(Admin $admin): int
    {
        return $this->markAllReadForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id);
    }

    public function markAllReadForReceiver(string $receiverType, int $receiverId): int
    {
        return NotificationReceipt::query()
            ->where('receiver_type', $this->normalizeReceiverType($receiverType))
            ->where('receiver_id', $receiverId)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->update([
                'read_at' => time(),
                'updated_at' => time(),
            ]);
    }

    public function deleteForAdmin(Admin $admin, int $id): void
    {
        $this->deleteForReceiver(NotificationReceipt::RECEIVER_ADMIN, (int) $admin->id, $id);
    }

    public function deleteForReceiver(string $receiverType, int $receiverId, int $id): void
    {
        $updated = NotificationReceipt::query()
            ->where('notification_id', $id)
            ->where('receiver_type', $this->normalizeReceiverType($receiverType))
            ->where('receiver_id', $receiverId)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => time(),
                'updated_at' => time(),
            ]);

        if (0 === $updated) {
            throw new BackgroundException(__('ptadmin::background.data_not_found'));
        }
    }

    private function findOrCreateMessage(string $receiverType, array $message, int $now): NotificationMessage
    {
        $bizKey = $this->nullableString($message['biz_key'] ?? null, 150);
        if (null !== $bizKey) {
            $exists = NotificationMessage::query()->where('biz_key', $bizKey)->first();
            if ($exists instanceof NotificationMessage) {
                return $exists;
            }
        }

        /** @var NotificationMessage $notification */
        $notification = NotificationMessage::query()->create([
            'audience_type' => $this->normalizeAudienceType($message['audience_type'] ?? $receiverType),
            'source_type' => $this->normalizeString($message['source_type'] ?? 'system', 20, 'system'),
            'source_code' => $this->normalizeString($message['source_code'] ?? 'system', 100, 'system'),
            'category' => $this->normalizeString($message['category'] ?? 'notice', 50, 'notice'),
            'level' => $this->normalizeString($message['level'] ?? 'info', 20, 'info'),
            'title' => $this->normalizeString($message['title'] ?? '', 255, ''),
            'content' => $this->nullableString($message['content'] ?? ($message['message'] ?? null), 65535),
            'action_type' => $this->normalizeString($message['action_type'] ?? 'none', 20, 'none'),
            'action_url' => $this->nullableString($message['action_url'] ?? null, 500),
            'biz_type' => $this->nullableString($message['biz_type'] ?? null, 100),
            'biz_id' => $this->nullableString($message['biz_id'] ?? null, 100),
            'biz_key' => $bizKey,
            'data' => $this->normalizeArray($message['data'] ?? []),
            'created_by' => (int) ($message['created_by'] ?? 0),
            'expires_at' => $this->nullableTimestamp($message['expires_at'] ?? null),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $notification;
    }

    private function receiverMessageQuery(string $receiverType, int $receiverId)
    {
        return $this->receiverBaseQuery($receiverType, $receiverId)
            ->select([
                'notification_messages.id',
                'notification_messages.audience_type',
                'notification_messages.source_type',
                'notification_messages.source_code',
                'notification_messages.category',
                'notification_messages.level',
                'notification_messages.title',
                'notification_messages.content',
                'notification_messages.action_type',
                'notification_messages.action_url',
                'notification_messages.biz_type',
                'notification_messages.biz_id',
                'notification_messages.biz_key',
                'notification_messages.data',
                'notification_messages.created_by',
                'notification_messages.expires_at',
                'notification_messages.created_at',
                'notification_messages.updated_at',
                'notification_receipts.id as receipt_id',
                'notification_receipts.receiver_type',
                'notification_receipts.receiver_id',
                'notification_receipts.read_at',
                'notification_receipts.archived_at',
            ]);
    }

    private function receiverBaseQuery(string $receiverType, int $receiverId)
    {
        return NotificationReceipt::query()
            ->join('notification_messages', 'notification_messages.id', '=', 'notification_receipts.notification_id')
            ->where('notification_receipts.receiver_type', $this->normalizeReceiverType($receiverType))
            ->where('notification_receipts.receiver_id', $receiverId)
            ->whereNull('notification_receipts.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('notification_messages.expires_at')
                    ->orWhere('notification_messages.expires_at', '>', time());
            });
    }

    private function rawq(string $table, string $column): string
    {
        return DB::getTablePrefix().$table.'.'.$column;
    }

    private function normalizeQueryFields(array $query): array
    {
        return $query;
    }

    /**
     * @param mixed $row
     */
    private function formatJoinedMessage($row): array
    {
        if (is_array($row)) {
            $data = $row;
        } elseif (is_object($row) && method_exists($row, 'toArray')) {
            $data = $row->toArray();
        } else {
            $data = (array) $row;
        }

        $payload = $data['data'] ?? [];
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = JSON_ERROR_NONE === json_last_error() && is_array($decoded) ? $decoded : [];
        }

        $data['data'] = is_array($payload) ? $payload : [];
        $data['is_read'] = !empty($data['read_at']);

        return $data;
    }

    private function messageToArray(NotificationMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'audience_type' => $message->audience_type,
            'source_type' => $message->source_type,
            'source_code' => $message->source_code,
            'category' => $message->category,
            'level' => $message->level,
            'title' => $message->title,
            'content' => $message->content,
            'action_type' => $message->action_type,
            'action_url' => $message->action_url,
            'biz_type' => $message->biz_type,
            'biz_id' => $message->biz_id,
            'biz_key' => $message->biz_key,
            'data' => $message->data ?? [],
            'created_by' => (int) $message->created_by,
            'expires_at' => $message->expires_at,
        ];
    }

    private function normalizeReceiverType(string $type): string
    {
        $type = trim($type);
        if (!in_array($type, [NotificationReceipt::RECEIVER_ADMIN, NotificationReceipt::RECEIVER_USER], true)) {
            throw new BackgroundException('通知接收人类型无效');
        }

        return $type;
    }

    /**
     * @param mixed $type
     */
    private function normalizeAudienceType($type): string
    {
        $type = trim((string) $type);
        if (in_array($type, [NotificationMessage::AUDIENCE_ADMIN, NotificationMessage::AUDIENCE_USER, NotificationMessage::AUDIENCE_MIXED], true)) {
            return $type;
        }

        return NotificationMessage::AUDIENCE_MIXED;
    }

    private function normalizeReceiverIds(array $receiverIds): array
    {
        $ids = [];
        foreach ($receiverIds as $receiverId) {
            $receiverId = (int) $receiverId;
            if ($receiverId > 0) {
                $ids[] = $receiverId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param mixed $value
     */
    private function normalizeString($value, int $maxLength, string $default): string
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return $default;
        }

        return mb_substr($value, 0, $maxLength);
    }

    /**
     * @param mixed $value
     */
    private function nullableString($value, int $maxLength): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : mb_substr($value, 0, $maxLength);
    }

    /**
     * @param mixed $value
     */
    private function normalizeArray($value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @param mixed $value
     */
    private function normalizeChannels($value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @param mixed $value
     */
    private function nullableTimestamp($value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return false === $timestamp || $timestamp <= 0 ? null : $timestamp;
    }

    private function categoryDefinitions(): array
    {
        return [
            ['code' => 'notice', 'title' => '通知', 'sort' => 10],
            ['code' => 'todo', 'title' => '待办', 'sort' => 20],
            ['code' => 'alert', 'title' => '告警', 'sort' => 30],
        ];
    }
}
