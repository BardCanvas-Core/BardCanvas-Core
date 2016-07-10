<?php
/**
 * User devices editor
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_base\device;

include "../config.php";
include "../includes/bootstrap.inc";

if( ! $account->_exists ) throw_fake_401();

if( $_POST["mode"] == "set_label" )
{
    if( trim(stripslashes($_POST["id_device"])) == "" )
        die($current_module->language->devices_nav->ops_messages->empty_id_device);
    
    if( trim(stripslashes($_POST["device_label"])) == "" )
        die($current_module->language->devices_nav->ops_messages->empty_label);
    
    $device = new device(trim(stripslashes($_POST["id_device"])));
    if( ! $device->_exists )
        die($current_module->language->devices_nav->ops_messages->unexistent_device);
    
    if( $device->id_account != $account->id_account )
        die($current_module->language->devices_nav->ops_messages->not_owned);
    
    if( $device->state == "deleted" )
        die($current_module->language->devices_nav->ops_messages->device_deleted);
    
    if( $device->state == "unregistered" )
        die($current_module->language->devices_nav->ops_messages->device_unregistered);
    
    $device->device_label = trim(stripslashes($_POST["device_label"]));
    $device->save();
    
    die("OK");
}

if( $_POST["mode"] == "set_state" )
{
    if( trim(stripslashes($_POST["id_device"])) == "" )
        die($current_module->language->devices_nav->ops_messages->empty_id_device);
    
    $new_state = trim(stripslashes($_POST["state"]));
    if( $new_state != "enabled" && $new_state != "disabled" && $new_state != "deleted" )
        die($current_module->language->devices_nav->ops_messages->invalid_state);
    
    $device = new device(trim(stripslashes($_POST["id_device"])));
    if( ! $device->_exists )
        die($current_module->language->devices_nav->ops_messages->unexistent_device);
    
    if( $device->id_account != $account->id_account )
        die($current_module->language->devices_nav->ops_messages->not_owned);
    
    if( $device->state == "deleted" )
        die($current_module->language->devices_nav->ops_messages->device_deleted);
    
    if( $device->state == "unregistered" )
        die($current_module->language->devices_nav->ops_messages->device_unregistered);
    
    $current_device = new device($account->id_account);
    if( $current_device->id_device == $device->id_device )
        die($current_module->language->devices_nav->ops_messages->cant_change_state_on_current_device);
    
    $device->state = $new_state;
    if( $new_state == "deleted" ) $device->delete();
    else                          $device->save();
    
    die("OK");
}

$template->set_page_title($current_module->language->page_titles->user_devices);
$template->page_contents_include = "user_devices.tpl.inc";
include "{$template->abspath}/main.php";
