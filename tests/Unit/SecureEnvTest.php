<?php

declare(strict_types=1);

use Ikapelyushny\EnvCipher\EnvCipher;
use Ikapelyushny\EnvCipher\EnvCipherException;
use Ikapelyushny\EnvCipher\SecureEnv;

beforeEach(function (): void {
    $this->key = EnvCipher::generateKey();
    $this->keyFile = tempFile($this->key);
    $this->cipher = EnvCipher::fromEncodedKey($this->key);
});

afterEach(function (): void {
    @unlink($this->keyFile);
    SecureEnv::flush();
});

it('returns plain values as is', function (): void {
    withEnv(['EC_TEST_PLAIN' => 'hello'], function (): void {
        expect(secure_env('EC_TEST_PLAIN'))->toBe('hello');
    });
});

it('returns the default for missing variables', function (): void {
    expect(secure_env('EC_TEST_MISSING', 'fallback'))->toBe('fallback');
});

it('decrypts cipher values with the key from ENV_CIPHER_KEY_FILE', function (): void {
    withEnv([
        'ENV_CIPHER_KEY_FILE' => $this->keyFile,
        'EC_TEST_SECRET' => $this->cipher->encrypt('s3cret'),
    ], function (): void {
        expect(secure_env('EC_TEST_SECRET'))->toBe('s3cret');
    });
});

it('reads <KEY>_FILE first and trims the trailing newline', function (): void {
    $secretFile = tempFile("from-file\n");

    withEnv([
        'EC_TEST_DB_PASSWORD' => 'from-env',
        'EC_TEST_DB_PASSWORD_FILE' => $secretFile,
    ], function (): void {
        expect(secure_env('EC_TEST_DB_PASSWORD'))->toBe('from-file');
    });

    unlink($secretFile);
});

it('fails loudly when <KEY>_FILE points to a missing file', function (): void {
    withEnv(['EC_TEST_X_FILE' => '/nonexistent/secret'], function (): void {
        secure_env('EC_TEST_X');
    });
})->throws(EnvCipherException::class, 'file [/nonexistent/secret] from [EC_TEST_X_FILE]');

it('fails loudly when an encrypted value has no key file', function (): void {
    withEnv([
        'ENV_CIPHER_KEY_FILE' => '/nonexistent/key',
        'EC_TEST_SECRET' => 'cipher:whatever',
    ], function (): void {
        secure_env('EC_TEST_SECRET');
    });
})->throws(EnvCipherException::class, 'key file [/nonexistent/key]');

it('names the variable but not the value when decryption fails', function (): void {
    $foreign = EnvCipher::fromEncodedKey(EnvCipher::generateKey())->encrypt('s3cret');

    try {
        withEnv([
            'ENV_CIPHER_KEY_FILE' => $this->keyFile,
            'EC_TEST_SECRET' => $foreign,
        ], fn (): mixed => secure_env('EC_TEST_SECRET'));
        $this->fail('Expected EnvCipherException');
    } catch (EnvCipherException $e) {
        expect($e->getMessage())->toContain('[EC_TEST_SECRET]')
            ->not->toContain($foreign)
            ->not->toContain('s3cret');
    }
});

it('falls back to the default key file path', function (): void {
    expect(SecureEnv::keyFilePath())->toBe('/run/secrets/env_cipher_key');

    withEnv(['ENV_CIPHER_KEY_FILE' => '/tmp/custom'], function (): void {
        expect(SecureEnv::keyFilePath())->toBe('/tmp/custom');
    });
});

it('keeps non-UTF-8 secret files byte-exact', function (): void {
    $secretFile = tempFile("p\xE9ss\n");

    withEnv(['EC_TEST_BIN_FILE' => $secretFile], function (): void {
        expect(secure_env('EC_TEST_BIN'))->toBe("p\xE9ss");
    });

    unlink($secretFile);
});

it('returns cipher-looking secret file contents verbatim', function (): void {
    $secretFile = tempFile("cipher:abc\n");

    withEnv(['EC_TEST_RAW_FILE' => $secretFile], function (): void {
        expect(secure_env('EC_TEST_RAW'))->toBe('cipher:abc');
    });

    unlink($secretFile);
});

it('falls back to <KEY> when <KEY>_FILE is empty', function (): void {
    withEnv([
        'EC_TEST_EMPTY_FILE' => '',
        'EC_TEST_EMPTY' => 'plain',
    ], function (): void {
        expect(secure_env('EC_TEST_EMPTY'))->toBe('plain');
    });
});

it('casts "true" to boolean like env()', function (): void {
    withEnv(['EC_TEST_BOOL' => 'true'], function (): void {
        expect(secure_env('EC_TEST_BOOL'))->toBeTrue();
    });
});
