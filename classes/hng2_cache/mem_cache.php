<?php
namespace hng2_cache;

class mem_cache
{
    protected $data = array();
    
    private $var_prefix;
    
    private $suffix;
    
    /**
     * @var \Memcache()
     */
    private $server;
    
    private $cache_hits = array();
    
    private $enabled = true;
    
    public function __construct($var_prefix = "")
    {
        global $MEMCACHE_SERVERS, $config;
        
        $this->var_prefix = $config->website_key . "_";
        
        if( ! empty($var_prefix) ) $this->var_prefix .= $var_prefix . "_";
        
        $this->suffix = $config->memory_cache_version;
        
        if( empty($MEMCACHE_SERVERS) )
        {
            $this->enabled = false;
        }
        elseif( ! class_exists("Memcache") )
        {
            $this->enabled = false;
        }
        else
        {
            $this->server = new \Memcache();
            
            foreach($MEMCACHE_SERVERS as $server)
                $this->server->addserver($server["host"], $server["port"]);
        }
    }
    
    public function set($key, $value, $flag = 0, $expiration = 0)
    {
        global $config;
        
        if( ! $this->enabled ) return false;
        
        $key = $this->var_prefix . $key . "~v" . $this->suffix;
        
        if( empty($value) )
        {
            $this->delete($key);
            
            return true;
        }
        
        $res = $this->server->set($key, $value, $flag, $expiration);
        $this->data[$key] = $value;
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
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
        
        return $res;
    }
    
    /**
     * @param      $key
     * @param bool $raw_key
     *
     * @return mixed|null
     */
    public function get($key, $raw_key = false)
    {
        global $config;
        
        if( ! $this->enabled ) return null;
        
        if( $raw_key === false ) $key = $this->var_prefix . $key . "~v" . $this->suffix;
        
        if( isset($this->data[$key]) ) return $this->data[$key];
        
        $value = $this->server->get($key);
        if( $value === false ) $value = null;
        $this->data[$key] = $value;
        
        if( is_null($value) ) return $value;
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
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
        global $config;
        
        if( ! $this->enabled ) return;
        
        $key = $this->var_prefix . $key . "~v" . $this->suffix;
        unset( $this->data[$key] );
        $this->server->delete($key);
    
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
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
    
    /**
     * Gets all keys stored in memcache with this prefix
     * 
     * @see https://coderwall.com/p/imot3w/php-memcache-list-keys
     * 
     * @param int $limit
     *
     * @return array
     */
    public function get_all_keys($limit = 10000)
    {
        $keys = array();
        
        if( ! $this->enabled ) return $keys;
        
        $slabs = $this->server->getextendedstats('slabs');
        foreach( $slabs as $serverSlabs )
        {
            foreach( $serverSlabs as $slabId => $slabMeta )
            {
                try
                {
                    $cacheDump = $this->server->getextendedstats('cachedump', (int) $slabId, 1000);
                }
                catch( \Exception $e )
                {
                    continue;
                }
                
                if( ! is_array($cacheDump) ) continue;
                
                foreach( $cacheDump as $dump )
                {
                
                    if( ! is_array($dump) ) continue;
                    
                    foreach( $dump as $key => $value )
                    {
                        if( preg_match("#^{$this->var_prefix}.*#", $key) )
                            $keys[] = $key;
                        
                        if( count($keys) == $limit ) return $keys;
                    }
                }
            }
        }
        
        return $keys;
    }
    
    public function purge_by_prefix($prefix)
    {
        if( ! $this->enabled ) return;
        
        $all_keys = $this->get_all_keys();
        $key      = $this->var_prefix . $prefix;
        
        foreach($all_keys as $existing_key)
        {
            if( ! @preg_match("#^{$key}.*#", $existing_key) ) continue;
            
            unset( $this->data[$key], $this->data[$existing_key] );
            $this->server->delete($existing_key);
        }
    }
}
