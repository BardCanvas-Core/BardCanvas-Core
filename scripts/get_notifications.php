<?php
/**
 * Notifications deliverer
 *
 * @package    HNG2
 * @subpackage Core::public_html
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_base\module;

$_ROOT_URL = "..";
include "{$_ROOT_URL}/config.php";
include "{$_ROOT_URL}/includes/bootstrap.inc";

if( ! $account->_exists ) throw_fake_401();

$start_from = "";
if( ! empty($_REQUEST["last_read"]) )
{
    $parts      = explode("_", $_REQUEST["last_read"]);
    $start_from = $parts[1] . "." . $parts[2];
}

$notifications_return_collection = array(
    "notifications" => array(),
);

$notifications_return_collection["notifications"] = get_notifications($account->id_account);

# Extensions
/** @var module[] $modules */
foreach($modules as $module)
{
    if( ! isset($module->extends_to->_base_system_->notifications_getter->return_additions) ) continue;
    
    foreach($module->extends_to->_base_system_->notifications_getter->return_additions as $addition)
    {
        $this_module = $module;
        $include = "{$_ROOT_URL}/{$module->name}/$addition";
        
        if( ! file_exists($include) ) continue;
        
        include $include;
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($notifications_return_collection);
