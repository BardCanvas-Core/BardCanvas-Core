
# BardCanvas Core Changelog 

## [1.14.9.11] - 2022-11-03

- Increased session token expiration time.

## [1.14.9.10] - 2022-10-26

- Fixed path issue in the abstract video manager constructor.

## [1.14.9.9] - 2022-10-17

- Optimized memory probing on the memory exhausting protection snippet.

## [1.14.9.8] - 2022-10-17

- Added memory exhausting protection on the database class.
- Fixed an issue in session management that caused excessive entries in the logins table.

## [1.14.9.7] - 2022-10-16

- Tuned session management.

## [1.14.9.6] - 2022-10-14

- Added error 500 thrower.

## [1.14.9.5] - 2022-10-12

- Tuned SQL injection patterns.

## [1.14.9.4] - 2022-10-02

- Patterns tunning on the scripts injection checker.

## [1.14.9.3] - 2022-09-28

- Fixed online session cookie behavior that didn't recognize saved data.

## [1.14.9.2] - 2022-09-28

- Added treatment of empty values on system encryption/decryption functions.
- Added cleanup of previous session vars in memcache during session renewal.
- Added missing decryption of session cookies on logout.

## [1.14.9.1] - 2022-09-28

- Improved device management in session internals.

## [1.14.9.0] - 2022-09-28

- Strengthened session control.

## [1.14.8.7] - 2022-09-27

- Fixed params issue in encryption function.

## [1.14.8.6] - 2022-09-26

- Refactored session control to allow usage without memcached.

## [1.14.8.5] - 2022-09-24

- Added setup checks to avoid throwing warnings when invoked after initial run.
- Added web helper function to detect script injections.
- Added CSRF treatment methods to the account class.
- Added sanitization to the device class constructor.
- Added HTTPonly flag to user session cookies.

## [1.14.8.4] - 2022-09-20

- Switched default encryption cypher.
- Added tokenized cookies for session control.

## [1.14.8.3] - 2022-07-01

- Some adjustments to `mem_cache` class.

## [1.14.8.2] - 2022-07-01

- Added flag for error processing on the database controller.

## [1.14.8.1] - 2022-05-13

- Added option to include password on account creation email.

## [1.14.8.0] - 2022-03-16

- Added support for IPinfo.io
- Refactored IP Geolocation functions.

## [1.14.7.21] - 2022-02-24

- Added support for custom system encryption key.

## [1.14.7.20] - 2022-02-01

- Left strict IP change checks to admin accounts only.

## [1.14.7.19] - 2022-01-16

- Tuned the IP detection algorithm.

## [1.14.7.18] - 2022-01-12

- Added pre-bootstrap checks.
- Added checks to prevent warnings on pre-bootstrap checks.

## [1.14.7.17] - 2022-01-10

- Tuned the IP detection algorithm.

## [1.14.7.16] - 2022-01-10

- Tuned the IP detection algorithm.

## [1.14.7.15] - 2022-01-08

- Added SLQ injection prechecks on the bootstrap.
- Tuned protection of data directory.
- Added protection to logs directory.

## [1.14.7.14] - 2022-01-07

- Tuned the IP detection algorithm.

## [1.14.7.13] - 2022-01-07

- Added updatable keys to the engine prefs saver on the account toolbox.

## [1.14.7.12] - 2022-01-05

- Tuned IP change checks on the `account` class.

## [1.14.7.11] - 2021-12-31

- Enhanced IP detection algorithm.

## [1.14.7.10] - 2021-12-27

- Tuned SQL injection patterns.
- Added extension points on the DB controller.
- Added SQL injection check over cookies on the modules loader.

## [1.14.7.9] - 2021-12-18

- Tuned SQL injection patterns.

## [1.14.7.8] - 2021-12-17

- Tuned filtering on the document handler.

## [1.14.7.7] - 2021-12-17

- Input sanitization on the document handler.
- Added debug info on the DB controller error handler.

## [1.14.7.6] - 2021-12-15

- Input sanitization on the `find` method on the abstract repository class.

## [1.14.7.5] - 2021-12-15

- Added checks to the user IP getter.

## [1.14.7.4] - 2021-12-15

- Added debug info to DB errors log.
- Tuned SQL injection patterns.
- Added checks to the user IP getter.

## [1.14.7.3] - 2021-12-13

- Tuned SQL injection patterns.

## [1.14.7.2] - 2021-12-11

- Tuned IP tracking checks.

## [1.14.7.1] - 2021-12-10

