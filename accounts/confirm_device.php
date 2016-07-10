<?php
/**
 * User device confirmation page
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_base\account;
use hng2_base\device;

include "../config.php";
include "../includes/bootstrap.inc";

$id_account = "";
$id_device  = "";
$limit      = "0000-00-00 00:00:00";
$errors     = array();

if( trim($_REQUEST["token"]) == "" )
    $errors[] = $current_module->language->errors->device_authorization->missing_token;

if( count($errors) == 0 )
{
    $token = decrypt( trim(stripslashes($_REQUEST["token"])), $config->encryption_key );
    
    list($id_account, $id_device, $limit) = explode("\t", $token);
    if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        $errors[] = $current_module->language->errors->device_authorization->invalid_token;
}

if( count($errors) == 0 )
{
    if( date("Y-m-d H:i:s") > $limit ) $errors[] = $current_module->language->errors->device_authorization->expired_token;
}

if( count($errors) == 0 )
{
    # Let's check if the account is already activated
    $xaccount = new account($id_account);
    if($xaccount->state != "enabled")
        $errors[] = $current_module->language->errors->device_authorization->account_not_enabled;
}

$row = null;
if( count($errors) == 0 )
{
    # Let's check if the device belongs to the account
    $res = $database->query("select * from account_devices where id_account = '$id_account' and id_device = '$id_device'");
    if( $database->num_rows($res) == 0 )
        $errors[] = $current_module->language->errors->device_authorization->device_not_belongs;
    else
        $row = $database->fetch_object($res);
}

if( count($errors) == 0 )
{
    # Let's check if the device is already enabled
    $xdevice = new device();
    $xdevice->assign_from_object($row);
    if( $xdevice->state == "enabled" )
        $errors[] = $current_module->language->errors->device_authorization->device_already_enabled;
}

if( count($errors) == 0 )
{
    # Let's activate the account
    $xdevice->enable();
}

$_errors = $errors;
$template->set_page_title($current_module->language->page_titles->device_confirmation);
$template->page_contents_include = "confirm_device.tpl.inc";
include "{$template->abspath}/main.php";
