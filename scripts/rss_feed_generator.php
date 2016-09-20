<?php
/**
 * RSS Feed generator - 
 *
 * @package    HNG2
 * @subpackage Core::public_html
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * $_GET params:
 * @param handle
 * 
 * @var settings $settings
 */

use hng2_base\module;
use hng2_base\settings;
use hng2_rss\channel;

include "../config.php";
include "../includes/bootstrap.inc";

$extenders = array();
$channel   = new channel();
$handle    = trim(stripslashes($_GET["handle"]));
$include   = "";

/** @var module[] $modules */
foreach($modules as $module)
{
    if( ! isset($module->php_includes) ) continue;
    if( ! isset($module->php_includes->rss_feed_generator) ) continue;
    
    $include = ROOTPATH . "/{$module->name}/{$module->php_includes->rss_feed_generator}";
    if( ! is_file($include) ) continue;
    
    if( empty($module->php_includes->rss_feed_generator["handle_pattern"]) ) continue;
    $handle_pattern = trim($module->php_includes->rss_feed_generator["handle_pattern"]);
    $handle_pattern = "#{$handle_pattern}#";
    if( ! preg_match($handle_pattern, $handle, $handle_matches) ) continue;
    
    $current_module = $this_module = $module;
    
    break;
}

if( empty($current_module) || empty($include) ) throw_fake_404();

include $include;

if( $_GET["as_text"] == "true" ) header("Content-Type: text/plain; charset=utf-8");
else                             header("Content-Type: application/xml; charset=utf-8");

$channel->comments[] = "Channel served by: {$settings->get("engine.website_name")}/{$current_module->name}";
$channel->comments[] = "Data handle: $handle";

$header = "";
foreach($channel->comments as $comment) $header .= "<!-- $comment -->\n";

$xml = $channel->export();
$xml = str_replace("<channel>\n", "<channel>\n    $header", $xml);
echo $xml;
