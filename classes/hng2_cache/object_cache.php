<?php
namespace hng2_cache;

class object_cache
{
    protected $owner;
    
    protected $data = array();
    
    private static $cache_hits = array();
    
    public function __construct($owner)
    {
        $this->owner = $owner;
    }
    
    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        global $config;
        
        if( ! isset($this->data[$key]) ) return null;
    
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        self::$cache_hits[] = (object) array(
            "owner"     => $this->owner,
            "type"      => "get",
            "key"       => $key,
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
        
        return $this->data[$key];
    }
    
    public function exists($key)
    {
        return isset($this->data[$key]);
    }
    
    /**
     * @return array
     */
    public function get_all()
    {
        return $this->data;
    }
    
    public function set($key, $value)
    {
        global $config;
        
        $this->data[$key] = $value;
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        self::$cache_hits[] = (object) array(
            "owner"     => $this->owner,
            "type"      => "set",
            "key"       => $key,
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
    }
    
    public function delete($key)
    {
        global $config;
        
        if( isset($this->data[$key]) )
        {
            $backtrace = "N/A";
            if( $config->query_backtrace_enabled )
            {
                $backtrace = debug_backtrace();
                foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
            }
            self::$cache_hits[] = (object) array(
                "owner"     => $this->owner,
                "type"      => "delete",
                "key"       => $key,
                "timestamp" => microtime(true),
                "backtrace" => $backtrace,
            );
        }
        
        unset( $this->data[$key] );
    }
    
    public static function get_hits()
    {
        return self::$cache_hits;
    }
}
