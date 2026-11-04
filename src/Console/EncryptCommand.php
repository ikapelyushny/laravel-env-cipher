<?php

declare(strict_types=1);

namespace Ikapelyushny\EnvCipher\Console;

use Ikapelyushny\EnvCipher\EnvCipher;
use Ikapelyushny\EnvCipher\EnvCipherException;
use Ikapelyushny\EnvCipher\SecureEnv;
use Illuminate\Console\Command;

final class EncryptCommand extends Command
{
    protected $signature = 'env-cipher:encrypt
        {--key-file= : Key file (default: ENV_CIPHER_KEY_FILE or /run/secrets/env_cipher_key)}';

    protected $description = 'Encrypt a value for .env (the value is read from hidden input)';

    public function handle(): int
    {
        $option = $this->option('key-file');
        $keyFile = is_string($option) && $option !== '' ? $option : SecureEnv::keyFilePath();

        $value = $this->secret('Value to encrypt');

        if (!is_string($value) || $value === '') {
            $this->components->error('Nothing to encrypt.');

            return self::FAILURE;
        }

        try {
            $cipher = EnvCipher::fromKeyFile($keyFile);
        } catch (EnvCipherException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line($cipher->encrypt($value));

        return self::SUCCESS;
    }
}
