<?php
/**
 * Settings class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base;

use hng2_cache\disk_cache;

class settings
{
    /**
     * @var disk_cache
     */
    private $cache;
    
    public function __construct()
    {
        global $config;
        
        $this->cache = new disk_cache("{$config->datafiles_location}/cache/settings.dat");
        if( ! $this->cache->loaded )
        {
            $res = $this->get_all();
            $this->cache->prefill($res);
        }
    }
    
    /**
     * @param            $name
     * @param bool|mixed $forced If true, cache is skipped.
     *                           If false, nothing is returned if not found.
     *                           Else, the string is returned if not found.
     *
     * @return string
     * 
     * @throws \Exception
     */
    public function get($name, $forced = false)
    {
        global $database;
        
        if( is_bool($forced) )
        {
            $default_value = "";
        }
        else
        {
            $default_value = $forced;
            $forced = false;
        }
        
        if( $this->cache->exists($name) && ! $forced ) return $this->cache->get($name);
        
        $res = $database->query("select value from settings where name = '$name'");
        
        if( $database->num_rows($res) == 0 && empty($default_value) )
        {
            $this->cache->set($name, "");
            
            return "";
        }
        elseif( $database->num_rows($res) == 0 && ! empty($default_value) )
        {
            $this->cache->set($name, $default_value);
            
            return $default_value;
        }
        
        $row = $database->fetch_object($res);
        if( ! $forced ) $this->cache->set($name, $row->value);
        
        return $row->value;
    }
    
    private function get_all()
    {
        global $database;
        
        $res = $database->query("select * from settings");
        if( $database->num_rows($res) == 0 ) return array();
    
        $return = array();
        while( $row = $database->fetch_object($res) ) $return[$row->name] = $row->value;
        
        return $return;
    }
    
    public function set($name, $value)
    {
        global $database;
        
        $this->cache->set($name, $value);
        
        $value = addslashes(trim(stripslashes($value)));
        
        $database->exec("
            insert into settings (
                `name`, `value`
            ) values (
                '$name', '$value'
            )
            on duplicate key update
                value = '$value'
        ");
    }
    
    public function delete($name)
    {
        global $database;
        
        $this->cache->delete($name);
        $database->exec("delete from settings where name = '$name'");
    }
}
