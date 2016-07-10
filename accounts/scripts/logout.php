<?php
/**
 * User logout
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

include "../../config.php";
include "../../includes/bootstrap.inc";

$account->close_session();

$go = empty($_REQUEST["go"]) ? $config->full_root_path : $_REQUEST["go"];
header("Location: " . $go);
