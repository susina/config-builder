# Changelog

All notable changes to `susina/config-builder` project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

### [1.1] - 2025-05-28

### Added

- Add `replaces` option to inject an external parameter to resolve. Useful, i.e. to pass
  `kernel_dir` or other parameters.
- Add [phpdocumentor](https://phpdoc.org/) to generate the documentation api

### Changed

- Update the documentation

## [1.0.1] - 2024-11-30

### Fixed

- Remove PHP 8.4 deprecations

## [1.0] - 2024-09-16

### Added

- Introduce `susina/param-resolver` library
- Introduce `susina/xml-to-array` library

### Changed

- Bump dependencies
- Update Github Actions
- Update documentation

### Removed

- Remove `.ini` support since it's not suited for complex nested configurations

## [0.4] - 2023-02-16

### Added

- `ConfigBuilder::keepFirstXmlTag` method, to include into the configuration array also the first xml tag

### Changed

- Introduce [Pest](https://www.pest.com) testing tool. Since Pest is built on top of Phpunit, this change doesn't break backward compatibility

### Fixed

- Fixed Github Actions warning by update our workflows dependencies

## [0.3] - 2023-01-07

### Added

- `ConfigBuilder::populateContainer` method, to populate a dependency injection container with the loaded parameters.

## [0.2] - 2021-12-29

### Added

- Support for Symfony 6 libraries

## [0.1] - 2021-12-16

First release: fully functional library.
