<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\NotificationDelivery;
use PTAdmin\Admin\Models\NotificationMessage;
use PTAdmin\Admin\Models\NotificationReceipt;
use PTAdmin\Admin\Models\User;

class NotificationDeliveryService
{
    public function dispatchForNotification(NotificationMessage $notification, array $channels = []): void
    {
        $receipts = NotificationReceipt::query()
            ->where('notification_id', (int) $notification->id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($receipts as $receipt) {
            $this->dispatchForReceipt($notification, $receipt, $channels);
        }
    }

    public function dispatchForReceipt(NotificationMessage $notification, NotificationReceipt $receipt, array $channels = []): void
    {
        if (!(bool) config('ptadmin.notifications.delivery.enabled', true)) {
            return;
        }

        foreach ($this->resolveChannels($receipt, $notification, $channels) as $channel) {
            $delivery = $this->createDelivery($notification, $receipt, $channel);

            if ((bool) config('ptadmin.notifications.delivery.sync', true)) {
                $this->sendDelivery($delivery);
            }
        }
    }

    public function sendDelivery(NotificationDelivery $delivery): void
    {
        $notification = NotificationMessage::query()->find((int) $delivery->notification_id);
        $receipt = NotificationReceipt::query()->find((int) $delivery->receipt_id);
        if (!$notification instanceof NotificationMessage || !$receipt instanceof NotificationReceipt) {
            $this->markFailed($delivery, '通知消息或接收状态不存在');

            return;
        }

        try {
            $provider = $this->resolveProvider((string) $delivery->provider);
            if ('mail' === ($provider['driver'] ?? '')) {
                $result = $this->sendMail($notification, $receipt);
            } else {
                $result = $this->sendAddonNotify($notification, $receipt, $delivery, $provider);
            }

            $this->markSuccess($delivery, $result);
        } catch (\Throwable $throwable) {
            $this->markFailed($delivery, $throwable->getMessage());
        }
    }

    private function createDelivery(NotificationMessage $notification, NotificationReceipt $receipt, array $channel): NotificationDelivery
    {
        /** @var NotificationDelivery $delivery */
        $delivery = NotificationDelivery::query()->create([
            'notification_id' => (int) $notification->id,
            'receipt_id' => (int) $receipt->id,
            'receiver_type' => $receipt->receiver_type,
            'receiver_id' => (int) $receipt->receiver_id,
            'channel' => (string) ($channel['channel'] ?? $channel['provider'] ?? ''),
            'provider' => (string) ($channel['provider'] ?? $channel['channel'] ?? ''),
            'status' => 'pending',
            'meta' => [
                'template' => $channel['template'] ?? null,
                'channel' => $channel,
            ],
        ]);

        return $delivery;
    }

    private function resolveChannels(NotificationReceipt $receipt, NotificationMessage $notification, array $channels): array
    {
        $channels = [] === $channels ? (array) config('ptadmin.notifications.channels.'.$receipt->receiver_type, []) : $channels;
        $results = [];

        foreach ($channels as $channel) {
            if (is_string($channel)) {
                $channel = ['channel' => $channel, 'provider' => $channel];
            }
            if (!is_array($channel)) {
                continue;
            }
            if (isset($channel['levels']) && is_array($channel['levels']) && !in_array($notification->level, $channel['levels'], true)) {
                continue;
            }
            if (isset($channel['categories']) && is_array($channel['categories']) && !in_array($notification->category, $channel['categories'], true)) {
                continue;
            }

            $provider = trim((string) ($channel['provider'] ?? $channel['channel'] ?? ''));
            if ('' === $provider) {
                continue;
            }

            $channel['provider'] = $provider;
            $channel['channel'] = trim((string) ($channel['channel'] ?? $provider));
            $results[] = $channel;
        }

        return $results;
    }

    private function resolveProvider(string $provider): array
    {
        $config = (array) config('ptadmin.notifications.providers.'.$provider, []);
        if ([] === $config) {
            return [
                'driver' => 'addon',
                'code' => $provider,
                'enabled' => true,
            ];
        }

        $config['code'] = $config['code'] ?? $provider;
        $config['driver'] = $config['driver'] ?? 'addon';
        $config['enabled'] = array_key_exists('enabled', $config) ? (bool) $config['enabled'] : true;

        return $config;
    }

    private function sendMail(NotificationMessage $notification, NotificationReceipt $receipt): array
    {
        $mailConfig = $this->resolveMailConfig();
        $receiver = $this->resolveReceiver($receipt);
        $email = trim((string) ($receiver['email'] ?? ''));
        if ('' === $email) {
            throw new \RuntimeException('通知接收人邮箱为空');
        }

        Mail::raw((string) ($notification->content ?? $notification->title), function ($message) use ($email, $notification, $mailConfig): void {
            $message->from($mailConfig['from_address'], $mailConfig['from_name']);
            $message->to($email)->subject((string) $notification->title);
        });

        return [
            'message_id' => null,
            'batch_id' => null,
            'status' => 'sent',
            'accepted_at' => time(),
            'delivered_at' => time(),
            'meta' => [
                'email' => $email,
                'from_address' => $mailConfig['from_address'],
            ],
            'raw' => null,
        ];
    }

    private function resolveMailConfig(): array
    {
        $enabled = (int) system_config('mail.enabled', 0);
        if (1 !== $enabled) {
            throw new \RuntimeException('内置邮件通知未启用');
        }

        $host = trim((string) system_config('mail.host', ''));
        $port = (int) system_config('mail.port', 587);
        $username = trim((string) system_config('mail.username', ''));
        $password = (string) system_config('mail.password', '');
        $encryption = trim((string) system_config('mail.encryption', 'tls'));
        $fromAddress = trim((string) system_config('mail.from_address', ''));
        $fromName = trim((string) system_config('mail.from_name', 'PTAdmin'));

        if ('' === $host) {
            throw new \RuntimeException('邮件 SMTP 主机未配置');
        }
        if ($port <= 0) {
            throw new \RuntimeException('邮件 SMTP 端口未配置');
        }
        if ('' === $username) {
            throw new \RuntimeException('邮件账号未配置');
        }
        if ('' === $password) {
            throw new \RuntimeException('邮件密码未配置');
        }
        if ('' === $fromAddress) {
            $fromAddress = $username;
        }

        $config = [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $port,
            'encryption' => '' === $encryption ? null : $encryption,
            'username' => $username,
            'password' => $password,
            'timeout' => null,
            'auth_mode' => null,
        ];

        Config::set('mail.default', 'ptadmin_notification');
        Config::set('mail.mailers.ptadmin_notification', $config);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        if (method_exists(Mail::getFacadeRoot(), 'purge')) {
            Mail::purge('ptadmin_notification');
        }

        return [
            'from_address' => $fromAddress,
            'from_name' => $fromName,
        ];
    }

    private function sendAddonNotify(NotificationMessage $notification, NotificationReceipt $receipt, NotificationDelivery $delivery, array $provider): array
    {
        if (array_key_exists('enabled', $provider) && !(bool) $provider['enabled']) {
            throw new \RuntimeException('通知渠道未启用');
        }

        $receiver = $this->resolveReceiver($receipt);
        $code = (string) ($provider['code'] ?? $delivery->provider);

        return (array) Addon::executeInject('notify', $code, [
            'channel' => (string) $delivery->channel,
            'receiver' => $receiver['target'] ?? (string) $receipt->receiver_id,
            'template' => $this->deliveryTemplate($delivery),
            'subject' => $notification->title,
            'message' => $notification->content,
            'data' => $notification->data ?? [],
            'meta' => [
                'notification_id' => (int) $notification->id,
                'receipt_id' => (int) $receipt->id,
                'delivery_id' => (int) $delivery->id,
                'receiver_type' => $receipt->receiver_type,
                'receiver_id' => (int) $receipt->receiver_id,
                'receiver' => $receiver,
            ],
        ], 'send');
    }

    private function resolveReceiver(NotificationReceipt $receipt): array
    {
        if (NotificationReceipt::RECEIVER_ADMIN === $receipt->receiver_type) {
            $admin = Admin::query()->find((int) $receipt->receiver_id);

            return [
                'type' => $receipt->receiver_type,
                'id' => (int) $receipt->receiver_id,
                'target' => (string) $receipt->receiver_id,
                'email' => $admin instanceof Admin ? $admin->email : null,
                'mobile' => $admin instanceof Admin ? $admin->mobile : null,
            ];
        }

        if (NotificationReceipt::RECEIVER_USER === $receipt->receiver_type) {
            $user = User::query()->find((int) $receipt->receiver_id);

            return [
                'type' => $receipt->receiver_type,
                'id' => (int) $receipt->receiver_id,
                'target' => (string) $receipt->receiver_id,
                'email' => $user instanceof User ? $user->email : null,
                'mobile' => $user instanceof User ? $user->mobile : null,
            ];
        }

        return [
            'type' => $receipt->receiver_type,
            'id' => (int) $receipt->receiver_id,
            'target' => (string) $receipt->receiver_id,
        ];
    }

    private function deliveryTemplate(NotificationDelivery $delivery): ?string
    {
        $meta = $delivery->meta ?? [];
        if (!is_array($meta)) {
            return null;
        }

        $template = $meta['template'] ?? null;

        return null === $template || '' === $template ? null : (string) $template;
    }

    private function markSuccess(NotificationDelivery $delivery, array $result): void
    {
        $delivery->fill([
            'message_id' => $this->nullableString($result['message_id'] ?? null, 150),
            'batch_id' => $this->nullableString($result['batch_id'] ?? null, 150),
            'status' => (string) ($result['status'] ?? 'sent'),
            'accepted_at' => $this->nullableTimestamp($result['accepted_at'] ?? time()),
            'delivered_at' => $this->nullableTimestamp($result['delivered_at'] ?? null),
            'error_message' => null,
            'meta' => is_array($result['meta'] ?? null) ? $result['meta'] : [],
            'raw' => $this->normalizeRaw($result['raw'] ?? null),
        ])->save();
    }

    private function markFailed(NotificationDelivery $delivery, string $message): void
    {
        $delivery->fill([
            'status' => 'failed',
            'error_message' => '' === $message ? '通知投递失败' : mb_substr($message, 0, 1000),
        ])->save();
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

    /**
     * @param mixed $value
     */
    private function nullableString($value, int $length): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : mb_substr($value, 0, $length);
    }

    /**
     * @param mixed $raw
     *
     * @return mixed
     */
    private function normalizeRaw($raw)
    {
        if (null === $raw || is_array($raw)) {
            return $raw;
        }

        if (is_scalar($raw)) {
            return ['value' => $raw];
        }

        return ['value' => (string) json_encode($raw)];
    }
}
