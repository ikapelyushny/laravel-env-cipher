<?php

declare(strict_types=1);

use Ikapelyushny\EnvCipher\SecureEnv;
use Ikapelyushny\EnvCipher\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * Runs the callback with temporary $_SERVER variables and restores them afterwards.
 *
 * @param  array<string, string>  $variables
 */
function withEnv(array $variables, Closure $callback): mixed
{
    $backup = [];

    foreach ($variables as $name => $value) {
        $backup[$name] = $_SERVER[$name] ?? null;
        $_SERVER[$name] = $value;
    }

    try {
        return $callback();
    } finally {
        foreach ($backup as $name => $value) {
            if ($value === null) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $value;
            }
        }

        SecureEnv::flush();
    }
}

/** Creates a temporary file with the given contents and returns its path. */
function tempFile(string $contents): string
{
    $path = sys_get_temp_dir() . '/env-cipher-' . bin2hex(random_bytes(8));
    file_put_contents($path, $contents);

    return $path;
}
