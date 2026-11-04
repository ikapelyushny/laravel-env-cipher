<?php

declare(strict_types=1);

namespace Ikapelyushny\EnvCipher;

use RuntimeException;
use Throwable;

/**
 * Messages name variables and files, never values.
 */
final class EnvCipherException extends RuntimeException
{
    public static function keyFileUnreadable(string $path): self
    {
        return new self("env-cipher: key file [{$path}] is missing or unreadable. Set ENV_CIPHER_KEY_FILE or mount the Docker secret.");
    }

    public static function invalidKey(): self
    {
        return new self('env-cipher: key must be "base64:" followed by 32 random bytes encoded in base64. Generate one with "php artisan env-cipher:key".');
    }

    public static function cannotDecrypt(string $variable, Throwable $previous): self
    {
        return new self("env-cipher: cannot decrypt [{$variable}]: wrong key or corrupted value.", 0, $previous);
    }

    public static function secretFileUnreadable(string $variable, string $path): self
    {
        return new self("env-cipher: file [{$path}] from [{$variable}_FILE] is missing or unreadable.");
    }
}
