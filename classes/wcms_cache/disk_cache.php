<?php
namespace wcms_cache;

class disk_cache
{
    protected $data = array();
    
    private $disk_cache_dir;
    private $disk_cache_file;
    
    public $loaded = false;
    
    public function __construct($target_file_path)
    {
        $this->disk_cache_dir  = dirname($target_file_path);
        $this->disk_cache_file = $target_file_path;
        
        $this->load();
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
    
    /**
     * @return array
     */
    public function get_all()
    {
        return $this->data;
    }
    
    public function set($key, $value)
    {
        if( empty($value) ) unset( $this->data[$key] );
        else                $this->data[$key] = $value;
        
        $this->save();
    }
    
    private function save()
    {
        $data = serialize($this->data);
        
        if( ! @file_put_contents($this->disk_cache_file, $data) )
            throw new \RuntimeException("Can't write cache file {$this->disk_cache_file}");
        
        @chmod($this->disk_cache_file, 0777);
    }
}
