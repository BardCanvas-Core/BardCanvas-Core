
# BardCanvas Core Changelog 

## [1.11.5.6] - 2017-08-19

- Improved styles for nav tables.

## [1.11.5.5] - 2017-08-11

- Added pre-generated links for account and device registration emails
  (they were sent as plain text).

## [1.11.5.4] - 2017-08-08

- Removed suffix from the home page, now it shows the website name only.

## [1.11.5.3] - 2017-08-04

- Tuned TinyMCE defaults:
  - Enabled browser-based spell checking.
  - Removed custom context menu to allow using the browser default context menu and the spell checker.
  - Added table button.
  - Allowed editors to access the source code view.
  - Removed full screen and code view from minimalistic editor.

## [1.11.5.2] - 2017-08-03

- Further style corrections.

## [1.11.5.1] - 2017-08-03

- Changed overlay icon rendering method.

## [1.11.5.0] - 2017-08-03

- Added support for "play" icon overlay on videos.

## [1.11.4.1] - 2017-07-29

- Added class autoloader for templates.
- Minor CSS fixes.

## [1.11.4.0] - 2017-07-26

- Added support for language files on templates.

## [1.11.3.4] - 2017-07-06

- Chaged MIME detection method for video files to avoid warnings.

## [1.11.3.3] - 2017-07-06

- Changed mail method from sendmail to PHP mail() function to avoid errors on highly restricted hosts.

## [1.11.3.2] - 2017-07-06

- Added resetting of SQL mode to avoid global strict warnings.

## [1.11.3.1] - 2017-07-05

- Fixed login helper paths in .htaccess (may require manual edition on deployed websites).
- Showing display errors changed to "On" by default on config-sample.php
- Added styles for responsive grids into TinyMCE.
- Fixed path issue on account validation email.

## [1.11.3.0] - 2017-07-03

- Fixed issue that prevented Gravatar removal on the user account.
- Added responsiveness to settings editors (config/preferences).
- Added support for extra meta tags (to facilitate SEO extenders).
- Fixed issue on memcache purging method.

## [1.11.2.0] - 2017-06-27

- Added support for template previews.
- Switched calls to bardcanvas.com through HTTPS instead of HTTP.

## [1.11.1.1] - 2017-06-26

- Allowed parsing of multiple shortcodes instances on the same source.

## [1.11.1.0] - 2017-06-19

- Added settings for default image.
- Tuned meta tags.
- Tuned AJAX error handler.

## [1.11.0.1] - 2017-06-16

- Changed memcache purging method to avoid issues.

## [1.11.0.0] - 2017-06-16

- Removed memcache probing.
- Added some checks to avoid errors.
- Added caching on missing places.
- Removed rsync as requirement for setup.
- Other minor changes.

## [1.10.0.1] - 2017-06-08

- Updated TinyMCE init script.

## [1.10.0.0] - 2017-06-08

- Added missing check for account interaction in PMs.
- Removed dependency on Memcache.
- Changed ffmpeg detection function to avoid issues.
- Removed ffmpeg requirement.

## [1.9.1.2] - 2017-05-29

- Added support for global messaging blockages.
- Added filtering support on online users list forger.

## [1.9.1.1] - 2017-05-25

- Added restoration of hidden files from the media repository when taken out from the trash.

## [1.9.1.0] - 2017-05-23

- Tuning account classes and added methods to simplfy some areas on the templates.

## [1.9.0.1] - 2017-05-16

- Relocated notifications killer to the top of the notifications list.

## [1.9.0.0] - 2017-05-13

- Tuned notifications killer.
  Note: the container div no longer needs to be present in the template, so it has been deprecated.
- Tuned IP address retriever funciton.
- Added AJAX calls error handler.
- Tuned notifications getter to return an valid empty result when called without a session
  to avoid unneeded AJAX errors popping up.
- Added blockUI elements and functions to support AJAXified upload progress indicators.
- Removed unneeded line from crontab proposition after setup.
- Modified the data key on template class to suit v2 of the templates manager.
  **IMPORTANT:** This will render ALL widgets that require settings obsolete.
  All of them will have to be edited with the new editor.

## [1.8.1.0] - 2017-05-05

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
