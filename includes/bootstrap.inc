<?php
/**
 * Bootstrap file
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_base\account;
use hng2_base\config;
use hng2_cache\object_cache;
use hng2_db\db_controller;
use hng2_base\module;
use hng2_base\settings;
use hng2_base\template;
use hng2_cache\mem_cache;

include dirname(__FILE__) . "/class_autoloader.inc";
include dirname(__FILE__) . "/crypt_functions.inc";
include dirname(__FILE__) . "/text_functions.inc";
include dirname(__FILE__) . "/geoip_functions.inc";
include dirname(__FILE__) . "/web_helper_functions.inc";

# Inits
$config       = new config();
$database     = new db_controller();
$mem_cache    = new mem_cache();
$object_cache = new object_cache("system");
$settings     = new settings();
$config->fill_user_levels();
$config->set_metering_toggles();
$config->fill_upload_types();

# Engine Language loader
$language_cookie_val = $settings->get("engine.default_language");
if( $_COOKIE[$config->language_cookie_var] != $language_cookie_val )
{
    $language_cookie_val = $settings->get("engine.default_language");
    if( empty($language_cookie_val) ) $language_cookie_val = "en_US";
    
    @setcookie($config->language_cookie_var, $language_cookie_val, 0, "/", $config->cookies_domain );
    $_COOKIE[$config->language_cookie_var] = $language_cookie_val;
}
$language = simplexml_load_file( ROOTPATH . "/language/".$_COOKIE[$config->language_cookie_var].".xml" );

# Localization
if( file_exists(ROOTPATH . "/language/".$_COOKIE[$config->language_cookie_var].".inc") )
    include ROOTPATH . "/language/".$_COOKIE[$config->language_cookie_var].".inc";
    
/** @var module $this_module */
$this_module    = null;

/** @var module $current_module */
$current_module = null;

$template  = new template();

/** @var module[] $modules */
$modules = array();
include dirname(__FILE__) . "/modules_autoloader.inc";
load_tinymce_addons();

$template->build_includes();

$account = new account();
$account->load_session();

if( $account->_is_admin && ! empty($_GET["preview_template"]) )
{
    $template = new template($_GET["preview_template"]);
    $template->build_includes();
}
