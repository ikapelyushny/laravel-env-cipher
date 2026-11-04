<?php

declare(strict_types=1);

namespace Ikapelyushny\EnvCipher\Console;

use Ikapelyushny\EnvCipher\EnvCipher;
use Illuminate\Console\Command;

final class GenerateKeyCommand extends Command
{
    protected $signature = 'env-cipher:key
        {path : File to write the new key to (created with mode 0600)}
        {--force : Overwrite an existing file}';

    protected $description = 'Generate a new env-cipher key and write it to a file';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!is_string($path) || $path === '') {
            $this->components->error('Path is required.');

            return self::FAILURE;
        }

        if (file_exists($path) && $this->option('force') !== true) {
            $this->components->error("File [{$path}] already exists. Use --force to overwrite it.");

            return self::FAILURE;
        }

        $directory = dirname($path);

        if (!is_dir($directory) || !is_writable($directory)) {
            $this->components->error("Directory [{$directory}] does not exist or is not writable.");

            return self::FAILURE;
        }

        $previousUmask = umask(0077);

        try {
            file_put_contents($path, EnvCipher::generateKey() . PHP_EOL);
            chmod($path, 0600);
        } finally {
            umask($previousUmask);
        }

        $this->components->info("Key written to [{$path}]. Keep a copy in your password manager and never commit it.");

        return self::SUCCESS;
    }
}
