<?php
/**
 * Class account_record
 * Extends the account toolbox, which also extends the abstract record class.
 * 
 * @package hng2_base
 */

namespace hng2_base;

class account_record extends account_toolbox
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
