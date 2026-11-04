<?php

declare(strict_types=1);

use Ikapelyushny\EnvCipher\EnvCipher;
use Ikapelyushny\EnvCipher\EnvCipherException;
use Illuminate\Contracts\Encryption\DecryptException;

it('generates keys as base64-encoded 32 random bytes', function (): void {
    $key = EnvCipher::generateKey();

    expect($key)->toStartWith('base64:')
        ->and(mb_strlen(base64_decode(mb_substr($key, 7), true), '8bit'))->toBe(32)
        ->and(EnvCipher::generateKey())->not->toBe($key);
});

it('round-trips values, including unicode', function (string $plain): void {
    $cipher = EnvCipher::fromEncodedKey(EnvCipher::generateKey());

    $encrypted = $cipher->encrypt($plain);

    expect($encrypted)->toStartWith('cipher:')
        ->and($cipher->decrypt($encrypted))->toBe($plain);
})->with([
    'ascii' => ['sk-proj-123'],
    'unicode' => ["пароль з апострофом м'ясо"],
    'spaces and equals' => ['a b=c'],
]);

it('uses a random IV for every encryption', function (): void {
    $cipher = EnvCipher::fromEncodedKey(EnvCipher::generateKey());

    expect($cipher->encrypt('same'))->not->toBe($cipher->encrypt('same'));
});

it('rejects malformed keys', function (string $key): void {
    EnvCipher::fromEncodedKey($key);
})->with([
    'no prefix' => [base64_encode(random_bytes(32))],
    'too short' => ['base64:' . base64_encode(random_bytes(16))],
    'not base64' => ['base64:@@@'],
])->throws(EnvCipherException::class, 'key must be');

it('fails to decrypt with a different key', function (): void {
    $encrypted = EnvCipher::fromEncodedKey(EnvCipher::generateKey())->encrypt('secret');

    EnvCipher::fromEncodedKey(EnvCipher::generateKey())->decrypt($encrypted);
})->throws(DecryptException::class);

it('fails to decrypt corrupted values', function (): void {
    $cipher = EnvCipher::fromEncodedKey(EnvCipher::generateKey());

    $cipher->decrypt(mb_substr($cipher->encrypt('secret'), 0, -8) . 'AAAAAAAA');
})->throws(DecryptException::class);

it('refuses to decrypt values without the prefix', function (): void {
    EnvCipher::fromEncodedKey(EnvCipher::generateKey())->decrypt('plain');
})->throws(DecryptException::class);

it('detects encrypted values', function (mixed $value, bool $expected): void {
    expect(EnvCipher::isEncrypted($value))->toBe($expected);
})->with([
    ['cipher:abc', true],
    ['plain', false],
    [null, false],
    [true, false],
]);

it('loads the key from a file and ignores the trailing newline', function (): void {
    $key = EnvCipher::generateKey();
    $path = tempFile($key . "\n");

    $encrypted = EnvCipher::fromEncodedKey($key)->encrypt('secret');

    expect(EnvCipher::fromKeyFile($path)->decrypt($encrypted))->toBe('secret');

    unlink($path);
});

it('explains a missing key file without leaking anything', function (): void {
    EnvCipher::fromKeyFile('/nonexistent/env_cipher_key');
})->throws(EnvCipherException::class, 'key file [/nonexistent/env_cipher_key] is missing or unreadable');
