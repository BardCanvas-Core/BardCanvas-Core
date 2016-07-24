<?php
/**
 * Module class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base;

class module
{
    # Base settings
    var $name;
    var $version;
    var $abspath;
    
    /**
     * @var \SimpleXMLElement
     */
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
    
    /**
     * Information to show in the modules manager on how this module can be extended. 
     * @var \SimpleXMLElement
     */
    var $extension_areas_info;
    
    /**
     * Modules it extends
     * @var \SimpleXMLElement
     */
    var $extends_to;
    
    /**
     * Modules that extend this module.
     * @var array Two dimensions: module name and extension area.
     */
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
        
        # Sanitization
        $this->version    = trim($this->version);
        $this->group      = trim($this->group);
        $this->admin_only = trim($this->admin_only);
        
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
        /** @noinspection PhpUnusedLocalVariableInspection */
        global $modules, $current_module;
        
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
            include ABSPATH . "/$module_name/".trim($sections[$hook_area]->{$hook_marker});
        }
    }
    
    /**
     * @return module
     */
    public function serialize()
    {
        $self = clone $this;
        $self->php_includes      = empty($self->php_includes)      ? "" : $self->php_includes->asXML();
        $self->template_includes = empty($self->template_includes) ? "" : $self->template_includes->asXML();
        $self->language          = empty($self->language)          ? "" : $self->language->asXML();
        $self->menu_items        = empty($self->menu_items)        ? "" : $self->menu_items->asXML();
        $self->working_flags     = empty($self->working_flags)     ? "" : $self->working_flags->asXML();
    
        $self->extension_areas_info = empty($self->extension_areas_info) ? "" : $self->extension_areas_info->asXML();
        $self->extends_to           = empty($self->extends_to)           ? "" : $self->extends_to->asXML();
        
        /** @var \SimpleXMLElement $area */
        if( ! empty($self->extended_by) )
            foreach($self->extended_by as &$extending_areas)
                foreach($extending_areas as &$area)
                    $area = $area->asXML();
        
        return $self;
    }
    
    public function unserialize()
    {
        $this->php_includes      = simplexml_load_string($this->php_includes);
        $this->template_includes = simplexml_load_string($this->template_includes);
        $this->language          = simplexml_load_string($this->language);
        $this->menu_items        = simplexml_load_string($this->menu_items);
        $this->working_flags     = simplexml_load_string($this->working_flags);
        
        $this->extension_areas_info = simplexml_load_string($this->extension_areas_info);
        $this->extends_to           = simplexml_load_string($this->extends_to);
    
        /** @var \SimpleXMLElement $area */
        if( ! empty($this->extended_by) )
            foreach($this->extended_by as &$extending_areas)
                foreach($extending_areas as &$area)
                    $area = simplexml_load_string($area);
    }
}
