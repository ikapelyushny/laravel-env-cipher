# laravel-env-cipher

Keep secrets in `.env` encrypted and read Docker secret files from Laravel config.

- Values look like `cipher:<payload>` (AES-256-GCM via `Illuminate\Encryption\Encrypter`).
- The key lives outside the project, by default in the Docker secret `/run/secrets/env_cipher_key`.
- A leaked `.env` is useless without the key file.

## Installation

The package is not on Packagist yet and has no tagged release. Install it from GitHub:

```bash
composer config repositories.env-cipher vcs https://github.com/ikapelyushny/laravel-env-cipher
composer require ikapelyushny/laravel-env-cipher:dev-develop
```

## Usage in config files

Replace `env()` with `secure_env()` for secrets:

```php
'key' => secure_env('APP_KEY'),
'password' => secure_env('DB_PASSWORD', ''),
```

`secure_env($key, $default)` resolves a value in this order:

1. If `<KEY>_FILE` is set, it returns the contents of that file without the trailing newline (Docker secrets). Only a trailing `\r\n` is stripped; the rest is returned byte-exact.
2. Otherwise, if `<KEY>` starts with `cipher:`, it decrypts the value with the key from `ENV_CIPHER_KEY_FILE` (default `/run/secrets/env_cipher_key`).
3. Otherwise it returns the value as is, so plain values keep working in local development.

Errors name the variable or file, never the value.

## Commands

```bash
# Generate a key into a file with mode 0600 (the key is not printed)
php artisan env-cipher:key /path/to/env_cipher_key

# Encrypt a value (hidden input), prints cipher:...
php artisan env-cipher:encrypt --key-file=/path/to/env_cipher_key
```

`env-cipher:key` refuses to write to a directory and to overwrite an existing file. With `--force` it removes the old file and writes a new one with mode 0600.

`env-cipher:encrypt` is interactive:

- It loads the key first, then asks for the value with hidden input. If the input cannot be hidden, it fails instead of falling back to visible input.
- Copy the `cipher:...` line from the terminal. Do not redirect or pipe its stdout: the prompt is written to stdout too.
- Leading and trailing spaces in the typed value are trimmed.

## Notes

- Do not run `php artisan config:cache` in production: the cache file would contain decrypted values.
- Keep a copy of the key in a password manager. Losing it means re-encrypting every value.

### Returned values

- `<KEY>_FILE` contents are never decrypted. This is deliberate: secret files are shared with non-PHP containers such as Postgres.
- File contents and decrypted `cipher:` values are returned verbatim and skip the conversions `env()` applies. Do not encrypt booleans or null: `APP_DEBUG=cipher:...` holding `false` yields the string `"false"`, which is truthy.
- Plain (unencrypted) values still get the usual `env()` conversions.

### Key file and rotation

- A `cipher:` value needs the key file at boot. If you do not want a key in local development, use plain values there.
- To rotate the key, re-encrypt the values with the new key, then restart long-running processes (Octane, queue workers, Horizon). They keep resolved config and loaded keys in memory.

## License

MIT
