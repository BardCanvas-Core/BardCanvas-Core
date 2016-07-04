<?php
/**
 * Notifications deleter
 *
 * @package    HNG2
 * @subpackage Core::public_html
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @param string "identifier" id_account/json_file_prefix
 */

$_ROOT_URL = "..";
include "{$_ROOT_URL}/config.php";
include "{$_ROOT_URL}/includes/bootstrap.inc";

header("Content-Type: text/plain; charset=utf-8");

if( ! $account->_exists ) die($language->errors->page_requires_login);

if( empty($_GET["identifier"]) ) die($language->notification_deletion_errors->missing_id);

if( $_GET["identifier"] == "undefined" ) die("OK");

$notification_file = "{$config->datafiles_location}/notifications/{$_GET["identifier"]}.json";

if( stristr($_GET["identifier"], $account->id_account) === false )
    die($language->notification_deletion_errors->belongs_to_other);

if( ! file_exists($notification_file) ) die("OK");

if( ! @unlink($notification_file) )
    die($language->notification_deletion_errors->cant_be_deleted);

die("OK");
