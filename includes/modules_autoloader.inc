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
 * @var settings $settings
 */

use hng2_base\module;
use hng2_base\settings;
use hng2_cache\disk_cache;

$load_modules = true;
$modules_cache_enabled = $settings->get("modules:modules_manager.disable_cache") != "true";
# echo "\$modules_cache_enabled = [$modules_cache_enabled]<br>";
# echo "\$load_modules = [$load_modules]<br>";

if( $modules_cache_enabled )
{
    $modules_cache = new disk_cache("{$config->datafiles_location}/cache/modules.dat");
    if( $modules_cache->loaded )
    {
        $modules = $modules_cache->get_all();
        foreach($modules as &$module) $module->unserialize();
        $load_modules = false;
    }
    
    $always_on_cache = new disk_cache("{$config->datafiles_location}/cache/always_on.dat");
    if( $modules_cache->loaded )
    {
        $files = $always_on_cache->get_all();
        $template->set("always_on_files", $files);
    }
}

if( $load_modules )
{
    $always_on_files = array();
    $module_dirs_pattern = ROOTPATH . "/*";
    $available_modules   = glob($module_dirs_pattern, GLOB_ONLYDIR);
    
    # Part 1: loading per-se
    foreach($available_modules as $this_module_dir)
    {
        $module_keyname = basename($this_module_dir);
        $info_file = "$this_module_dir/module_info.xml";
        if( ! file_exists($info_file) ) continue;
        
        $module = new module($info_file);
        if( $module->installed && $module->enabled ) $modules[$module_keyname] = clone $module;
        
        if( isset($module->template_includes->always_on) )
            foreach($module->template_includes->always_on->file as $file)
                $always_on_files[] = "{$config->full_root_path}/{$module_keyname}/" . trim($file);
    }
    $template->set("always_on_files", $always_on_files);
    
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
    
    # Cache saving
    if( $modules_cache_enabled )
    {
        $caching_modules = array();
        foreach($modules as $key => $module)
        {
            try
            {
                $caching_modules[$key] = $module->serialize();
            }
            catch( \Exception $e )
            {
                echo "Warning: cannot serialize module $key for caching!";
                echo "<pre>" . print_r($module, true) . "</pre>";
                die();
            }
        }
        $modules_cache->prefill($caching_modules);
        unset( $caching_modules );
        
        $always_on_cache->prefill($always_on_files);
        unset( $always_on_files );
    }
}

# Versions hash preload
$module_versions = array($config->engine_version);
foreach($modules as $module)
    $module_versions[] = trim($module->version);
$config->module_versions_hash = md5(implode(", ", $module_versions));

# Bootstraps preload
foreach($modules as $module)
    if( ! empty($module->php_includes->bootstrap) )
        include "{$module->abspath}/{$module->php_includes->bootstrap}";

# Current module forge
$current_module = null;
$script_dir     = dirname($_SERVER["SCRIPT_FILENAME"]) . "/";
foreach($modules as $module_keyname => $this_module)
{
    $looking_dir = ROOTPATH . "/{$module_keyname}/";
    
    if( stristr($script_dir, $looking_dir) !== false )
    {
        $current_module = $this_module;
        
        break;
    }
}