- Added check for missing `hash_equals` function.

## [1.14.7.0] - 2021-12-10

- Added support for encrypted settings.
- Changed encryption method for session cookies.
- Added option for agressive IP tracking.

## [1.14.6.5] - 2021-11-23

- Added extra connection info to the db errors log.

## [1.14.6.4] - 2021-11-18

- Added condition to dodge extensions on the SQL injection checker.

## [1.14.6.3] - 2021-11-17

- Added connection info to the db errors log.
- Added extension point to the sql injection checker.

## [1.14.6.2] - 2021-11-16

- Added quotes management on the `set_engine_pref` method of the account toolbox.

## [1.14.6.1] - 2021-10-09

- Removed type hinting on session cookie extender method of account function to avoid errors.

## [1.14.6.0] - 2021-09-29

- Added restricted engine prefs collection to `config` class and helper method.
- Added db errors log and simplified thrown exceptions.

## [1.14.5.7] - 2021-09-24

- Added sanitization of user agents for device strings.

## [1.14.5.6] - 2021-09-22

- Added logging of new devices.
- Added logging of sent emails.
- Added changelog addition method to the accounts repository.
- Improved caching on the account class.

## [1.14.5.5] - 2021-09-19

- Added checks to the SQL injection checker.

## 1.14.5.4 - 2021-09-13

- Added fallback to avoid duplicate tags exception.

## [1.14.5.3] - 2021-08-29

- Added extension points on the account class.

## [1.14.5.2] - 2021-08-17

- Added extra check for IP logging dismissal in the `account` class.

## [1.14.5.1] - 2021-08-04

- Added extensible jQuery UI dialog defaults via template vars.
- Added extension point on the notifications getter script.

## [1.14.5.0] - 2021-08-01

- Tuned submenu widths and positioning.
- Added JS core function override option for allowing submenus to go by hovering the trigger on the main menu.
- Added option to disable the mail sender.
- Fixed unresponsive dialogs close button on the dialogs title bar.

## [1.14.4.4] - 2021-07-27

- Added JS core function override options for controlling the behavior of submenus on the main menu.

## [1.14.4.3] - 2021-07-20

- Added sanitization when setting a numeric value in an engine pref.

## [1.14.4.2] - 2021-04-20

- Tuned CLI color scheme.

## [1.14.4.1] - 2021-02-27

- Tuned empty notifications handling.

## [1.14.4.0] - 2021-02-11

- Added option to skip versioning in the disk_cache class.
- Tuned data directory access restrictions file (.htaccess).
- Added 2FA support functions to account class.
- Minor cleanup and code doc additions.
- Extended account session cookie from 7 to 30 days.

## [1.14.3.13] - 2021-01-25

- Enforced conversion of MP4 and M4V files.
- Added arguments to force FFMPEG to encode videos using the h.264 codec.
- Added logging of video conversions.
- Added auto close param to notifications thrower.
- Replaced clipboard copy alert with auto-closing notification or sound when available.

## [1.14.3.12] - 2021-01-14

- Added method to the settings helper class.
- Extended CLI helper to output to an extra file if specified.
- Limited the notifications getter to fetch only the last 100.

## [1.14.3.11] - 2020-08-27

- Bumped ion sound version.

## [1.14.3.10] - 2020-08-08

- Improved SQL injection checker.
- Added excerpt builder warning fallback.
- Added a fallback for trailing zeroes trimming function warnings. 

## [1.14.3.9] - 2020-05-16

- Added fallback to account login method to avoid fatal error.

## [1.14.3.8] - 2020-05-05

- Added meta section to module class.

## [1.14.3.7] - 2020-04-23

- Added notification sounds.

## [1.14.3.6] - 2020-04-20

- Tuned IP detection function.
- Added preset of possibly missing variables to .htaccess sample.

## [1.14.3.5] - 2020-04-07

- Tuned IP detection algorithm.

## [1.14.3.4] - 2020-04-04

- Added bot detection function to the web helpers.

## [1.14.3.3] - 2020-03-17

- Added extended UTF trimming support on the excerpts maker.
- Added SQL injection check on the records browser helper.
- Added headers_sent check to avoid warning when the records browser is called within a page.
- Changed encryption functions to begin taking out mcrypt's RIJNDAEL_256.

## [1.14.3.2] - 2020-02-05

- Moved SQL injection from bootstrap to web helper function.

## [1.14.3.1] - 2020-01-09

- Tuned SQL injection check.

## [1.14.3.0] - 2020-01-09

