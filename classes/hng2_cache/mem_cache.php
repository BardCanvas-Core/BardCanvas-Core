<?php
namespace hng2_cache;

class mem_cache
{
    protected $data = array();
    
    private $var_prefix;
    
    /**
     * @var \Memcache()
     */
    private $server;
    
    private $cache_hits = array();
    
    public function __construct($var_prefix = "")
    {
        global $MEMCACHE_SERVERS, $config;
        
        $this->var_prefix = $config->website_key . "_";
        
        if( ! empty($var_prefix) ) $this->var_prefix .= $var_prefix . "_";
        
        $this->server = new \Memcache();
        
        foreach($MEMCACHE_SERVERS as $server)
            $this->server->addserver($server["host"], $server["port"]);
    }
    
    public function set($key, $value, $flag = 0, $expiration = 0)
    {
        $key = $this->var_prefix . $key;
        
        if( empty($value) )
        {
            $this->delete($key);
            
            return;
        }
        
        $this->server->set($key, $value, $flag, $expiration);
        $this->data[$key] = $value;
        
        $backtrace = "N/A";
        if( defined("ENABLE_QUERY_BACKTRACE") && ENABLE_QUERY_BACKTRACE )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        $this->cache_hits[] = (object) array(
            "type"      => "set",
            "key"       => $key,
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
    }
    
    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        $key = $this->var_prefix . $key;
        
        if( isset($this->data[$key]) ) return $this->data[$key];
        
        $value = $this->server->get($key);
        if( $value === false ) $value = null;
        $this->data[$key] = $value;
        
        $backtrace = "N/A";
        if( defined("ENABLE_QUERY_BACKTRACE") && ENABLE_QUERY_BACKTRACE )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        
        $this->cache_hits[] = (object) array(
            "type"      => "get",
            "key"       => $key,
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
        
        return $value;
    }
    
    public function delete($key)
    {
        $key = $this->var_prefix . $key;
        unset( $this->data[$key] );
        $this->server->delete($key);
    
        $backtrace = "N/A";
        if( defined("ENABLE_QUERY_BACKTRACE") && ENABLE_QUERY_BACKTRACE )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        $this->cache_hits[] = (object) array(
            "type"      => "delete",
            "key"       => $key,
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
    }
    
    public function get_hits()
    {
        return $this->cache_hits;
    }
}
