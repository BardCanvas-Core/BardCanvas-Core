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
    
    public $datafiles_location;
    public $logfiles_location;
    
    // These two are loaded from the settings table
    public $user_levels_by_level = array();
    public $user_levels_by_name  = array();
    
    /**
     * Full URL of the website, for links in emails, etc.
     * 
     * @var string
     */
    public $full_root_url;
    
    /**
     * Full path to website root. Usually "/"
     * 
     * @var string
     */
    public $full_root_path;
    
    const UNREGISTERED_USER_LEVEL =   0;
    const UNCONFIRMED_USER_LEVEL  =   1;
    const NEWCOMER_USER_LEVEL     =  50;
    const AUTHOR_USER_LEVEL       = 100;
    const VIP_USER_LEVEL          = 150;
    const MODERATOR_USER_LEVEL    = 200;
    const COADMIN_USER_LEVEL      = 240;
    const ADMIN_USER_LEVEL        = 255;
    
    public function __construct()
    {
        $this->encryption_key     = ENCRYPTION_KEY;
        $this->website_key        = WEBSITE_ID;
        $this->cookies_domain     = "." . trim(str_replace("www", "", $_SERVER["HTTP_HOST"]), ".");
        
        $this->datafiles_location = ABSPATH . "/data";
        $this->logfiles_location  = ABSPATH . "/logs";
        
        $this->set_versions();
        $this->set_paths();
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
    
    private function set_paths()
    {
        if( defined("FULL_ROOT_PATH") )
        {
            $this->full_root_path = FULL_ROOT_PATH;
        }
        else
        {
            # ABSPATH = /home/user/public_html/some_folder/some_subfolder
            # DOCROOT = /home/user/public_html
            # then.....                       /some_folder/some_subfolder
            
            $docroot = empty($_SERVER["DOCUMENT_ROOT"]) ? ABSPATH : $_SERVER["DOCUMENT_ROOT"];
            $this->full_root_path = preg_replace("#{$docroot}#i", "", ABSPATH);
            $this->full_root_path = "/" . trim($this->full_root_path, "/");
        }
        
        if( defined("FULL_ROOT_URL") )
        {
            $this->full_root_url = FULL_ROOT_URL;
        }
        else
        {
            $this->full_root_url  = empty($_SERVER["HTTPS"]) ? "http://" : "https://";
            $this->full_root_url .= $_SERVER["HTTP_HOST"];
            $this->full_root_url .= $this->full_root_path;
        }
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
