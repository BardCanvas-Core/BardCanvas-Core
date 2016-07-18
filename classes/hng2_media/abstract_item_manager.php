<?php
namespace hng2_media;

abstract class abstract_item_manager
{
    protected $save_path;
    protected $relative_path;
    
    public $file_name;
    public $mime_type;
    public $file_path;
    
    public function __construct($file_name, $mime_type, $file_path)
    {
        global $config;
        
        $this->save_path     = $config->datafiles_location . "/uploaded_media/" . date("Y/m");
        $this->relative_path = date("Y/m"); 
        
        $this->file_name = $file_name;
        $this->mime_type = $mime_type;
        $this->file_path = $file_path;
    }
    
    public function copy_to_repository($new_file_name)
    {
        $this->check_target_directory();
        
        $source_file = $this->file_path;
        $target_file = $this->save_path . "/";
        if( empty($new_file_name) ) $target_file .= $this->file_name;
        else                        $target_file .= $new_file_name;
        
        if( ! @copy($source_file, $target_file) )
            throw new \Exception(sprintf(
                "Cannot copy $source_file into $target_file"
            ));
        
        @chmod($target_file, 0777);
        $this->file_name = $new_file_name;
        $this->file_path = $target_file;
    }
    
    public function move_to_repository($new_file_name)
    {
        $this->check_target_directory();
        
        $source_file = $this->file_path;
        $target_file = $this->save_path . "/";
        if( empty($new_file_name) ) $target_file .= $this->file_name;
        else                        $target_file .= $new_file_name;
        
        if( is_uploaded_file($source_file) )
        {
            if( ! @move_uploaded_file($source_file, $target_file) )
                throw new \Exception(sprintf(
                    "Cannot move $source_file into $target_file"
                ));
        }
        else
        {
            if( ! @rename($source_file, $target_file) )
                throw new \Exception(sprintf(
                    "Cannot move $source_file into $target_file"
                ));
        }
    
        @chmod($target_file, 0777);
        $this->file_name = $new_file_name;
        $this->file_path = $target_file;
    }
    
    private function check_target_directory()
    {
        if( ! is_dir($this->save_path) )
            if( ! @mkdir($this->save_path, 0777, true) )
                throw new \Exception(sprintf(
                    "Can't create directory '%s'. Please contact the tech support staff.",
                    $this->save_path
                ));
        
        @chmod($this->save_path, 0777);
    }
    
    public function get_relative_path()
    {
        return "{$this->relative_path}/{$this->file_name}";
    }
    
    abstract public function get_thumbnail();
}
