<?php
/**
 * User login
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @param string "user_name"
 * @param string "password"
 * @param string "redir_url" optional
 * 
 * @returns string
 * 
 * Returning strings:
 * • ERROR_MISSING_PARAMS
 * • ERROR_ACCOUNT_UNEXISTENT
 * • ERROR_ACCOUNT_DISABLED
 * • ERROR_WRONG_PASSWORD
 * • ERROR_ENGINE_DISABLED
 * • ERROR_DEVICE_DISABLED
 * • OK → User name → OK|UNREGISTERED → url
 *   Tab-separated fields: result, user name, device message, admin dashboard url or redir_url
 */

use hng2_base\account;
use hng2_base\device;

include "../../config.php";
include "../../includes/bootstrap.inc";

header("Content-Type: text/plain; charset=utf-8");

#=====================#
# Account validations #
#=====================#

if( trim($_POST["user_name"]) == "" || trim($_POST["password"])  == "" )
    die("ERROR_MISSING_PARAMS");

$account = new account(trim(stripslashes($_POST["user_name"])));
if( ! $account->_exists )
    die("ERROR_ACCOUNT_UNEXISTENT");

if( $account->state != "enabled" )
    die("ERROR_ACCOUNT_DISABLED");

if( md5(trim(stripslashes($_POST["password"]))) != $account->password )
    die("ERROR_WRONG_PASSWORD");

if( $settings->get("engine.enabled") != "true" && ! $account->_is_admin )
    die("ERROR_ENGINE_DISABLED");

#==========================================#
# Session opening and new device detection #
#===========================================#

$device = new device($account->id_account);

if( $settings->get("modules:accounts.enforce_device_registration") != "true" )
{
    if( ! $device->_exists )
    {
        $device->set_new($account);
        $device->state = "enabled";
        $device->save();
        $device_return = "OK";
    }
    else
    {
        $device->ping();
        $device_return = "OK";
    }
}
else # $settings->get("modules:accounts.enforce_device_registration") == "true"
{
    if( ! $device->_exists )
    {
        $device->set_new($account);
        $device->save();
        $device->send_auth_token($account);
        $device_return = "UNREGISTERED";
    }
    else
    {
        switch( $device->state )
        {
            case "disabled":
                die("ERROR_DEVICE_DISABLED");
                break;
            
            case "enabled":
                $device->ping();
                $device_return = "OK";
                break;
            
            case "unregistered":
            default:
                $device->send_auth_token($account);
                $device_return = "UNREGISTERED";
                break;
        }
    }
}

$account->open_session($device);
if( ! empty($_REQUEST["redir_url"]) )
{
    header("Location: " . $_REQUEST["redir_url"]);
    die("<a href='".$_REQUEST["redir_url"]."'>{$language->click_here_to_continue}</a>");
} # end if

$redir_url = empty($_REQUEST["redir_url"]) ? "/" : $_REQUEST["redir_url"];

echo "OK\t{$account->user_name}\t{$device_return}\t{$redir_url}";
