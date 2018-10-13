<?php
/**
 * Website Homepage
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

include "config.php";
include "includes/bootstrap.inc";

$title = $settings->get("engine.website_name");
if( empty($title ) ) $title = $language->home_title;
$template->set_page_title($title);
include "{$template->abspath}/home.php";
