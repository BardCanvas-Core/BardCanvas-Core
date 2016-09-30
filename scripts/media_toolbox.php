<?php
/**
 * Media toolbox
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @param method
 */

include "../config.php";
include "../includes/bootstrap.inc";

if( empty($_GET["method"]) ) throw_fake_404();

$method = $_GET["method"];

if( $method == "render_blank_thumbnail" )
{
    $cached = $mem_cache->get("media_toolbox:{$method}_v3_image");
    if( ! empty($cached) )
    {
        $contents = base64_decode($cached);
        $time     = $mem_cache->get("media_toolbox:{$method}_v3_mtime");
    }
    else
    {
        $dimensions = $settings->get("engine.thumbnail_size");
        if( empty($dimensions) ) $dimensions = "460x220";
        
        list($width, $height) = explode("x", $dimensions);
        $img = imagecreatetruecolor($width, $height);
        
        imagesavealpha($img, true);
        $color = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $color);
        imagepng($img, "/tmp/blank_thumbnail.png");
        $contents = file_get_contents("/tmp/blank_thumbnail.png");
        @unlink( "/tmp/blank_thumbnail.png" );
        $time = time();
        $mem_cache->set("media_toolbox:{$method}_v3_image", base64_encode($contents), 0, 60 * 60);
        $mem_cache->set("media_toolbox:{$method}_v3_mtime", $time                   , 0, 60 * 60);
    }
    
    $lastModified    = $time;
    $etagFile        = md5($contents);
    $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE']   : false);
    $etagHeader      = (isset($_SERVER['HTTP_IF_NONE_MATCH'])     ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
    
    header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
    header("Etag: $etagFile");
    header('Cache-Control: public');
    
    if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])==$lastModified || $etagHeader == $etagFile)
    {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }
    
    header("Content-type: image/png");
    echo $contents;
    
    exit;
}

echo "No mehtod provided";
