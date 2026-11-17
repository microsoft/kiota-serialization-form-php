# Changelog

## [2.3.0](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.2.3...microsoft-kiota-serialization-form-v2.3.0) (2026-11-16)


### Bug Fixes

* **serialization-form:** fix zero integer parsing and float validation in FormParseNode ([52d1052](https://github.com/microsoft/kiota-php/commit/52d10524b7e272543515270a669800214e5b5c10))
* **serialization-form:** support equals signs in values and zero payloads in FormParseNodeFactory ([bb99bd3](https://github.com/microsoft/kiota-php/commit/bb99bd32dfe3ebc78e98b274eb6dab6f5e73a4c7))
* **serialization:** handle zero values and preserve delimiters in form and text serialization ([d7ec55c](https://github.com/microsoft/kiota-php/commit/d7ec55c3bae22b55723235e2b4836304d46e7023))
* **serialization:** standardize null scalar serialization, deduplicate bom parsing, and strict count ([1480d6b](https://github.com/microsoft/kiota-php/commit/1480d6bc6306aed543840afb4015ddbdc355f30f))

## [2.2.3](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.2.2...microsoft-kiota-serialization-form-v2.2.3) (2026-11-03)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [2.2.2](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.2.1...microsoft-kiota-serialization-form-v2.2.2) (2026-10-21)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [2.2.1](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.2.0...microsoft-kiota-serialization-form-v2.2.1) (2026-10-08)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [2.2.0](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.1.1...microsoft-kiota-serialization-form-v2.2.0) (2026-10-06)


### Features

* add support for Guzzle 8 ([#110](https://github.com/microsoft/kiota-php/issues/110)) ([fe8f997](https://github.com/microsoft/kiota-php/commit/fe8f9973bdce074234fe4d187b2437207f5874cf))

## [2.1.1](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.1.0...microsoft-kiota-serialization-form-v2.1.1) (2026-08-31)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [2.1.0](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.0.2...microsoft-kiota-serialization-form-v2.1.0) (2026-08-27)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [2.0.2](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.0.1...microsoft-kiota-serialization-form-v2.0.2) (2026-04-20)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [2.0.1](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v2.0.0...microsoft-kiota-serialization-form-v2.0.1) (2026-03-13)


### Bug Fixes

* correct two failing FormParseNode tests ([9e03df9](https://github.com/microsoft/kiota-php/commit/9e03df96b7a5bc20954debd8777e0f2efa1945c0))
* correctly parse 'false' string to bool false in FormParseNode ([df4433b](https://github.com/microsoft/kiota-php/commit/df4433b69b877a029e356998fe2804651d772c97))
* reduce parse node allocations when deserializing primitive types ([24eda87](https://github.com/microsoft/kiota-php/commit/24eda872bcfe883fdfafae44b259f735a03c61c1))


### Performance Improvements

* reduce parse node allocations when deserializing primitive types ([fc07fcb](https://github.com/microsoft/kiota-php/commit/fc07fcbea5511ffecbf35096b4feeca97a1a1242))

## [2.0.0](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v1.5.2...microsoft-kiota-serialization-form-v2.0.0) (2026-02-24)


### ⚠ BREAKING CHANGES

* remove support for php 7.2 through 8.2 to address security issues
* remove support for php 7.2 through 8.2 to address security issues

### Bug Fixes

* drop PHP 7.4 support, raise minimum to PHP 8.2 ([194f577](https://github.com/microsoft/kiota-php/commit/194f577bb7a8f2a0c3c78b2790e2c64dfbfa30cd))
* remove PHP 7.4 support and upgrade minimum to PHP 8.2 ([35160f4](https://github.com/microsoft/kiota-php/commit/35160f41b2c1cf0b29357dc8f4de376edbe77701))
* remove support for php 7.2 through 8.2 to address security issues ([8654976](https://github.com/microsoft/kiota-php/commit/865497645eabd19694b77de82e55360b7708299d))
* remove support for php 7.2 through 8.2 to address security issues ([5ddf7c6](https://github.com/microsoft/kiota-php/commit/5ddf7c6d4d0337cae5dd9d6ab12398c3407de18d))

## [1.5.2](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v1.5.1...microsoft-kiota-serialization-form-v1.5.2) (2025-12-18)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [1.5.1](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v1.5.0...microsoft-kiota-serialization-form-v1.5.1) (2025-10-08)


### Miscellaneous Chores

* **microsoft-kiota-serialization-form:** Synchronize microsoft-kiota-php versions

## [1.5.0](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-form-v1.4.0...microsoft-kiota-serialization-form-v1.5.0) (2025-02-10)


### Features

* add release please configuration to monorepo ([57de3a2](https://github.com/microsoft/kiota-php/commit/57de3a20091d1cd349d3c4b0e840920ac3a57d75))


### Bug Fixes

* removes call to addcslashes in getStringValue() functions ([f7097a1](https://github.com/microsoft/kiota-php/commit/f7097a1e13c71f5fe4246d61dc806ac7300412ea))
* removes call to addcslashes in getStringValue() functions ([64db05d](https://github.com/microsoft/kiota-php/commit/64db05d895bf6e1b09462dbd184665a6e7b3a66f))
* subproject config & CI ([673beef](https://github.com/microsoft/kiota-php/commit/673beef4ae3f99c94a7730bb3810d4a1abdf27d5))
