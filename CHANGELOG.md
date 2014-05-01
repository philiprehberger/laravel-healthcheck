# Changelog

All notable changes to `philiprehberger/laravel-healthcheck` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-09

### Added
- Initial release.
- `HealthCheck` contract for implementing custom checks.
- `CheckResult` value object with `ok`, `warning`, and `critical` factory methods.
- `HealthReport` aggregate with overall status resolution and JSON serialisation.
- `HealthService` for registering and running checks with per-check timeout support.
- Built-in checks: `DatabaseCheck`, `CacheCheck`, `StorageCheck`, `RedisCheck`, `QueueCheck`, `EnvironmentCheck`, `HttpCheck`.
- `HealthController` with `/health`, `/health/live`, and `/health/ready` endpoints.
- Kubernetes liveness (`/health/live`) and readiness (`/health/ready`) probes.
- `HealthcheckServiceProvider` with auto-discovery, route registration, and config publishing.
- Result caching via `cache.enabled` config option.
- Configurable route prefix and middleware.
- Full PHPUnit test suite with Orchestra Testbench.
- GitHub Actions CI for PHP 8.2, 8.3, 8.4 against Laravel 11 and 12.
- PHPStan level 8 static analysis.
- Laravel Pint code style enforcement.

[Unreleased]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/philiprehberger/laravel-healthcheck/releases/tag/v1.0.0
