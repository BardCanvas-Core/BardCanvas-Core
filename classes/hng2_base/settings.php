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
        global $database, $config;
        
        if( is_bool($forced) )
        {
            $default_value = "";
        }
        else
        {
            $default_value = $forced;
            $forced = false;
        }
        
        if( $this->cache->exists($name) && $forced === false )
        {
            $value = $this->cache->get($name);
            if( substr($value, 0, 7) == "@@s3le:" )
            {
                $value = preg_replace("/^@@s3le:/", "", $value);
                $value = three_layer_decrypt($value, $config->encryption_key, md5($config->encryption_key), sha1($config->encryption_key));
            }
            
            return $value;
        }
        
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
        
        $row   = $database->fetch_object($res);
        $value = $row->value;
        
        if( ! $forced ) $this->cache->set($name, $value);
        
        if( substr($value, 0, 7) == "@@s3le:" )
        {
            $value = preg_replace("/^@@s3le:/", "", $value);
            $value = three_layer_decrypt($value, $config->encryption_key, md5($config->encryption_key), sha1($config->encryption_key));
        }
        
        return $value;
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
        global $database, $config;
        
        if( substr($value, 0, 10) == "@@encrypt:" )
        {
            $value = preg_replace("/^@@encrypt:/", "", $value);
            $value = "@@s3le:" . three_layer_encrypt($value, $config->encryption_key, md5($config->encryption_key), sha1($config->encryption_key));
        }
        
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
    
    /**
     * Find settings containing the provided string within the key
     * 
     * @param $pattern
     * 
     * @return array
     */
    public function find($pattern)
    {
        if( ! $this->cache->loaded )
        {
            $res = $this->get_all();
            $this->cache->prefill($res);
        }
        
        $found = array();
        
        foreach($this->cache->get_all() as $key => $val)
            if( stristr($key, $pattern) !== false ) $found[$key] = $val;
        
        return $found;
    }
    
    public function prepare_batch()
    {
        $this->cache->enable_batchmode();
    }
    
    public function commit_batch()
    {
        $this->cache->disable_batchmode();
        $this->cache->save();
    }
    
    /**
     * Returns an array of lines stored in a code input representing options.
     * Skips empty lines and those commented out with a trailing hash symbol.
     *
     * @param string $key
     * @param bool   $forced
     * 
     * @return array
     * @throws \Exception
     */
    public function get_lines($key, $forced = false)
    {
        $input = $this->get($key, $forced);
        if( empty($input) ) return array();
        
        $return = array();
        foreach(explode("\n", $input) as $line)
        {
            $line = trim($line);
            if( empty($line) ) continue;
            if( substr($line, 0, 1) == "#" ) continue;
            
            $return[] = $line;
        }
        
        return $return;
    }
}
