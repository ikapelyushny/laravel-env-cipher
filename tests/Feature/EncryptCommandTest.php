<?php

declare(strict_types=1);

use Ikapelyushny\EnvCipher\EnvCipher;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
        ->expectsOutputToContain('key file [/nonexistent/key]')
        ->assertFailed();
});

it('prints a cipher text that decrypts to the typed value', function (): void {
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, "s3cret\n");
    rewind($stream);

    $input = new ArrayInput(['command' => 'env-cipher:encrypt', '--key-file' => $this->keyFile]);
    $input->setStream($stream);
    $output = new BufferedOutput;
    QuestionHelper::disableStty();

    $status = $this->app->make(Kernel::class)->handle($input, $output);

    expect($status)->toBe(0)
        ->and(preg_match('/^cipher:\S+$/m', $output->fetch(), $m))->toBe(1)
        ->and(EnvCipher::fromKeyFile($this->keyFile)->decrypt($m[0]))->toBe('s3cret');
});
