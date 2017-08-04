<?php
/**
 * Media <embed> generator
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @param file
 * @param width
 * @param height
 */

include "../config.php";
include "../includes/bootstrap.inc";

if( empty($_GET["file"]) ) throw_fake_404();

# Note: for an unknown reason, URL rewrites on video files stopped working on chrome.
# Thus, the /mediaserver rewrite was skipped.
$file  = $config->full_root_url . "/data/uploaded_media/" . stripslashes($_GET["file"]);
$style = "";

$width  = empty($_GET["width"])  ? "" : "width='{$_GET["width"]}'";
$height = empty($_GET["height"]) ? "" : "height='{$_GET["height"]}'";

echo "<div class='video_container'><div class='play_trigger pseudo_link' 
           onclick=\"$(this).closest('.video_container').find('video')[0].play(); $(this).remove();\"></div><video {$width} {$height}
           controls><source src='{$file}' /></video></div>";
