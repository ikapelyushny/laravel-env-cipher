<?php

declare(strict_types=1);

namespace Ikapelyushny\EnvCipher;

use Ikapelyushny\EnvCipher\Console\EncryptCommand;
use Ikapelyushny\EnvCipher\Console\GenerateKeyCommand;
use Illuminate\Support\ServiceProvider;

final class EnvCipherServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([GenerateKeyCommand::class, EncryptCommand::class]);
        }
    }
}
