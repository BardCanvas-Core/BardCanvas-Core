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
$language_cookie_val  = "";
$set_account_language = false;
if( ! empty($_REQUEST["lang"]) )
{
    $specified_language = trim(stripslashes($_REQUEST["lang"]));
    if( file_exists(ROOTPATH . "/language/{$specified_language}.xml") )
    {
        $language_cookie_val  = $specified_language;
        @setcookie($config->language_cookie_var, $language_cookie_val, time() + (60*60*24*365), "/", $config->cookies_domain );
        $_COOKIE[$config->language_cookie_var] = $language_cookie_val;
        
        $set_account_language = true;
    }
}
if( empty($language_cookie_val) )
{
    $specified_language = trim(stripslashes($_COOKIE[$config->language_cookie_var]));
    if( file_exists(ROOTPATH . "/language/{$specified_language}.xml") )
        $language_cookie_val = $specified_language;
}
if( empty($language_cookie_val) )
{
    $language_cookie_val = $settings->get("engine.default_language");
    if( empty($language_cookie_val) ) $language_cookie_val = "en_US";
    
    @setcookie($config->language_cookie_var, $language_cookie_val, time() + (60*60*24*365), "/", $config->cookies_domain );
    $_COOKIE[$config->language_cookie_var] = $language_cookie_val;
    
    $set_account_language = true;
}
$language = simplexml_load_file( ROOTPATH . "/language/".$_COOKIE[$config->language_cookie_var].".xml" );
header("Content-language: {$language->info->iso}");

# All languages and locales construction
$all_languages = array();
$all_locales   = array();
foreach( glob(ROOTPATH . "/language/*.xml") as $language_file )
{
    $tmp_lang   = simplexml_load_file( $language_file );
    $tmp_locale = trim($tmp_lang->info->locale);
    
    if( trim($language->info->locale) == $tmp_locale )
        $all_languages[$tmp_locale] = $language;
    else
        $all_languages[$tmp_locale] = $tmp_lang;
    
    $locale_file = str_replace(".xml", ".inc", $language_file);
    if( ! file_exists($locale_file) )
    {
        $all_locales[$tmp_locale] = $tmp_locale;
    }
    else
    {
        include $locale_file;
        $all_locales[$tmp_locale] = setlocale(LC_ALL, 0);
    }
}
unset($tmp_lang, $tmp_locale);

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
foreach($modules as $module)
    if( ! empty($module->php_includes->before_loading_session) )
        include "{$module->abspath}/{$module->php_includes->before_loading_session}";

$account->load_session();

if( $account->_exists )
{
    if( $set_account_language )
    {
        $account->set_engine_pref("!core:user_language", $_COOKIE[$config->language_cookie_var]);
    }
    // else
    // {
    //     $language_cookie_val = $account->engine_prefs["!core:user_language"];
    //     if( empty($language_cookie_val) ) $language_cookie_val = "en_US";
    //    
    //     @setcookie($config->language_cookie_var, $language_cookie_val, time() + (60*60*24*365), "/", $config->cookies_domain );
    //     $_COOKIE[$config->language_cookie_var] = $language_cookie_val;
    // }
}

foreach($modules as $module)
    if( ! empty($module->php_includes->after_loading_session) )
        include "{$module->abspath}/{$module->php_includes->after_loading_session}";

if( $account->_is_admin && ! empty($_GET["preview_template"]) )
{
    $template = new template($_GET["preview_template"]);
    $template->build_includes();
}
