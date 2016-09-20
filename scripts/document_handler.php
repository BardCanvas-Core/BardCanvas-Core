<?php
/**
 * Document handler - Includes all registered document handlers and lets them stop on hit.
 *
 * @package    HNG2
 * @subpackage Core::public_html
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * $_GET params:
 * @param handle
 */

use hng2_base\module;

include "../config.php";
include "../includes/bootstrap.inc";

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
