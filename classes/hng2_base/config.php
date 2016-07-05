<?php
/**
 * Config class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base;

class config
{
    public $encryption_key;
    public $website_key;
    public $cookies_domain;
    
    public $engine_version;
    public $scripts_version;
    
    public $document_root_path;
    public $datafiles_location;
    public $logfiles_location;
    
    public $user_levels_by_level = array();
    public $user_levels_by_name  = array();
    
    public function __construct()
    {
        $this->encryption_key     = ENCRYPTION_KEY;
        $this->website_key        = WEBSITE_ID;
        $this->cookies_domain     = COOKIES_DOMAIN;
        
        $this->document_root_path = ABSPATH;
        $this->datafiles_location = ABSPATH . "/data";
        $this->logfiles_location  = ABSPATH . "/logs";
        
        $this->set_versions();
    }
    
    private function set_versions()
    {
        if( file_exists(ABSPATH . "/engine_version.dat") )
            $this->engine_version = trim(file_get_contents(ABSPATH . "/engine_version.dat"));
        else
            $this->engine_version = "1.0";
    
        if( file_exists(ABSPATH . "/scripts_version.dat") )
            $this->scripts_version = trim(file_get_contents(ABSPATH . "/scripts_version.dat"));
        else
            $this->scripts_version = "1.0";
    }
    
    /**
     * Fills user levels. Must be loaded once the settings are loaded.
     */
    public function fill_user_levels()
    {
        global $settings;
        
        $levels = $settings->get("engine.user_levels");
        if( empty($levels) ) return;
        
        $lines = explode("\n", $levels);
        foreach($lines as $line)
        {
            list($level, $name) = explode(" - ", $line);
            $level = trim($level);
            $name  = trim($name);
            
            $this->user_levels_by_level[$level] = $name;
            $this->user_levels_by_name[$name] = $level;
        }
    }
}
