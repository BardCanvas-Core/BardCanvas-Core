<?php
/**
 * Mail tester - for admins only
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

include "../config.php";
include "../includes/bootstrap.inc";
header("Content-Type: text/plain; charset=utf-8");

if( ! $account->_is_admin ) die("Sorry, this script is for admins only.");
if( empty($_GET["to"]) ) die("Usage: test_mail.php?to=recipient_address");
if( ! filter_var($_GET["to"], FILTER_VALIDATE_EMAIL) ) die("Please provide a valid email address.");

$config->globals["phpmailer_debug_mode_enabled"] = true;
send_mail(
    "Test mail",
    "<p>This is a test mail.</p>",
    array($_GET["to"])
);
