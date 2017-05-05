
# BardCanvas Core Changelog 

# [1.8.1.0] - 2017-05-05

- Replaced Font Awesome with version 4.7.0 on the common header.
- Added support to prevent blocking ajax record browsers (when refreshing) on JS global functions.

## [1.8.0.0] - 2017-05-01

- Improved "discardable" and "ajax" temporary dialogs.
- CSS fixes to pseudo dialog.
- Limited notifications area to avoid them surpassing the display height.
- Minor adjustments to language files.
- Detached libraries to a different package to reduce the size of the core.
- Updated setup file to check against the lib directory.

## [1.7.4.1] - 2017-04-29

- Added common header (to be removed from all templates).
- Fadeout timing adjustment for notifications killer.

## [1.7.4.0] - 2017-04-28

- Added jQuery Table Sorter 2.0.5b to lib.
- Added CSS pseudo dialog helper in media directory.
- Added possibility of title overriding on sidebar widgets.
- Updated setup to remove a deprecated argument from the updates checker on the crontab proposal.
- Tuned notifications killer.

## [1.7.3.0] - 2017-04-23

- Added .htaccess to data dir.
- Added robots.txt to avoid indexing searches.
- Fixed issues in meta tag language specs.
- Added meta tags treatment on the template class.

## [1.7.2.0] - 2017-04-21

- Added support for one-click on-screen notifications dismissal.
- Fixed wrong tag in RSS feeds builder.

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
