<?php
namespace hng2_base\repository;

use hng2_base\config;

class account_record extends abstract_record
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
    
    public $_exists   = false;
    public $_is_admin = false;
    
    protected function set_from_object($object_or_array)
    {
        parent::set_from_object($object_or_array);
        
        if( ! empty($this->id_account) ) $this->_exists = true;
        
        if( $this->level >= config::COADMIN_USER_LEVEL ) $this->_is_admin = true;
    }
    
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
    
    /**
     * @return object
     */
    public function get_for_database_insertion()
    {
        $return = (array) $this;
        
        unset(
            $return["_exists"],
            $return["_is_admin"]
        );
        
        foreach( $return as $key => &$val ) $val = addslashes($val);
        
        return (object) $return;
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
    
}
