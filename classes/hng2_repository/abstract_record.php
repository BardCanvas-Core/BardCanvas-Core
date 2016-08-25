<?php
/**
 * Abstract database record
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_repository;

abstract class abstract_record
{
    public function __construct($object_or_array = null)
    {
        if( ! is_null($object_or_array) ) $this->set_from_object($object_or_array);
    }
    
    protected function set_from_object($object_or_array)
    {
        if( is_array($object_or_array) ) $object_or_array = (array) $object_or_array;
        
        foreach( $object_or_array as $key => $val ) $this->{$key} = $val;
    }
    
    public function set_from_post()
    {
        foreach( $_POST as $key => $val )
            if( is_string($val) )
                $this->{$key} = stripslashes($val);
    }
    
    abstract public function set_new_id();
    
    /**
     * @return array
     */
    public function get_as_associative_array()
    {
        return (array) $this;
    }
    
    /**
     * @return object
     */
    public function get_for_database_insertion()
    {
        $return = (array) $this;
        
        foreach( $return as $key => &$val ) $val = addslashes($val);
        
        return (object) $return;
    }
}
