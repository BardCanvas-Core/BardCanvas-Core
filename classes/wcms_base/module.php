<?php
/**
 * Module class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace wcms_base;

class module
{
    # Base settings
    var $name;
    var $version;
    var $abspath;
    var $working_flags;
    var $group;
    
    # Functionality flags
    var $installed = false;
    var $enabled   = false;
    
    # Access control
    var $required_level = 0;
    var $admin_only;
    
    /**
     * Array with items to show in the admin menu
     *
     * @var \SimpleXMLElement
     */
    var $menu_items;
    
    /**
     * Processing includes
     *
     * @var \SimpleXMLElement
     */
    var $php_includes = null;
    
    /**
     * Content includes
     *
     * @var \SimpleXMLElement
     */
    var $template_includes = null;
    
    /**
     * @var \SimpleXMLElement
     */
    var $language;
    
    # Information to show in the modules manager on how this module can be extended.
    var $extension_areas_info;
    
    # Modules it extends
    var $extends_to;
    
    # Extended by modules.
    var $extended_by;
    
    # Widget definitions
    var $widgets;
    
    /**
     * Module template
     *
     * @param string $module_info_file
     */
    function __construct($module_info_file)
    {
        global $config, $language, $settings;
        session_start();
        
        if( ! file_exists($module_info_file) ) return;
        
        # Main settings
        $this->abspath = dirname($module_info_file);
        $this->name    = basename($this->abspath);
        
        # Var mapping from info file to local vars
        $module_info_contents = simplexml_load_file($module_info_file);
        # echo "<pre>\$module_info_contents := " . print_r($module_info_contents, true) . "</pre>";
        # echo "<pre>pre \$this := " . print_r($this, true) . "</pre>";
        foreach($module_info_contents as $key => $val)
            $this->{$key} = $val;
        # echo "<pre>post \$this := " . print_r($this, true) . "</pre>";
        
        # Language var loading
        $language_cookie_name = "{$config->website_key}_UL";
        $language_file = "$this->abspath/language/{$_COOKIE[$language_cookie_name]}.xml";
        if( ! file_exists($language_file) )
            $language_file = "$this->abspath/language/{$settings->get("engine.default_language")}.xml";
        
        if( ! file_exists($language_file) )
            $language_file = "$this->abspath/language/en_US.xml";
        
        if( ! file_exists($language_file) )
        {
            $message = replace_escaped_vars(
                $language->bootstrap->errors->language_file_dont_exist,
                array('{$language}', '{$module_name}'),
                array($_COOKIE[$language_cookie_name], $this->name)
            );
            
            die($message);
        }
        
        $this->language = simplexml_load_file($language_file);
        
        # Functionality flags
        $this->installed = $settings->get("modules:$this->name.installed") == "true";
        $this->enabled   = $settings->get("modules:$this->name.enabled")   == "true";
    }
    
    /**
     * Loads extensions of the invoked module.
     *
     * @param string $hook_area    Script/include/function being affected
     * @param string $hook_marker  Place of effect
     */
    public function load_extensions($hook_area, $hook_marker)
    {
        global /** @noinspection PhpUnusedLocalVariableInspection */
        $modules, $_ROOT_URL, $current_module;
        
        /**
         * @var module[] $modules
         * @var module   $current_module
         * @var module   $this_module
         */
        
        if( empty($this->extended_by) ) return;
        
        foreach($this->extended_by as $module_name => $sections)
        {
            if( empty($sections[$hook_area]) ) continue;
            
            if( empty($sections[$hook_area]->{$hook_marker}) ) continue;
    
            /** @noinspection PhpUnusedLocalVariableInspection */
            $this_module = $modules[$module_name];
            include "$_ROOT_URL/$module_name/".trim($sections[$hook_area]->{$hook_marker});
        }
    }
}
