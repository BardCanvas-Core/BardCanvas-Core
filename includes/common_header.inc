<?php
/**
 * Common header for all tempates
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *             
 * @var template $template
 * @var settings $settings
 * @var config   $config
 * @var account  $account
 */

use hng2_base\account;
use hng2_base\config;
use hng2_base\settings;
use hng2_base\template;

$favicon = $settings->get("engine.favicon");
if( empty($favicon) ) $favicon = $template->get("favicon");
if( empty($favicon) ) $favicon = "{$template->url}/media/favicon.ico";
$favicon_extension = strtolower(end(explode(".", $favicon)));
$favicon_type      = $favicon_extension == "ico" ? "image/x-icon" : "image/$favicon_extension";

$jquery_ui_theme = $template->get("jquery_ui_theme");
if( empty($jquery_ui_theme) ) $jquery_ui_theme = "blitzer";

$page_meta_image = $template->page_meta_image;
if( empty($page_meta_image) ) $page_meta_image = $settings->get("engine.meta_default_image");
if( ! empty($page_meta_image) && substr($page_meta_image, 0, 4) != "http" ) $page_meta_image = "{$config->full_root_url}{$page_meta_image}";

if( ! isset($template->page_extra_metas["og:type"]) )
    $template->page_extra_metas["og:type"] = "<meta property='og:type' content='website'>";

$page_title = $template->layout == "home" ? $template->get_page_title(false) : $template->get_page_title();
$og_url     = empty($template->page_meta_url) ? $config->full_root_url : $template->page_meta_url;
$og_aux_url = preg_match('/^http:/i', $og_url) ? preg_replace('/^http:/i', 'https:', $og_url) : preg_replace('/^https:/i', 'http:', $og_url);

