# Changelog

All notable changes to `qwerkon/code-usage` will be documented here.

## [1.0.1] - 2025-01-22
### Fixed
- guard against infinite loops when the dispatcher boots by lazy-loading the tracker dependency instead of resolving it during provider registration.

## [1.0.0] - 2025-01-22
### Added
- initial Laravel telemetry package structure, middleware, dispatcher, buffered tracker, CLI commands and migrations.
