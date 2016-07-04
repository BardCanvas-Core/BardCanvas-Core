<?php
/**
 * User logout
 *
 * @package    HNG2
 * @subpackage modules::accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

$_ROOT_URL = "../..";
include "{$_ROOT_URL}/config.php";
include "{$_ROOT_URL}/includes/bootstrap.inc";

$account->close_session();

$go = empty($_REQUEST["go"]) ? "$_ROOT_URL/" : $_REQUEST["go"];
header("Location: " . $go);
