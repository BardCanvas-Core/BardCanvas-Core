<?php
/**
 * Modules loader
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *             
 * @var module   $module
 * @var module[] $modules
 */

use wcms_base\module;

$module_dirs_pattern = "{$config->document_root_path}/*";
$available_modules   = glob($module_dirs_pattern, GLOB_ONLYDIR);

# Part 1: loading per-se
foreach($available_modules as $this_module_dir)
{
    $module_keyname = basename($this_module_dir);
    $info_file = "$this_module_dir/module_info.xml";
    if( ! file_exists($info_file) ) continue;
    
    $module = new module($info_file);
    if( $module->installed && $module->enabled ) $modules[$module_keyname] = clone $module;
    
    if( ! empty($module->php_includes->bootstrap) )
        include "{$module->abspath}/{$module->php_includes->bootstrap}";
}

# Part 2: extenders
foreach($modules as $extender_keyname => $extender_module)
{
    if( ! is_object($extender_module->extends_to) && ! is_array($extender_module->extends_to) ) continue;
    
    foreach($extender_module->extends_to as $extended_module_keyname => $extended_module_data)
    {
        if( ! isset($modules[$extended_module_keyname]) ) continue;
        
        foreach($extended_module_data as $extended_case_keyname => $extended_case_include)
        {
            /** @var module */
            $extended_module = $modules[$extended_module_keyname];
            if( ! is_array($extended_module->extended_by) )
                $extended_module->extended_by = array();
            
            $extended_module->extended_by[$extender_keyname][$extended_case_keyname] = $extended_case_include;
        }
    }
}

# Part 3: current module
$current_module = null;
$script_dir     = dirname($_SERVER["SCRIPT_FILENAME"]) . "/";
foreach($modules as $module_keyname => $this_module)
{
    $looking_dir = ABSPATH . "/{$module_keyname}/";
    
    # echo "<pre>\$module_keyname := $module_keyname</pre>";
    # echo "<pre>\$script_dir := $script_dir</pre>";
    # echo "<pre>\$looking_dir := $looking_dir</pre>";
    
    if( stristr($script_dir, $looking_dir) !== false )
    {
        # echo "<pre>\$current_module ---> $module_keyname</pre>";
        $current_module = $this_module;
        
        break;
    }
}
