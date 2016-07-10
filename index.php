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

$template->set_page_title($language->home_title);
include "{$template->abspath}/home.php";
