<?php
namespace hng2_base\repository;

class account_record extends abstract_record
{
    public $id_account;
    public $user_name;
    public $password;
    public $display_name;
    public $email;
    public $alt_email;
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
        global $settings;
        
        parent::set_from_object($object_or_array);
        
        if( ! empty($this->id_account) ) $this->_exists = true;
        
        $admins_list = explode(",", $settings->get("engine.admins"));
        if( in_array($this->id_account, $admins_list) ) $this->_is_admin = true;
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
}
