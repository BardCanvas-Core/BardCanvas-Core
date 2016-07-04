<?php
/**
* Modules manager cache purge
* 
* @package    HNG2
* @subpackage Modules::included::modules_manager
* @author     Alejandro Caballero<lava.caballero@gmail.com>
*/

$_ROOT_URL = "..";
include "{$_ROOT_URL}/config.php";
include "{$_ROOT_URL}/includes/bootstrap.inc";
header("Content-Type: text/plain; charset=utf-8");

if( ! $account->_is_admin ) throw_fake_404();

@unlink( "{$config->datafiles_location}/cache/modules.dat" );
include "{$_ROOT_URL}/includes/modules_autoloader.inc";

echo "OK";
