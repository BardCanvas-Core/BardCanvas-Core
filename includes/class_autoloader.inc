<?php
/**
 * Class autoloader
 * 
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

/**
 * Class autoloader
 *
 * @param string $class_name
 */
function hng2_class_autoloader($class_name)
{
    # Class path: /classes
    # Class name: class_file
    $target_class = str_replace("\\", "/", $class_name);
    $class_file = ROOTPATH . "/classes/$target_class.php";
    if( file_exists($class_file) )
    {
        include_once $class_file;
        
        return;
    }
    
    # Class path: /lib
    # Class name: class_file
    $target_class = str_replace("\\", "/", $class_name);
    $class_file = ROOTPATH . "/lib/$target_class.php";
    if( file_exists($class_file) )
    {
        include_once $class_file;
        
        return;
    }
    
    # Class path: /<module_name>/classes
    # Class name: \hng2_modules\<module_name>\class_file
    $namespace_parts = explode("\\", str_replace("hng2_modules\\", "", $class_name));
    $this_class      = array_pop($namespace_parts);
    $module_name     = array_shift($namespace_parts);
    $class_path      = implode("/", $namespace_parts) . "/$this_class";
    $class_file      = str_replace("//", "/", ROOTPATH . "/$module_name/classes/$class_path.php");
    if( file_exists($class_file) )
    {
        include_once $class_file;
        
        return;
    }
    
    # Class path: /templates/<module_name>/classes
    # Class name: \hng2_templates\<module_name>\class_file
    $namespace_parts = explode("\\", str_replace("hng2_templates\\", "", $class_name));
    $this_class      = array_pop($namespace_parts);
    $template_name   = array_shift($namespace_parts);
    $class_path      = implode("/", $namespace_parts) . "/$this_class";
    $class_file      = str_replace("//", "/", ROOTPATH . "/templates/$template_name/classes/$class_path.php");
    if( file_exists($class_file) )
    {
        include_once $class_file;
        
        return;
    }
}

spl_autoload_register('hng2_class_autoloader');
