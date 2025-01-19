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

# Prechecks
$sqli_pchecks = array();
if( isset($_SERVER["HTTP_CF_CONNECTING_IP"]) && ! empty($_SERVER["HTTP_CF_CONNECTING_IP"]) ) $sqli_pchecks[] = $_SERVER["HTTP_CF_CONNECTING_IP"];
if( isset($_SERVER["HTTP_X_FORWARDED_FOR"])  && ! empty($_SERVER["HTTP_X_FORWARDED_FOR"]) )  $sqli_pchecks[] = $_SERVER["HTTP_X_FORWARDED_FOR"];
if( isset($_SERVER["HTTP_USER_AGENT"])       && ! empty($_SERVER["HTTP_USER_AGENT"]) )       $sqli_pchecks[] = $_SERVER["HTTP_USER_AGENT"];
if( isset($_SERVER["HTTP_REFERER"])          && ! empty($_SERVER["HTTP_REFERER"]) )          $sqli_pchecks[] = $_SERVER["HTTP_REFERER"];
if( ! empty($sqli_pchecks) )
{
    try{ check_sql_injection($sqli_pchecks); }
    catch(\Exception $e) { throw_fake_501(); }
}

# Inits
$config       = new config();
$database     = new db_controller();
$mem_cache    = new mem_cache();
$object_cache = new object_cache("system");
$settings     = new settings();
$config->fill_user_levels();
$config->set_metering_toggles();
$config->fill_upload_types();

# Website name theft protection
$hostname     = strtolower($_SERVER["HTTP_HOST"]);
$valid_hosts  = $settings->get_lines("engine.valid_hostnames");
if( $hostname
    && $_SERVER["REMOTE_ADDR"]
    && $_SERVER["REQUEST_METHOD"]
    && $_SERVER["REQUEST_URI"]
    && $_SERVER["SERVER_PROTOCOL"]
    && $_SERVER["DOCUMENT_ROOT"]
    && ! empty($valid_hosts) 
    && ! in_array($hostname, $valid_hosts) )
        throw_fake_403();

# Disguised PHP uploads
if( ! empty($_FILES) && is_array($_FILES) )
{
    $lpatt = '/\.php|\.inc|\.sh|\.pl|\.cgi/i';
    foreach($_FILES as $key => $vals)
    {
        $name = $vals["name"];
        if( is_string($name) && preg_match($lpatt, $name) )
        {
            @unlink($vals["tmp_name"]);
            $lip   = get_user_ip();
            $lhost = @gethostbyaddr($lip);
            $lgeo  = forge_geoip_location($lip);
            $ltime = date("Ymd");
            $lfile = "{$config->logfiles_location}/disguised_php_uploads-{$ltime}.log";
            $ldate = date("Y-m-d H:i:s");
            $luri  = $_SERVER["REQUEST_METHOD"] . " " . $_SERVER["REQUEST_URI"];
            $lagnt = $_SERVER["HTTP_USER_AGENT"];
            $lmsg  = unindent("
                         [$ldate]
                         $lip - $lhost - $lgeo
                         > $name
                         ~ $luri
                         ~ $lagnt
                     ");
            @file_put_contents($lfile, "$lmsg\n\n", FILE_APPEND);
            throw_fake_501();
        }
        else if( is_array($name) )
        {
            foreach($name as $key2 => $val)
            {
                if( is_string($val) && preg_match($lpatt, $val) )
                {
                    @unlink($vals["tmp_name"][$key2]);
                    $lip   = get_user_ip();
                    $lhost = @gethostbyaddr($lip);
                    $lgeo  = forge_geoip_location($lip);
                    $ltime = date("Ymd");
                    $lfile = "{$config->logfiles_location}/disguised_php_uploads-{$ltime}.log";
                    $ldate = date("Y-m-d H:i:s");
                    $luri  = $_SERVER["REQUEST_METHOD"] . " " . $_SERVER["REQUEST_URI"];
                    $lagnt = $_SERVER["HTTP_USER_AGENT"];
                    $lmsg  = unindent("
                                 [$ldate]
                                 $lip - $lhost - $lgeo
                                 > $name
                                 ~ $luri
                                 ~ $lagnt
                             ");
                    @file_put_contents($lfile, "$lmsg\n\n", FILE_APPEND);
                    throw_fake_501();
                }
            }
        }
    }
}

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

if( $settings->get("engine.session_cookie_vars_upgraded_to_v2") != "true" )
{
    $user_online_cookie  = $settings->get("engine.user_online_cookie");
    $user_session_cookie = $settings->get("engine.user_session_cookie");
    $settings->set("engine.user_online_cookie",                 $user_online_cookie  . "_v2");
    $settings->set("engine.user_session_cookie",                $user_session_cookie . "_v2");
    $settings->set("engine.session_cookie_vars_upgraded_to_v2", "true");
}

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
