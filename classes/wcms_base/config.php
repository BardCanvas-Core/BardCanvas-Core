<?php
/**
 * Config class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace wcms_base;

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
}
