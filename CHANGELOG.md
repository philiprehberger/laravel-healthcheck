# Changelog

All notable changes to `philiprehberger/laravel-healthcheck` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.2] - 2026-03-31

### Changed
- Standardize README to 3-badge format with emoji Support section
- Update CI checkout action to v5 for Node.js 24 compatibility
- Add GitHub issue templates, dependabot config, and PR template

## [1.2.1] - 2026-03-23

### Changed
- Add phpstan/extension-installer to require-dev with allow-plugins config
- Remove manual larastan include from phpstan.neon (extension-installer handles it)
- Remove non-standard Features section from README per template guide

## [1.2.0] - 2026-03-22

### Added
- `HealthStatus` backed string enum with `Ok`, `Degraded`, and `Critical` cases
- `CheckResult::degraded()` factory method for degraded status
- `CheckResult::withMetrics(array $metrics): self` for attaching metrics to check results
- `$metrics` array property on `CheckResult`, included in `toArray()` output
- `HealthReport::getMetrics(): array` to aggregate metrics from all checks
- `CheckResult::isDegraded()` boolean helper method
- `CheckResult::STATUS_DEGRADED` constant

### Changed
- `HealthReport::$status` now returns a `HealthStatus` enum instead of a plain string
- `HealthReport` status resolution: Critical if any check is critical, Degraded if any is degraded or warning (but none critical), Ok otherwise
- `HealthReport::toArray()` serializes status via `$status->value` for backward-compatible JSON output

## [1.1.5] - 2026-03-21

### Changed
- Consolidate README and configuration updates from diverged branch

## [1.1.3] - 2026-03-18

### Fixed
- Fix Larastan include path in `phpstan.neon` and package name in `composer.json` (`nunomaduro/larastan` → `larastan/larastan`)

## [1.1.2] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.1.1] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts
- Add Development section to README

## [1.1.0] - 2026-03-13

### Fixed
- `CacheCheck` now cleans up probe key even when cache operations fail (uses `try-finally`)
- `StorageCheck` now cleans up probe file even when content verification fails (uses `try-finally`)

### Added
- 8 new tests covering resource cleanup and failure scenarios for `CacheCheck` and `StorageCheck`

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

[Unreleased]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.2.1...HEAD
[1.2.1]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.1.5...v1.2.0
[1.1.5]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.1.3...v1.1.5
[1.1.3]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.1.2...v1.1.3
[1.1.2]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/philiprehberger/laravel-healthcheck/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/philiprehberger/laravel-healthcheck/releases/tag/v1.0.0
