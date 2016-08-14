<?php
/**
 * Class account_toolbox
 * Serves as base for shared methods between the actual account_record class
 * and the self-served account class.
 * 
 * @package hng2_base
 */

namespace hng2_base;

use hng2_cache\disk_cache;
use hng2_repository\abstract_record;

class account_toolbox extends abstract_record
{
    /**
     * @var disk_cache
     */
    private $engine_prefs_cache = null;
    
    public function set_new_id()
    {
        $this->id_account = uniqid();
    }
    
    public function get_processed_display_name()
    {
        $contents = $this->display_name;
        $contents = convert_emojis($contents);
        
        return $contents;
    }
    
    public function get_processed_signature()
    {
        $contents = $this->signature;
        $contents = convert_emojis($contents);
        
        return $contents;
    }
    
    public function get_role()
    {
        global $config;
        
        return $config->user_levels_by_level[$this->level];
    }
    
    public function get_avatar_url($fully_qualified = false)
    {
        global $config;
        
        if( $this->avatar == "@gravatar" )
            return "https://www.gravatar.com/avatar/" . md5(trim(strtolower($this->email)));
        
        $file = empty($this->avatar) ? "media/default_avatar.jpg" : "user/{$this->user_name}/avatar";
        
        if( $fully_qualified ) return "{$config->full_root_url}/{$file}";
        
        return "{$config->full_root_path}/{$file}";
    }
    
    function get_profile_banner_url($fully_qualified = false)
    {
        global $config;
        
        $file = empty($this->profile_banner) ? "media/default_user_banner.jpg" : "user/{$this->user_name}/profile_banner";
        
        if( $fully_qualified ) return "{$config->full_root_url}/{$file}";
        
        return "{$config->full_root_path}/{$file}";
    }
    
    protected function init_engine_prefs_cache()
    {
        global $config;
        
        if( ! is_object($this->engine_prefs_cache) )
            $this->engine_prefs_cache = new disk_cache(
                "{$config->datafiles_location}/cache/account_prefs_{$this->user_name}.dat"
            );
    }
    
    protected function load_engine_prefs()
    {
        global $database;
        
        $this->init_engine_prefs_cache();
        if( $this->engine_prefs_cache->loaded )
        {
            $this->engine_prefs = $this->engine_prefs_cache->get_all();
            
            return;
        }
        
        $this->engine_prefs = array();
        $res = $database->query("
            select * from account_engine_prefs where id_account = '$this->id_account' order by `name` asc
        ");
        
        if( ! $res ) return;
        if( $database->num_rows($res) == 0 )
        {
            $this->engine_prefs_cache->set("_none_", "Left here on purpose. Discardable.");
            
            return;
        }
        
        while( $row = $database->fetch_object($res) )
            $this->engine_prefs[$row->name] = json_decode($row->value);
        
        $this->engine_prefs_cache->prefill($this->engine_prefs);
    }
    
    public function get_engine_pref($key, $default_value = "")
    {
        $this->load_engine_prefs();
    
        if( isset($this->engine_prefs[$key]) ) return $this->engine_prefs[$key];
        else                                   return $default_value;
    }
    
    public function set_engine_pref($key, $value)
    {
        global $database;
    
        $this->load_engine_prefs();
        
        if( empty($value) )
        {
            if( isset($this->engine_prefs[$key]) )
            {
                unset( $this->engine_prefs[$key] );
                $this->engine_prefs_cache->set($key, "");
            }
            
            $database->exec("
                delete from account_engine_prefs where
                    id_account = '".addslashes($this->id_account)."' and
                    `name`  = '$key'
            ");
        }
        else
        {
            $this->engine_prefs[$key] = $value;
            $this->engine_prefs_cache->set($key, $value);
            
            $database->exec("
                insert into account_engine_prefs set
                    id_account = '".addslashes($this->id_account)."',
                    `name`     = '$key',
                    `value`    = '".json_encode($value)."'
                on duplicate key update
                    `value`    = '".json_encode($value)."'
            ");
        }
    }
}
