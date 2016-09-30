<?php
namespace hng2_media;

class item_manager_jpeg extends abstract_image_manager
{
    public function __construct($file_name, $mime_type, $file_path)
    {
        if( ! function_exists("gfuncs_getmakethumbnail") )
            include_once ROOTPATH . "/includes/guncs.php";
        
        parent::__construct($file_name, $mime_type, $file_path);
    }
    
    public function post_relocation_fixes()
    {
        $this->fix_orientation();
        
        parent::post_relocation_fixes();
    }
    
    public function get_thumbnail()
    {
        /*
        global $settings;
        
        $width = current(explode("x", $settings->get("engine.thumbnail_size", "460x220")));
        if( empty($width) ) $width = 460;
        
        $compression = $settings->get("engine.thumbnail_jpg_compression");
        if( empty($compression) ) $compression = 90;
        
        $parts = @getimagesize($this->file_path);
        if( $parts ) $this->dimensions = "{$parts[0]}x{$parts[1]}";
        
        return "{$this->relative_path}/" . gfuncs_getmakethumbnail(
            $this->file_path,
            $this->save_path,
            $width,
            0,
            THUMBNAILER_USE_WIDTH,
            false,
            $compression
        );
        */
        
        return "{$this->relative_path}/"
            . $this->build_cropped_thumbnail($this->file_path, $this->save_path, THUMBNAILER_USE_WIDTH);
    }
}
