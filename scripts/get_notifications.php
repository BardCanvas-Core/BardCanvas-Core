<?php
/**
 * Notifications deliverer
 *
 * @package    HNG2
 * @subpackage Core::public_html
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var module[] $modules
 */

use hng2_base\module;

include "../config.php";
include "../includes/bootstrap.inc";
header("Content-Type: application/json; charset=utf-8");

$start_from = "";
if( ! empty($_REQUEST["last_read"]) )
{
    $parts      = explode("_", $_REQUEST["last_read"]);
    $start_from = $parts[1] . "." . $parts[2];
}

$notifications_return_collection = array(
    "notifications" => array(),
);

if( ! $account->_exists ) die( json_encode($notifications_return_collection) );

$notifications_return_collection["notifications"] = get_notifications($account->id_account);

# Extenders
foreach($modules as $module)
{
    if( ! isset($module->php_includes->notifications_getter) ) continue;
    
    foreach($module->php_includes->notifications_getter as $getter)
    {
        $this_module = $module;
        $include = ROOTPATH . "/{$module->name}/$getter";
        
        if( ! file_exists($include) ) continue;
        
        include $include;
    }
}

echo json_encode($notifications_return_collection);
