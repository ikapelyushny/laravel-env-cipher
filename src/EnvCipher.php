<?php

declare(strict_types=1);

namespace Ikapelyushny\EnvCipher;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use SensitiveParameter;

/**
 * Encrypts single .env values as "cipher:<payload>" with AES-256-GCM.
 */
final readonly class EnvCipher
{
    public const string PREFIX = 'cipher:';

    public const string CIPHER = 'aes-256-gcm';

    public const string DEFAULT_KEY_FILE = '/run/secrets/env_cipher_key';

    private const string KEY_PREFIX = 'base64:';

    private Encrypter $encrypter;

    public function __construct(#[SensitiveParameter] string $rawKey)
    {
        if (!Encrypter::supported($rawKey, self::CIPHER)) {
            throw EnvCipherException::invalidKey();
        }

        $this->encrypter = new Encrypter($rawKey, self::CIPHER);
    }

    public static function generateKey(): string
    {
        return self::KEY_PREFIX . base64_encode(Encrypter::generateKey(self::CIPHER));
    }

    public static function fromEncodedKey(#[SensitiveParameter] string $encodedKey): self
    {
        $rawKey = str_starts_with($encodedKey, self::KEY_PREFIX)
            ? base64_decode(mb_substr($encodedKey, mb_strlen(self::KEY_PREFIX)), true)
            : false;

        if ($rawKey === false) {
            throw EnvCipherException::invalidKey();
        }

        return new self($rawKey);
    }

    public static function fromKeyFile(string $path): self
    {
        $contents = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw EnvCipherException::keyFileUnreadable($path);
        }

        return self::fromEncodedKey(mb_trim($contents));
    }

    /**
     * @phpstan-assert-if-true string $value
     */
    public static function isEncrypted(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    public function encrypt(#[SensitiveParameter] string $plain): string
    {
        return self::PREFIX . $this->encrypter->encryptString($plain);
    }

    /**
     * @throws DecryptException
     */
    public function decrypt(string $value): string
    {
        if (!str_starts_with($value, self::PREFIX)) {
            throw new DecryptException('Value is not prefixed with "' . self::PREFIX . '".');
        }

        return $this->encrypter->decryptString(mb_substr($value, mb_strlen(self::PREFIX)));
    }
}