- Added error 501 thrower to the web helpers.
- Added SQL injection check on the bootstrap.

## [1.14.2.1] - 2019-10-15

- Added check to avoid warnings on in-page rendering
- Coding style fixes

## [1.14.2.0] - 2019-10-15

- Tuned the records browser class.

## [1.14.2.0] - 2019-10-08

- Tuned comments generation.
- Added capability for full contents output instead of excerpts.

## [1.14.1.0] - 2019-08-03

- Tuned notifications.

## [1.14.0.3] - 2019-07-30

- Edited robots.txt.
- Added optional requirements to setup script.

## [1.14.0.2] - 2019-07-29

- Added check to avoid image forging errors on the graphics management functions file.

## [1.14.0.1] - 2019-07-22

- Rearranged reCAPTCHA keys on the settings editor.

## [1.14.0.0] - 2019-06-07

- Added IP privacy settings (user-level based).

## [1.13.3.0] - 2019-05-20

- Added slug word separator selection.
- Removed conversion function on cli to HTML exporter.

## [1.13.2.6] - 2019-04-25

- Tuned hashtags autolinking function on the web helper.
- Added missing year on time_today_string helper function for old dates.

## [1.13.2.5] - 2019-03-31

- Added missing treatment in submenu triggers initialization.

## [1.13.2.4] - 2019-03-19

- Tuned session closing function.

## [1.13.2.3] - 2019-02-06

- Added *system* shortcodes:
  - `fa` to show a Font Awesome icon, E.G. `[fa icon="info-circle"]`
  - `framed_content`, `framed_div` to show a framed content div with optional state style,
    E.G. `[framed_content state="highlight"]Text[/framed_content]`
  - `framed_span` similar to the previous one, but in a `span` instead of a `div`
  - `content_frame` to show a framed content div styled according to the actual template,
    E.G. `[content_frame]Some text [/content_frame]`
  - `div`, `p` and `span` to show contents wrapped with any of these tags with custom classes and styles,
    E.G. `[span style="color: blue; font-family: 'Times New Roman, Times, serif'"]Some text[/span]"`
- Tuned excerpt maker.

## [1.13.2.2] - 2019-01-29

- Addition to text functions.

## [1.13.2.1] - 2019-01-21

- Tuned JS core functions.

## [1.13.2.0] - 2018-12-16

- Tuned modules & editable prefs caches.

## [1.13.1.0] - 2018-12-11

- Added JS function overriding flags on the common header.
- Tuned global AJAX error handler.
- Added support for JSON formatted RSS feeds.
- Re-reworked URL externalizer on the web helper functions due to transcoding issues.

## [1.13.0.5] - 2018-11-22

- Tuning of body attributes related to starting window dimensions.
- Added saving of homepage URL on account creation.
- Added memcache check to avoid unwanted warnings.

## [1.13.0.4] - 2018-10-17

- Reworked URL externalizer on the web helper functions.

## [1.13.0.3] - 2018-10-13

- Page title tuning.

## [1.13.0.2] - 2018-09-28

- Added extension points on the account class.
- Added page favicon support directly into the settings editor.
- Added encryption algorithm to help migrating from Rijndael algorithm.

## [1.13.0.1] - 2018-09-07

- Fixed exception caused by registering devices where country names had quotes.

## [1.13.0.0] - 2018-08-04

- Refactored user language setting.
- Added support for multi-language files on modules loader.

## [1.12.1.0] - 2018-03-16

- Added extension point on the web helper's mail sender.
- Added a language pre-setter on the bootstrap.

## [1.12.0.4] - 2018-03-16

- Added methods to the record browsers helper.

## [1.12.0.3] - 2018-02-21

- Fixed issue in unique id maker that caused issues in fast consecutive calls.

## [1.12.0.2] - 2018-01-31

- Rolled back previous addition due to conflicts with Facebook scrappers.

## [1.12.0.1] - 2018-01-31

- Added extra og:url tag on the header to improve SEO.

## [1.12.0.0] - 2017-12-14

- Removed website name suffix from `og:title` meta tag.
- Added check to avoid throwing empty notifications.
- Added extension point after loading user session.
- Changed ajax error alerts for notifications to avoid locking up the browser.
- Added helper method to the template class.
- Added directives to TinyMCE styles.

## [1.11.6.0] - 2017-11-18

- Fixed wrong logic on URL forging of the module class.
- Added extension point before loading user session.
- Tuned filename sanitization function.
- Added support to paste data images in TinyMCE.

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
