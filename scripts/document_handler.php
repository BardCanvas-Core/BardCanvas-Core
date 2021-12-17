<?php
/**
 * Document handler - Includes all registered document handlers and lets them stop on hit.
 *
 * @package    HNG2
 * @subpackage Core::public_html
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * $_GET params:
 * @param string $handle
 */

use hng2_base\module;

include "../config.php";
include "../includes/bootstrap.inc";

$handle = trim(stripslashes($_GET["handle"]));
if( preg_match('#[^a-z0-9.:,;_/-]#i', $handle) ) throw_fake_501();

/** @var module[] $modules */
foreach($modules as $module)
{
    if( ! isset($module->php_includes) ) continue;
    if( ! isset($module->php_includes->document_handler) ) continue;
    
    $include = ROOTPATH . "/{$module->name}/{$module->php_includes->document_handler}";
    if( ! is_file($include) ) continue;
    
    $current_module = $this_module = $module;
    include $include;
}

throw_fake_404();
