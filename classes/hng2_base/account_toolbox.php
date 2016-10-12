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
    public $id_account;
    public $user_name;
    public $password;
    public $display_name;
    public $email;
    public $alt_email;
    public $birthdate;
    public $avatar;
    public $profile_banner;
    public $signature;
    public $bio;
    public $homepage_url;
    public $country;
    public $level;
    public $state;
    public $creation_host;
    public $creation_date;
    public $last_update;
    public $last_activity;
    public $changelog;
    
    # Dynamically loaded
    public $_exists    = false;
    public $_is_admin  = false;
    
    /**
     * @var disk_cache
     */
    private $engine_prefs_cache = null;
    
    public function set_new_id()
    {
        $this->id_account = make_unique_id("10");
    }
    
    public function get_processed_display_name()
    {
        global $config, $modules;
        
        $contents = $this->display_name;
        $contents = convert_emojis($contents);
        
        $config->globals["processing_id_account"]  = $this->id_account;
        $config->globals["processing_contents"] = $contents;
        $modules["accounts"]->load_extensions("account_record_class", "get_processed_display_name");
        $contents = $config->globals["processing_contents"];
        unset( $config->globals["processing_contents"] );
        
        return $contents;
    }
    
    public function get_processed_signature()
    {
        $contents = $this->signature;
        $contents = convert_emojis($contents);
        
        return $contents;
    }
    
    public function get_role($lowercased = false)
    {
        global $config;
        
        if( $lowercased ) return strtolower($config->user_levels_by_level[$this->level]);
        else              return $config->user_levels_by_level[$this->level];
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
        global $config, $settings;
        
        $default = $settings->get("modules:accounts.default_profile_banner");
        if( empty($default) ) $default = "media/default_user_banner.jpg";
        
        $file = empty($this->profile_banner) ? $default : "user/{$this->user_name}/profile_banner";
        $file = ltrim($file, "/");
        
        if( $fully_qualified ) return "{$config->full_root_url}/{$file}";
        
        return "{$config->full_root_path}/{$file}";
    }
    
    protected function init_engine_prefs_cache()
    {
        global $config;
        
        $dir = substr($this->user_name, 0, 3);
        $cache_file = "{$config->datafiles_location}/cache/account_prefs/{$dir}/{$this->user_name}.dat";
        
        if( ! is_object($this->engine_prefs_cache) )
            $this->engine_prefs_cache = new disk_cache($cache_file);
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
    
    /**
     * @return array
     * 
     * @throws \Exception
     */
    public function get_editable_prefs()
    {
        global $database;
        
        $res = $database->query("
            select * from account_engine_prefs
            where id_account = '{$this->id_account}'
            and   name like '@%'
        ");
        
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        while($row = $database->fetch_object($res) )
            $return[$row->name] = json_decode($row->value);
        
        return $return;
    }
    
    public function get_last_activity($as_elapsed_string = false)
    {
        global $database, $language;
        
        $res = $database->query("
            select last_activity from account_devices
            where account_devices.id_account = '{$this->id_account}'
            order by last_activity desc limit 1
        ");
        
        if( $database->num_rows($res) == 0 )
        {
            if($as_elapsed_string) return $language->never;
            else                   return "";
        }
        
        $row = $database->fetch_object($res);
        if( $as_elapsed_string ) return time_elapsed_string($row->last_activity);
        else                     return $row->last_activity;
    }
    
    public function is_online()
    {
        $last_activity = $this->get_last_activity();
        
        if( empty($last_activity) ) return false;
        
        return $last_activity >= date("Y-m-d H:i:s", strtotime("now - 1 minute"));
    }
    
    public function ping()
    {
        global $config, $database;
        
        if( ! $this->_exists ) return;
        
        $device_cookie_key = "_" . $config->website_key . "_DIC";
        if( empty($_COOKIE[$device_cookie_key]) ) return;
        
        $id_device = decrypt( $_COOKIE[$device_cookie_key], $config->encryption_key );
        $date      = date("Y-m-d H:i:s");
        
        $database->exec("
            update account_devices set
                last_activity    = '$date'
            where
                id_device        = '$id_device'
        ");
    }
}
