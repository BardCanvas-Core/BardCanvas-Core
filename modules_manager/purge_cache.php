<?php
/**
* Modules manager cache purge
* 
* @package    HNG2
* @subpackage modules_manager
* @author     Alejandro Caballero - lava.caballero@gmail.com
*/

include "../config.php";
include "../includes/bootstrap.inc";
header("Content-Type: text/plain; charset=utf-8");

if( ! $account->_is_admin ) throw_fake_404();

@unlink( "{$config->datafiles_location}/cache/modules.dat" );
include ABSPATH . "/includes/modules_autoloader.inc";

echo "OK";
