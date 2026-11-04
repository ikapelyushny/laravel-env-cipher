# Changelog

All notable changes to this project are documented in this file.

## [WIP]

- `secure_env()` reads `<KEY>_FILE` Docker secrets and decrypts `cipher:` values.
- `EnvCipher`: AES-256-GCM encryption of single `.env` values with a file-based key.
- Commands `env-cipher:key` and `env-cipher:encrypt`.
