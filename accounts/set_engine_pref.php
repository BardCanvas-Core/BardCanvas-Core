<?php
/**
 * Account engine pref saver
 *
 * @package    HNG2
 * @subpackage modules::accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @param string "key"
 * @param string "value"
 * 
 * @return string "OK" on success | error message
 */

$_ROOT_URL = "..";
include "{$_ROOT_URL}/config.php";
include "{$_ROOT_URL}/includes/bootstrap.inc";

$_current_page_requires_login = true;
if( ! $account->_exists ) die( $language->errors->page_requires_login );

$key = trim(stripslashes($_REQUEST["key"]));
if( empty($key) ) die($current_module->language->errors->prefs_setting->empty_key);

$val = trim(stripslashes($_REQUEST["value"]));
$account->set_engine_pref($key, $val);

echo "OK";
