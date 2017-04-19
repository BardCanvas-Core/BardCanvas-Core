
# BardCanvas Core Changelog 

## [1.7.1.1] - 2017-04-19

- Changed mail sender override method on send_mail web helper function.

## [1.7.1.0] - 2017-04-13

- Added a check on the media repository to prevent empty errors being thrown by internal calls.
- Added batch mode for settings saves.
- Added a helper function for text replacements.
- Changed class instantiations on the bootstrap to avoid fatal errors.

## [1.7.0.1] - 2017-04-10

- Added memory cache helper for disk cache saves to avoid massive hangups on clustered setups.
- Added a clarification in doc for send_mail function in web_helper_functions.inc.

## [1.7.0.0] - 2017-04-08

- Added complementary headers to the user profile resource delivering script.
- Adjustments on crontab template for setup.
- Added globally available shortcode functions.
- Added shortcode_handlers node in modules class to have a unified shortcodes registry.

## [1.6.40.10] - 2017-04-03

- classes/mem_cache: Added support for raw keys.
- classes/internals: Added values of memcached keys.

## [1.6.40.9] - 2017-03-28

- Added changelog.
- Added https://github.com/erusev/parsedown to libraries.
- Refactored readme and license files.

## [1.6.40.8] - 2017-03-27

- Mediaserver fix: added direct URL to files served under /mediaserver URL rewrites since videos stopped working on chrome.

## [1.6.40.7] - 2017-03-27

- Web Helper Functions fix: added error suppressor on notifications sender to avoid errors on automatic updates.
- GeoIP functions fix: fixed error hint.
