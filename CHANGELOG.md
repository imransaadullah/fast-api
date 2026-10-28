# Changelog

## v2.9.0 — 2026-10-28

### Security
- Require `firebase/php-jwt` `^7.0` (was `^6.11`). Fixes CVE-2025-45769 / PKSA-y2cr-5h3j-g3ys
  ("php-jwt contains weak encryption"): php-jwt 7 enforces minimum key sizes.
  **Breaking for weak keys:** HS256 secrets shorter than 32 bytes (HS384 < 48, HS512 < 64)
  and RSA keys under 2048 bits now throw `DomainException: Provided key is too short`.
  Rotate such keys before upgrading — see `docs/token.md`.

### Changed
- `ext-redis` moved from `require` to `suggest`. The rate limiter already falls back to
  file storage when the extension is missing, so installs no longer need
  `config.platform.ext-redis` on hosts without Redis (e.g. cPanel).

### Fixed
- `Token` constructed without a time handler called the private `CustomTime` constructor
  and fataled; it now uses `CustomTime::getInstance()`.
