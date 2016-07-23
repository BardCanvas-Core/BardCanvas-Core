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

$file  = $config->full_root_url . "/mediaserver/" . stripslashes($_GET["file"]);
$style = "";

$width  = empty($_GET["width"])  ? "" : "width='{$_GET["width"]}'";
$height = empty($_GET["height"]) ? "" : "height='{$_GET["height"]}'";

echo "<video {$width} {$height} controls><source src='{$file}'></video>";
