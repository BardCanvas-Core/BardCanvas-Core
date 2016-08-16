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
$settings     = new settings();
$object_cache = new object_cache("system");
$config->fill_user_levels();
$config->set_metering_toggles();
$config->fill_upload_types();

# Engine Language loader
$language_cookie_name = "{$config->website_key}_UL";
if( empty($_COOKIE[$language_cookie_name]) )
{
    $language_cookie_val = $settings->get("engine.default_language");
    if( empty($language_cookie_val) ) $language_cookie_val = "en_US";
    
    @setcookie($language_cookie_name, $language_cookie_val, (86400*365), "/", $config->cookies_domain );
    $_COOKIE[$language_cookie_name] = $language_cookie_val;
}
$language = simplexml_load_file( ABSPATH . "/language/".$_COOKIE[$language_cookie_name].".xml" );

# Localization
if( file_exists(ABSPATH . "/language/".$_COOKIE[$language_cookie_name].".inc") )
    include ABSPATH . "/language/".$_COOKIE[$language_cookie_name].".inc";
    
/** @var module $this_module */
$this_module    = null;

/** @var module $current_module */
$current_module = null;

$template  = new template();
$mem_cache = new mem_cache();

/** @var module[] $modules */
$modules = array();
include dirname(__FILE__) . "/modules_autoloader.inc";
load_tinymce_addons();

$account = new account();
$account->load_session();
