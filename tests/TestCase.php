<?php

declare(strict_types=1);

namespace Ikapelyushny\EnvCipher\Tests;

use Ikapelyushny\EnvCipher\EnvCipherServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [EnvCipherServiceProvider::class];
    }
}
