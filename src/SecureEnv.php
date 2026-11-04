<?php

declare(strict_types=1);

namespace Ikapelyushny\EnvCipher;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Env;

/**
 * Resolves configuration values from Docker secret files and encrypted .env values.
 *
 * Works without the Laravel container, so it is safe to call from config files.
 */
final class SecureEnv
{
    /** @var array<string, EnvCipher> */
    private static array $ciphers = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $file = Env::get($key . '_FILE');

        if (is_string($file) && $file !== '') {
            return self::readSecretFile($key, $file);
        }

        $value = Env::get($key, $default);

        if (!EnvCipher::isEncrypted($value)) {
            return $value;
        }

        try {
            return self::cipher()->decrypt($value);
        } catch (DecryptException $e) {
            throw EnvCipherException::cannotDecrypt($key, $e);
        }
    }

    public static function keyFilePath(): string
    {
        $path = Env::get('ENV_CIPHER_KEY_FILE');

        return is_string($path) && $path !== '' ? $path : EnvCipher::DEFAULT_KEY_FILE;
    }

    /**
     * Forgets loaded keys (tests).
     */
    public static function flush(): void
    {
        self::$ciphers = [];
    }

    private static function cipher(): EnvCipher
    {
        $path = self::keyFilePath();

        return self::$ciphers[$path] ??= EnvCipher::fromKeyFile($path);
    }

    private static function readSecretFile(string $key, string $path): string
    {
        $contents = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw EnvCipherException::secretFileUnreadable($key, $path);
        }

        return rtrim($contents, "\r\n");
    }
}
