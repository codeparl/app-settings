# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial release of `schoolpalm/app-settings` — a lightweight settings abstraction package for Laravel applications.
- `SettingsService` contract interface defining the storage abstraction.
- `SettingsManager` with standard API (`get`, `put`, `has`, `forget`, `all`, `flush`) and fluent chaining.
- `SettingsQueryBuilder` fluent builder for expressive settings operations.
- `AppSettings` facade for developer-friendly access.
- `AppSettingsServiceProvider` for Laravel auto-discovery and config publishing.
- Full test suite with Pest and Orchestra Testbench.

