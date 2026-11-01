# Release Notes for Simple Logger

All notable changes to this project will be documented in this file.

## 2.1.0 - 2026.11.01
### Added
- Added support for PHP `^8.2 || ^8.3 || ^8.4 || ^8.5`
- Added support for Monolog 3 (`LogRecord` and `Level` enum compatibility)
- Added PHPStan strict static analysis at Level 8 (`phpstan.neon`)
- Added PSR-12 code style formatting via Laravel Pint
- Added multi-version PHP verification matrix (PHP 8.2, 8.3, 8.4, and 8.5)

### Changed
- Modernized `composer.json` schema (`authors` array, metadata block, dev scripts)
- Enforced strict PHP 8.2+ parameter and return type declarations across all classes
- Modernized Craft 5 API calls (`Craft::$app->getPath()->getLogPath()`, `Craft::$app->getSites()`)

### Fixed
- Fixed undefined `TemporaryException` in `EmailJob::canRetry()`
- Fixed Monolog 3 compatibility in `SimpleHandler::write()` for `LogRecord`
- Fixed unsafe message truncation in `SimpleHandler` when stack trace delimiter is absent
- Fixed implicit nullability and potential `null` pointer exceptions

## 2.0.1 - 2024.04.10
### Added
- Added `RetryableJobInterface` implementation to `EmailJob`

## 2.0.0 - 2024.03.26
Craft 5, PHP 8.2

## 1.0.2 - 2023.03.23
Cleanup, PHP 8.2

## 1.0.1 - 2023.01.27
Implemented a better way to to filter non-critical expections using `NotFoundHttpException`

## 1.0.0 - 2023.01.21
Initial release
