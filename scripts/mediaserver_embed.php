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

if( ! empty($_GET["width"]) )  $style .= "width: {$_GET["width"]}px; ";
if( ! empty($_GET["height"]) ) $style .= "height: {$_GET["height"]}px; ";

echo "<embed src='{$file}' style='{$style}'></embed>";
