# Changelog
Uses [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) & [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.0] - 2019-02-08
### Added
- Support for slack bot communications, able to add and remove orders (#4).
- Ask Kebabble for assistance and a menu for the active order company.
### Fixed
- Fixed a bug where enabling order pullthrough on an empty install caused an exception.
- Hidden the WordPress taxonomy box on order, since a custom entry was present.

## [0.2.2] - 2018-12-20
(Due to human error, 0.2.1 was skipped).
### Fixed
- Fixed non-numeric tax bug (#17).
- Quality improvements (#18).
- Tax is assigned per person, not per order (#19).

## [0.2.0] - 2018-12-16
### Added
- Fixed-width order form replaced in favour of a JSON-operated field repeater (#9).
- Frequent resturants can be codified to pull in costs of items (#3).
- If above is used, then the cost and tax is calculated and displayed.

## [0.1.8] - 2018-09-16
### Fixed
- Fixed an issue where Windows-based installations would crash upon posting (Issue #16).
- Serious bug where updates to entries would create a whole new entry.

## [0.1.7] - 2018-09-13
### Added
- Driver charge is now flexible per-order (#15).
- Can now be configured to copy side-options from the previous order (#12).

## [0.1.5] - 2018-04-10
### Added
- Global definable driver contribution amount.
### Changed
- Advanced Custom Fields dependency removed.

[0.1.8]: https://gitlab.com/soup-bowl/kebabble/tags/v0.1.8-alpha
[0.1.7]: https://gitlab.com/soup-bowl/kebabble/tags/v0.1.7-alpha
[0.1.5]: https://gitlab.com/soup-bowl/kebabble/tags/v0.1.5-alpha
[0.2.0]: https://gitlab.com/soup-bowl/kebabble/tags/v0.2-alpha
[0.2.2]: https://gitlab.com/soup-bowl/kebabble/tags/v0.2.2-alpha