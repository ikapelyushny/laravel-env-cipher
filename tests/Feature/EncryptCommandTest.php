<?php

declare(strict_types=1);

use Ikapelyushny\EnvCipher\EnvCipher;

beforeEach(function (): void {
    $this->keyFile = tempFile(EnvCipher::generateKey());
});

afterEach(function (): void {
    @unlink($this->keyFile);
});

it('encrypts a hidden value and prints only the cipher text', function (): void {
    $this->artisan('env-cipher:encrypt', ['--key-file' => $this->keyFile])
        ->expectsQuestion('Value to encrypt', 's3cret')
        ->expectsOutputToContain('cipher:')
        ->doesntExpectOutputToContain('s3cret')
        ->assertSuccessful();
});

it('fails on an empty value', function (): void {
    $this->artisan('env-cipher:encrypt', ['--key-file' => $this->keyFile])
        ->expectsQuestion('Value to encrypt', '')
        ->assertFailed();
});

it('fails with a clear message when the key file is missing', function (): void {
    $this->artisan('env-cipher:encrypt', ['--key-file' => '/nonexistent/key'])
        ->expectsQuestion('Value to encrypt', 's3cret')
        ->expectsOutputToContain('key file [/nonexistent/key]')
        ->assertFailed();
});
