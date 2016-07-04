<?php
/**
 * Website Homepage
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

$_ROOT_URL = ".";
include "{$_ROOT_URL}/config.php";
include "{$_ROOT_URL}/includes/bootstrap.inc";

$template->set_page_title($language->home_title);
include "{$template->abspath}/home.php";