if( empty($config->globals["js_core_function_overrides"]) ) $config->globals["js_core_function_overrides"] = (object) array();
?>

    <!-- =================== -->
    <!-- Common header begin -->
    <!-- =================== -->
    
    <!-- Base metas -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type"    content="text/html; charset=utf-8">
    <meta charset="UTF-8">
    
    <title><?= $page_title ?></title>
    <meta name="description"        content="<?= $template->sanitize_meta_var($template->page_meta_description) ?>">
    <meta name="keywords"           content="<?= $template->sanitize_meta_var($template->page_meta_keywords) ?>">
    <meta name="rating"             content="<?= $template->sanitize_meta_var($template->page_meta_rating) ?>">
    <meta name="revisit-after"      content="<?= $template->sanitize_meta_var($template->page_meta_revisit_after) ?>">
    <meta name="robots"             content="<?= $template->sanitize_meta_var($template->page_meta_robots) ?>">
    <meta itemprop="name"           content="<?= $template->sanitize_meta_var($template->get_page_title(false)) ?>">
    <meta itemprop="description"    content="<?= $template->sanitize_meta_var($template->page_meta_description) ?>">
    <? if( ! empty($page_meta_image) ) echo "<meta itemprop='image'          content='{$page_meta_image}'>\n" ?>
    <meta property="og:site_name"   content="<?= $template->sanitize_meta_var($settings->get("engine.website_name")) ?>">
    <meta property="og:title"       content="<?= $template->sanitize_meta_var($template->get_page_title(false)) ?>">
    <meta property="og:description" content="<?= $template->sanitize_meta_var($template->page_meta_description) ?>">
    <meta property="og:url"         content="<?= $og_url ?>">
    <? if( ! empty($page_meta_image) ) echo "<meta property='og:image'       content='{$page_meta_image}'>\n" ?>
    <?= implode("\n    ", $template->page_extra_metas) ?> 
    
    <link rel="icon"          type="<?= $favicon_type ?>" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="<?= $favicon_type ?>" href="<?= $favicon ?>">
    
    <!-- Defaults -->
    <script type="text/javascript">
        var $_LANGUAGE_NAME           = '<?= addslashes($language->info->full_name) ?>';
        var $_LANGUAGE_LOCALE         = '<?= $language->info->locale ?>';
        var $_LANGUAGE_ISO            = '<?= $language->info->iso ?>';
        var $_LANGUAGE_COUNTRY        = '<?= $language->info->country ?>';
        var $_FULL_ROOT_PATH          = '<?= $config->full_root_path ?>';
        var $_COOKIES_DOMAIN          = '<?= $config->cookies_domain ?>';
        var $_PHP_SELF                = '<?= $_SERVER["PHP_SELF"] ?>';
        var $_REQUEST_URI             = '<?= $_SERVER["REQUEST_URI"] ?>';
        var $_CURRENT_USER_ID_ACCOUNT = '<?= $account->id_account ?>';
        var $_CURRENT_USER_LANGUAGE   = '<?= $_COOKIE[$config->language_cookie_var] ?>';
        var $_CURRENT_USER_LEVEL      = parseInt('<?= $account->level ?>');
        var $_CURRENT_USER_IS_ADMIN   = <?= $account->_is_admin ? "true" : "false" ?>;
        var $_CURRENT_USER_IS_MOD     = <?= $account->level >= config::MODERATOR_USER_LEVEL ? "true" : "false" ?>;
        var $_TEMPLATE_URL            = '<?= $template->url ?>';
        var $_TEMPLATE_CSS_VERSION    = '<?= $template->get("css_version") ?>';
        var $_SCRIPTS_VERSION         = '<?= $config->scripts_version ?>';
        var $_ENGINE_VERSION          = '<?= $config->engine_version ?>';
        var $_IS_MOBILE               = <?= is_mobile() ? "true" : "false" ?>;
        var $_GENERIC_CONFIRMATION    = '<?= $language->confirm_action ?>';
        var $_SILENT_NOTIFICATIONS    = '<?= $account->engine_prefs["!core:silent_notifications"] ?>' == 'true';
        var $_AJAX_ERROR_DIALOG_TITLE = '<?= trim($language->errors->ajax->title) ?>';
        var $_AJAX_ERROR_CONTENTS     = '<?= str_replace(array("\r", "\n"), "\\n", addslashes(unindent($language->errors->ajax->info))) ?>';
        
        if( $_TEMPLATE_CSS_VERSION === '' ) $_TEMPLATE_CSS_VERSION = 1;
        
        var $_MAIN_MENU_AUTO_COLLAPSE_WIDTH = parseInt('<?= (int) $settings->get("engine.main_menu_auto_collapse_width", 700) ?>');
        
        var tinymce_full_toolbar     = 'bold italic underline strikethrough | forecolor backcolor | styleselect fontselect fontsizeselect removeformat | undo redo | '
                                     + 'blockquote alignleft aligncenter alignright alignjustify | outdent indent bullist numlist | hr link unlink anchor table ';
        
        var tinymce_standard_toolbar = 'bold italic underline strikethrough | forecolor backcolor | fontsizeselect removeformat | '
                                     + 'blockquote outdent indent | hr link unlink';
        
        var tinymce_minimal_toolbar  = 'bold italic underline strikethrough | forecolor backcolor removeformat | hr link unlink';
        
        var tinymce_default_css_files = [
            $_FULL_ROOT_PATH  + '/media/styles~v' + $_ENGINE_VERSION + 'tmce-17.css',
            $_TEMPLATE_URL    + '/media/styles~v' + $_ENGINE_VERSION + 'tmce-' + $_TEMPLATE_CSS_VERSION + '.css',
            $_TEMPLATE_URL    + '/media/post_styles~v' + $_ENGINE_VERSION + 'tmce-' + $_TEMPLATE_CSS_VERSION + '.css'
        ];
        var tinymce_custom_plugins = [];
        var tinymce_custom_toolbar_buttons = [];
    </script>
    <script type="text/javascript" src="<?= $config->full_root_path ?>/media/defaults~v<?=$config->engine_version?>-5.js"></script>
    
    <!-- jQuery -->
    <script type="text/javascript" src="<?= $config->full_root_path ?>/lib/jquery-1.11.0.min.js"></script>
    <script type="text/javascript" src="<?= $config->full_root_path ?>/lib/jquery-migrate-1.2.1.js"></script>
    <script type="text/javascript" src="<?= $config->full_root_path ?>/lib/jquery.cookie.js"></script>
    <script type="text/javascript" src="<?= $config->full_root_path ?>/lib/jquery.scrollTo.js"></script>
    
    <!-- Clipboard -->
    <script type="text/javascript" src="<?= $config->full_root_path ?>/lib/clipboard.js"></script>
    <script type="text/javascript">
        var $_CLIPBOARD_COPIED = '<?= addslashes($language->clipboard->copied) ?>';
        var $_CLIPBOARD_UNABLE = '<?= addslashes($language->clipboard->unable_to_copy) ?>';
        $(document).ready(function()
        {
            var clipboard = new Clipboard('.clipboard-copy');
            clipboard.on('success', function(e) {
                if(throw_notification)
                    throw_notification($_CLIPBOARD_COPIED, 'info', 1000);
                else if(play_notification_sound)
                    play_notification_sound('question2');
                else
                    alert($_CLIPBOARD_COPIED);
            });
            clipboard.on('error', function(e) {
                var text = e.text; if( ! text ) text = $(e.trigger).attr('data-clipboard-text');
                prompt($_CLIPBOARD_UNABLE, text);
            });
        });
    </script>
    
    <!-- jQuery UI -->
    <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/jquery-ui-themes-1.10.4/<?= $jquery_ui_theme ?>/jquery-ui.css">
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/jquery-ui-1.10.4.custom.min.js"></script>
    <!--suppress JSPotentiallyInvalidConstructorUsage -->
    <script type="text/javascript">
        $(document).ready(function() { $.extend($.ui.dialog.prototype.options, {draggable: false}); });
    </script>
    <?
    $jq_ui_params = $template->get("jquery_ui_extra_options");
    if( ! empty($jq_ui_params) ): ?>
        <!--suppress JSPotentiallyInvalidConstructorUsage -->
        <script type="text/javascript">
            $(document).ready(function() {
                var params = <?= json_encode($jq_ui_params) ?>;
                $.extend($.ui.dialog.prototype.options, params);
            });
        </script>
    <? endif; ?>
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/jquery.ui.touch-punch.min.js"></script>
    
    <!-- UI Additions -->
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/jquery.blockUI.js"></script>
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/jquery.form.min.js"></script>
    <? if( $settings->get("engine.use_latest_fontawesome") == "true" ) $template->set("use_latest_fontawesome", true); 
       if( $template->get("use_latest_fontawesome") ): ?>
        <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/fontawesome-free-6.7.2-web/css/all.min.css">
        <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/fontawesome-free-6.7.2-web/css/v4-shims.min.css">
        <style type="text/css">
            .pull-left  { float: left; }
            .pull-right { float: right; }
        </style>
    <? else: ?>
        <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/font-awesome-4.7.0/css/font-awesome.css">
    <? endif; ?>
    <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/emojione-2.2.5/css/emojione-awesome.css">
    <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/prettyPhoto_compressed_3.1.6/css/prettyPhoto.css">
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/prettyPhoto_compressed_3.1.6/js/jquery.prettyPhoto.js"></script>
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/sprintf.js"></script>
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/ion.sound-3.0.7/js/ion.sound.min~v3.js"></script>
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/jquery.lazyload.js"></script>
    <script type="text/javascript"> $(document).ready(function() { $('.prettyPhoto').prettyPhoto({social_tools: false}); }); </script>
    <script type="text/javascript"> $(document).ready(function() { $('.lazyload').lazyload({effect: 'fadeIn', failure_limit : 99999}); }); </script>
    
    <!-- Core UI stuff -->
    <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/media/styles~v<?=$config->engine_version?>-17.css">
    <link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/media/admin~v<?=$config->engine_version?>-4.css">
    
    <script type="text/javascript"> var $_CORE_FUNCTION_OVERRIDES = JSON.parse('<?= json_encode($config->globals["js_core_function_overrides"]) ?>'); </script>
    <script type="text/javascript"          src="<?= $config->full_root_path ?>/media/functions~v<?=$config->engine_version?>-36.38.js"></script>
    
    <? if( $template->get("include_notification_functions") ): ?>
        <!-- Notifications -->
        <script type="text/javascript"> var notifications_killer_caption = '<?= trim(addslashes($language->dismiss_all_notifications)) ?>'; </script>
        <script type="text/javascript" src="<?= $config->full_root_path ?>/lib/noty-2.3.7/js/noty/packaged/jquery.noty.packaged.min.js"></script>
        <script type="text/javascript" src="<?= $config->full_root_path ?>/lib/noty-2.3.7/js/noty/themes/default.js"></script>
        <script type="text/javascript" src="<?= $config->full_root_path ?>/media/noty_defaults~v<?=$config->engine_version?>.js"></script>
        <script type="text/javascript" src="<?= $config->full_root_path ?>/media/notification_functions~v<?=$config->engine_version?>-21.js"></script>
        <script type="text/javascript"> $.noty.layouts.bottomLeft.css.width = 'auto'; </script>
    <? endif; ?>
    
    <!-- Language-based elements visibility -->
    <style type="text/css">
        *[langsense]:not([lang="<?= $language->info->iso ?>"]) { display: none; }
    </style>
    
    <!-- ================= -->
    <!-- Common header end -->
    <!-- ================= -->
