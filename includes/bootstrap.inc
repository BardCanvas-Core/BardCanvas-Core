<?php
/**
 * Bootstrap file
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use wcms_base\account;
use wcms_base\config;
use wcms_base\db\db_controller;
use wcms_base\module;
use wcms_base\settings;
use wcms_base\template;
use wcms_cache\mem_cache;

include dirname(__FILE__) . "/class_autoloader.inc";
include dirname(__FILE__) . "/crypt_functions.inc";
include dirname(__FILE__) . "/text_functions.inc";
include dirname(__FILE__) . "/geoip_functions.inc";
include dirname(__FILE__) . "/web_helper_functions.inc";

# Inits
$config   = new config();
$database = new db_controller();
$settings = new settings();

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

/** @var module $this_module */
$this_module    = null;

/** @var module $current_module */
$current_module = null;

$template  = new template();
$mem_cache = new mem_cache();

/** @var module[] $modules */
$modules = array();
include dirname(__FILE__) . "/modules_autoloader.inc";

$account = new account();
$account->load_session();
