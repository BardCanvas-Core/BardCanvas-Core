<?php
namespace hng2_cache;

class disk_cache
{
    protected $data = array();
    
    private $disk_cache_dir;
    private $disk_cache_file;
    
    public $loaded = false;
    
    private static $cache_hits = array();
    
    public function __construct($target_file_path = "", $preload_data = true)
    {
        global $config;
        
        if( empty($target_file_path) ) return;
        
        $this->disk_cache_dir  = dirname($target_file_path);
        
        $parts = explode(".", $target_file_path);
        $ext   = array_pop($parts);
        $name  = implode(".", $parts);
        
        $this->disk_cache_file = "$name~v{$config->disk_cache_version}.$ext";
        
        if( $preload_data ) $this->load();
    }
    
    private function load()
    {
        if( ! is_dir($this->disk_cache_dir) )
        {
            if( ! @mkdir($this->disk_cache_dir, 0777, true) )
                throw new \RuntimeException("Can't create cache directory {$this->disk_cache_dir}");
            
            @chmod($this->disk_cache_dir, 0777);
        }
        
        if( ! file_exists($this->disk_cache_file) ) return;
        
        $res = unserialize(file_get_contents($this->disk_cache_file));
        
        if( ! empty($res) ) $this->data = $res;
        $this->loaded = true;
        
        $backtrace = debug_backtrace();
        foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        self::$cache_hits[] = (object) array(
            "file"      => basename($this->disk_cache_file),
            "type"      => "load",
            "key"       => "N/A",
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
    }
    
    public function prefill(array $data)
    {
        $this->data = $data;
        $this->save();
    }
    
    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        if( ! isset($this->data[$key]) ) return null;
        
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
        
        $this->save();
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        self::$cache_hits[] = (object) array(
            "file"      => basename($this->disk_cache_file),
            "type"      => "set",
            "key"       => $key,
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
    }
    
    public function delete($key)
    {
        global $config;
        
        unset( $this->data[$key] );
        $this->save();
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        self::$cache_hits[] = (object) array(
            "file"      => basename($this->disk_cache_file),
            "type"      => "delete",
            "key"       => $key,
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
    }
    
    public function enable_batchmode()
    {
        global $config;
        $config->globals["disk_cache_batchmode:{$this->disk_cache_file}"] = true;
    }
    
    public function disable_batchmode()
    {
        global $config;
        $config->globals["disk_cache_batchmode:{$this->disk_cache_file}"] = false;
    }
    
    public function save()
    {
        global $config, $mem_cache;
        
        if( $config->globals["disk_cache_batchmode:{$this->disk_cache_file}"] ) return;
        
        $mem_cache_key = "saving_disk_cache:{$this->disk_cache_file}";
        if( $mem_cache->get($mem_cache_key) ) return;
        
        $mem_cache->set($mem_cache_key, "true", 0, 120);
        $data = serialize($this->data);
        
        if( ! @file_put_contents($this->disk_cache_file, $data) )
            throw new \RuntimeException("Can't write cache file {$this->disk_cache_file}");
        
        @chmod($this->disk_cache_file, 0777);
        $mem_cache->delete($mem_cache_key);
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        self::$cache_hits[] = (object) array(
            "file"      => basename($this->disk_cache_file),
            "type"      => "save",
            "key"       => "N/A",
            "timestamp" => microtime(true),
            "backtrace" => $backtrace,
        );
    }
    
    public static function get_hits()
    {
        return self::$cache_hits;
    }
}
