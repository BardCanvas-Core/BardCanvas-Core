<?php
/**
 * User profile resource deliverer
 *
 * @package    HNG2
 * @subpackage Core::public_html
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @param string "type" avatar|profile_banner
 * @param string "slug" User slug
 *               
 * @var config $config
 */

use hng2_base\account;
use hng2_base\config;

include "../config.php";
include "../includes/bootstrap.inc";

header("Content-Type: text/plain; charset=utf-8");

if( empty($_REQUEST["type"]) ) throw_fake_404();
if( empty($_REQUEST["slug"]) ) throw_fake_404();

if( ! in_array($_REQUEST["type"], array("avatar", "profile_banner")) ) throw_fake_404();

$account = new account($_REQUEST["slug"]);

if( ! $account->_exists ) throw_fake_404();

if( $_REQUEST["type"] == "avatar" )
    $file = "{$config->datafiles_location}/user_avatars/{$account->user_name}/{$account->avatar}";
else
    $file = "{$config->datafiles_location}/user_profile_banners/{$account->user_name}/{$account->profile_banner}";

if( ! is_file($file) ) throw_fake_404();

$size            = filesize($file);
$specs           = getimagesize($file);
$lastModified    = filemtime($file);
$etagFile        = md5_file($file);
$ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE']   : false);
$etagHeader      = (isset($_SERVER['HTTP_IF_NONE_MATCH'])     ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
header("Expires: ".gmdate("D, d M Y H:i:s", $lastModified + 3600)." GMT");
header("Etag: $etagFile");
header("Content-Length: $size");
header('Cache-Control: public, max-age=3600');

if( @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified || $etagHeader == $etagFile )
{
    header("HTTP/1.1 304 Not Modified");
    exit;
}

$fp = fopen($file, "rb");
if( $size && $fp )
{
    header("Content-type: {$specs["mime"]}");
    fpassthru($fp);
    fclose($fp);
    
    exit;
}

throw_fake_404();
