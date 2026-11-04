<?php

declare(strict_types=1);

use Ikapelyushny\EnvCipher\SecureEnv;

if (!function_exists('secure_env')) {
    /**
     * Like env(), but reads "<KEY>_FILE" (Docker secret) first and decrypts "cipher:" values.
     */
    function secure_env(string $key, mixed $default = null): mixed
    {
        return SecureEnv::get($key, $default);
    }
}
