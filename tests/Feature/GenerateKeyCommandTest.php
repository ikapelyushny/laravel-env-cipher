<?php

declare(strict_types=1);

use Ikapelyushny\EnvCipher\EnvCipher;

beforeEach(function (): void {
    $this->path = sys_get_temp_dir() . '/env-cipher-key-' . bin2hex(random_bytes(8));
});

afterEach(function (): void {
    @unlink($this->path);
});

it('writes a new key to a 0600 file without printing it', function (): void {
    $this->artisan('env-cipher:key', ['path' => $this->path])
        ->expectsOutputToContain('Key written to')
        ->doesntExpectOutputToContain('base64:')
        ->assertSuccessful();

    expect(fileperms($this->path) & 0777)->toBe(0600)
        ->and(file_get_contents($this->path))->toMatch('/^base64:[A-Za-z0-9+\/]{43}=\n$/');

    EnvCipher::fromKeyFile($this->path);
});

it('refuses to overwrite an existing key without --force', function (): void {
    file_put_contents($this->path, 'old');

    $this->artisan('env-cipher:key', ['path' => $this->path])->assertFailed();

    expect(file_get_contents($this->path))->toBe('old');
});

it('overwrites an existing key with --force', function (): void {
    file_put_contents($this->path, 'old');

    $this->artisan('env-cipher:key', ['path' => $this->path, '--force' => true])->assertSuccessful();

    expect(file_get_contents($this->path))->toStartWith('base64:');
});

it('fails when the directory does not exist', function (): void {
    $this->artisan('env-cipher:key', ['path' => '/nonexistent-dir/key'])->assertFailed();
});
