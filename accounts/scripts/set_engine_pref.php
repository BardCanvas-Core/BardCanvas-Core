<?php
/**
 * Account engine pref saver
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @param string "key"
 * @param string "value"
 * 
 * @return string "OK" on success | error message
 */

include "../../config.php";
include "../../includes/bootstrap.inc";

$_current_page_requires_login = true;
if( ! $account->_exists ) die( $language->errors->page_requires_login );

$key = trim(stripslashes($_REQUEST["key"]));
if( empty($key) ) die($current_module->language->errors->prefs_setting->empty_key);

$val = trim(stripslashes($_REQUEST["value"]));
$account->set_engine_pref($key, $val);

echo "OK";
