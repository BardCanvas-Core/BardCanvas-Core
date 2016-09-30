<?php
namespace hng2_media;

abstract class abstract_item_manager
{
    protected $media_type; // OVERRIDE
    protected $dimensions; // OVERRIDE
    protected $size;       // OVERRIDE
    
    protected $enforced_mime_type = ""; // OVERRIDE IF NEEDED
    
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
            if( ! @copy($source_file, $target_file) )
                throw new \Exception(sprintf(
                    "Cannot move $source_file into $target_file"
                ));
            @unlink($source_file);
        }
        
        @chmod($target_file, 0777);
        $this->file_name = $new_file_name;
        $this->file_path = $target_file;
        $this->size      = filesize($target_file);
        
        $this->post_relocation_fixes();
    }
    
    protected function post_relocation_fixes()
    {
        // Override this function with file fixes after moving it into the repository
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
    
    public function get_type()
    {
        return $this->media_type;
    }
    
    public function get_final_mime_type()
    {
        if( ! empty($this->enforced_mime_type) )
            return $this->enforced_mime_type;
        else
            return $this->mime_type;
    }
    
    abstract public function get_thumbnail();
    
    /**
     * @param        $source_file
     * @param string $target_dir
     * @param string $dimension Dimension to use when resizing. Options: THUMBNAILER_USE_WIDTH, THUMBNAILER_USE_HEIGHT
     *
     * @return string basename of the file
     * @throws \Exception
     */
    protected function build_cropped_thumbnail($source_file, $target_dir = "", $dimension = "")
    {
        global $settings;
        
        $filename    = basename($source_file);
        $target_dir  = empty($target_dir) ? dirname($source_file) : $target_dir;
        $parts       = explode(".", basename($this->file_path));
        $extension   = array_pop($parts);
        $thumbnail   = implode(".", $parts) . "-thumbnail.{$extension}";
        $temp_file   = "/tmp/{$thumbnail}";
        
        if( file_exists("{$target_dir}/{$thumbnail}") ) return $thumbnail;
        
        if( ! @copy($source_file, $temp_file) )
            throw new \Exception(sprintf("Can't copy %s into %s!", $source_file, $temp_file));
        
        $dimensions = $settings->get("engine.thumbnail_size");
        if( empty($dimensions) ) $dimensions = "460x220";
        list($th_width, $th_height) = explode("x", $dimensions);
        
        $jpeg_quality = $settings->get("engine.thumbnail_jpg_compression");
        $png_quality  = $settings->get("engine.thumbnail_png_compression");
        if( empty($jpeg_quality) ) $jpeg_quality = 90;
        if( empty($png_quality)  ) $png_quality  = 9;
        
        list($width, $height) = getimagesize($temp_file);
        if( empty($dimension) ) $dimension  = $width > $height ? THUMBNAILER_USE_HEIGHT : THUMBNAILER_USE_WIDTH;
        $res = preg_match('/(.jpg|.jpeg)$/i', $filename)
            ? gfuncs_resample_jpg($temp_file, $target_dir, $th_width, $th_height, $dimension, false, $jpeg_quality,        true, $th_width, $th_height)
            : gfuncs_resample_png($temp_file, $target_dir, $th_width, $th_height, $dimension, false, $png_quality,  false, true, $th_width, $th_height)
        ;
        @unlink($temp_file);
        
        return $res;
    }
    
    public function get_dimensions()
    {
        return $this->dimensions;
    }
    
    public function get_size()
    {
        return $this->size;
    }
}
