# Changelog
Uses [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) & [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Orders stop at midnight, to prevent people accidentally applying orders to previous days.
- Ability to decide the 'place' indicator from within the Company settings.
- Can cancel orders via Slack, if the cancel comes from the order creator.
- Internationalisation (i18n) support increased. gettext support added (#32).
- Custom message per order (#5).

### Changed
- Orders now stored in seperate meta entities, to improve internal accessibility (not backwards compatible).
- Improved handling of bad requests via the events API.

## [0.3.4] - 2019-09-02
### Added
- Check for PHP 7.2 or higher to avoid runtime errors (#36).
- Stat dashboard widget added, with simple analytics (#14).

### Changed
- Company column shown in admin.
- 'resource' is now 'assets'.
- Tests moved out of src.
- Drivers are now stored seperately, and come with their own payment and tax settings.

### Removed
- 'Pull existing' removed as progress heads more towards Slack orientation.

## [0.3.3] - 2019-08-30
### Added
- The channel is changable for new orders created via WordPress (#33).
- Orders placed via Slack can distinguish between channels.
- Slackbot can be told to change collector (#30).
- Slackbot can now begin orders (#29).

### Changed
- Dependencies have been upgraded.
- Kebabbble order parser split into seperate dependency project.
- 'Driver' changed to 'Collector' (internal references unchanged) (#31).
- Restructured Mention to remove Slack code from main process.
- Emojis now managed by a class.

### Fixed
- A check made for incoming Slack events was configured incorrectly.

### Fixed
- Longer items with a similar name are no longer incorrectly assigned (#38).

## [0.3.2] - 2019-03-10
### Changed
- Menu kebab is now a silhouette SVG instead of a picture.
### Fixed
- Fixed bug where removals stopped working via events (#27).
- Stopped for entries with a Slack reference in events (#28).

## [0.3.1] - 2019-02-19
### Added
- Orders per item are now counted (#21).
- Blank drivers field will now default to the current WordPress user (#20).
### Fixed
- Various coding smells throwing PHP notice messages (#25).

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
[0.3.0]: https://gitlab.com/soup-bowl/kebabble/tags/v0.3-alpha
[0.3.1]: https://gitlab.com/soup-bowl/kebabble/tags/v0.3.1-alpha
[0.3.2]: https://gitlab.com/soup-bowl/kebabble/tags/v0.3.2-alpha
[0.3.3]: https://gitlab.com/soup-bowl/kebabble/tags/v0.3.3-alpha
[0.3.4]: https://gitlab.com/soup-bowl/kebabble/tags/v0.3.4-alpha