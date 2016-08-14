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
