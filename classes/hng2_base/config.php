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
    public $language_cookie_var;
    
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
    const NEWCOMER_USER_LEVEL     =  10;
    const AUTHOR_USER_LEVEL       = 100;
    const VIP_USER_LEVEL          = 150;
    const MODERATOR_USER_LEVEL    = 200;
    const COADMIN_USER_LEVEL      = 240;
    const ADMIN_USER_LEVEL        = 255;
    
    public $display_performance_details = false;
    public $query_tracking_enabled      = false;
    public $query_backtrace_enabled     = false;
    
    public $upload_file_types = array(
        "png"  => "system",
        "gif"  => "system",
        "jpg"  => "system",
        "jpeg" => "system",
        
        "mpg"  => "system",
        "mp4"  => "system",
        "m4v"  => "system",
        "3gp"  => "system",
        "mov"  => "system",
        "avi"  => "system",
        "ogg"  => "system",
        "ogv"  => "system",
        "flv"  => "system",
        "mkv"  => "system",
        "wmv"  => "system",
        "webm" => "system",
    );
    
    /**
     * Generic usage
     * 
     * @var array
     */
    public $globals = array();
    
    /**
     * @var string MD5 of concatenated module version strings
     */
    public $module_versions_hash = "";
    
    public function __construct()
    {
        $this->encryption_key      = ENCRYPTION_KEY;
        $this->website_key         = WEBSITE_ID;
        $this->cookies_domain      = "." . trim(str_replace("www", "", $_SERVER["HTTP_HOST"]), ".");
        $this->language_cookie_var = WEBSITE_ID . "_" . LANGUAGE_COOKIE_VAR;
        
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
            
            if( $this->full_root_path == "/" ) $this->full_root_path = "";
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
        
        if( ! is_object($settings) )
            throw new \Exception(sprintf("%s method must be called after loading the settings.", __METHOD__));
        
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
    
    /**
     * Sets metering toggles. Must be loaded once the settings are loaded.
     */
    public function set_metering_toggles()
    {
        global $settings;
        
        if( ! is_object($settings) )
            throw new \Exception(sprintf("%s method must be called after loading the settings.", __METHOD__));
        
        $this->display_performance_details = $settings->get("engine.display_performance_details") == "true";
        $this->query_tracking_enabled      = $settings->get("engine.query_tracking_enabled")      == "true";
        $this->query_backtrace_enabled     = $settings->get("engine.query_backtrace_enabled")     == "true";
    }
    
    public function toggle_display_performance($value)
    {
        $target = "{$this->datafiles_location}/display_performance.enabled";
        if( $value == "true" )
        {
            if( ! @touch($target) )
                throw new \Exception("Impossible to enable performance displaying - can't write '$target' file.");
            
            @chmod($target, 0777);
        }
        else
        {
            if( ! @unlink($target) )
                throw new \Exception("Impossible to disable performance displaying - can't delete '$target' file.");
        }
    }
    
    public function toggle_query_tracking($value)
    {
        $target = "{$this->datafiles_location}/query_tracking.enabled";
        if( $value == "true" )
        {
            if( ! @touch($target) )
                throw new \Exception("Impossible to enable query tracking - can't write '$target' file.");
        
            @chmod($target, 0777);
        }
        else
        {
            if( ! @unlink($target) )
                throw new \Exception("Impossible to disable query tracking - can't delete '$target' file.");
        }
    }
    
    public function toggle_query_backtrace($value)
    {
        $target = "{$this->datafiles_location}/query_backtrace.enabled";
        if( $value == "true" )
        {
            if( ! @touch($target) )
                throw new \Exception("Impossible to enable query backtrace - can't write '$target' file.");
        
            @chmod($target, 0777);
        }
        else
        {
            if( ! @unlink($target) )
                throw new \Exception("Impossible to disable query backtrace - can't delete '$target' file.");
        }
    }
    
    public function fill_upload_types()
    {
        global $settings;
    
        if( ! is_object($settings) )
            throw new \Exception(sprintf("%s method must be called after loading the settings.", __METHOD__));
        
        $res = $settings->get("engine.upload_file_types");
        if( empty($res) ) return;
        
        $lines = explode("\n", $res);
        $this->upload_file_types = array();
        foreach($lines as $line)
        {
            $line = trim($line);
            if( empty($line) ) continue;
            
            list($type, $manager) = preg_split('/\s*\-\s*/', $line);
            $this->upload_file_types[$type] = $manager;
        }
    }
}
