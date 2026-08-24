<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Filesystem\Filesystem;
use PTAdmin\Admin\Exceptions\ApplicationInstanceUnavailableException;
use RuntimeException;
use Throwable;

/**
 * 提供应用状态采集使用的本地实例标识和签名密钥。
 *
 * 身份文件缺失或损坏时会自动重建，避免状态采集干预宿主业务。
 */
final class ApplicationInstanceService
{
    private Filesystem $filesystem;

    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /**
     * @return array{application_instance_id:string, public_key:string, created_at:int}
     */
    public function current(): array
    {
        $identity = $this->resolveIdentity();

        return [
            'application_instance_id' => $identity['application_instance_id'],
            'public_key' => $identity['public_key'],
            'created_at' => (int) ($identity['created_at'] ?? 0),
        ];
    }

    /**
     * @return array{application_instance_id:string, public_key:string, created_at:int}
     */
    public function ensure(): array
    {
        return $this->current();
    }

    public function applicationInstanceId(): string
    {
        return $this->current()['application_instance_id'];
    }

    public function publicKey(): string
    {
        return $this->current()['public_key'];
    }

    public function sign(string $payload): string
    {
        $identity = $this->resolveIdentity();
        $privateKey = openssl_pkey_get_private($identity['private_key']);
        if (false === $privateKey || !openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new ApplicationInstanceUnavailableException('Unable to sign PTAdmin application instance payload.');
        }

        return base64_encode($signature);
    }

    private function path(): string
    {
        return (string) config(
            'ptadmin.application_instance_path',
            storage_path('app/ptadmin/ptadmin-application-identity.json')
        );
    }

    /** @return array{application_instance_id:string, public_key:string, private_key:string, created_at:int} */
    private function resolveIdentity(): array
    {
        $path = $this->path();
        $directory = dirname($path);

        try {
            $this->filesystem->ensureDirectoryExists($directory);
        } catch (Throwable $exception) {
            throw new ApplicationInstanceUnavailableException(
                'Unable to prepare PTAdmin application instance identity directory.',
                0,
                $exception
            );
        }

        $lock = @fopen($path.'.lock', 'c');
        if (false === $lock || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new ApplicationInstanceUnavailableException('Unable to lock PTAdmin application instance identity.');
        }
        @chmod($path.'.lock', 0600);

        try {
            try {
                return $this->storedIdentity($path);
            } catch (Throwable $exception) {
                if ($this->filesystem->exists($path) && !$this->filesystem->delete($path)) {
                    throw new ApplicationInstanceUnavailableException(
                        'Unable to replace PTAdmin application instance identity.',
                        0,
                        $exception
                    );
                }
            }

            try {
                return $this->createIdentity($path);
            } catch (ApplicationInstanceUnavailableException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                throw new ApplicationInstanceUnavailableException(
                    'Unable to initialize PTAdmin application instance identity.',
                    0,
                    $exception
                );
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return array{application_instance_id:string, public_key:string, private_key:string, created_at:int} */
    private function storedIdentity(string $path): array
    {
        if (!$this->filesystem->exists($path)) {
            throw new RuntimeException('PTAdmin application instance identity does not exist.');
        }

        @chmod($path, 0600);

        try {
            $identity = json_decode($this->filesystem->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new RuntimeException('PTAdmin application instance identity is unreadable.', 0, $exception);
        }

        if (!is_array($identity)
            || !is_string($identity['application_instance_id'] ?? null)
            || '' === $identity['application_instance_id']
            || !is_string($identity['public_key'] ?? null)
            || '' === $identity['public_key']
            || !is_string($identity['private_key'] ?? null)
            || '' === $identity['private_key']
        ) {
            throw new RuntimeException('PTAdmin application instance identity is invalid.');
        }

        if (false === openssl_pkey_get_private($identity['private_key'])
            || false === openssl_pkey_get_public($identity['public_key'])
        ) {
            throw new RuntimeException('PTAdmin application instance key pair is invalid.');
        }

        return [
            'application_instance_id' => $identity['application_instance_id'],
            'public_key' => $identity['public_key'],
            'private_key' => $identity['private_key'],
            'created_at' => (int) ($identity['created_at'] ?? 0),
        ];
    }

    /** @return array{application_instance_id:string, public_key:string, private_key:string, created_at:int} */
    private function createIdentity(string $path): array
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        if (false === $key) {
            throw new ApplicationInstanceUnavailableException('Unable to generate PTAdmin application instance key pair.');
        }

        $privateKey = '';
        $publicDetails = openssl_pkey_get_details($key);
        if (false === $publicDetails || !isset($publicDetails['key'])) {
            throw new ApplicationInstanceUnavailableException('Unable to read PTAdmin application instance public key.');
        }
        if (!openssl_pkey_export($key, $privateKey) || '' === $privateKey) {
            throw new ApplicationInstanceUnavailableException('Unable to export PTAdmin application instance private key.');
        }

        $identity = [
            'application_instance_id' => 'pt_'.bin2hex(random_bytes(16)),
            'public_key' => $publicDetails['key'],
            'private_key' => $privateKey,
            'created_at' => time(),
        ];
        $contents = json_encode($identity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $stream = @fopen($path, 'x');
        if (false === $stream) {
            throw new ApplicationInstanceUnavailableException('Unable to create PTAdmin application instance identity file.');
        }
        @chmod($path, 0600);

        try {
            $written = fwrite($stream, $contents);
            if (false === $written || strlen($contents) !== $written) {
                throw new ApplicationInstanceUnavailableException('Unable to persist PTAdmin application instance identity.');
            }
        } catch (Throwable $exception) {
            fclose($stream);
            @unlink($path);

            throw $exception;
        }
        fclose($stream);

        return $identity;
    }
}
