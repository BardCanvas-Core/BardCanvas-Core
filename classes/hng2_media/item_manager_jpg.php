<?php
namespace hng2_media;

class item_manager_jpg extends abstract_item_manager
{
    public function __construct($file_name, $mime_type, $file_path)
    {
        if( ! function_exists("gfuncs_getmakethumbnail") )
            include_once ABSPATH . "/includes/guncs.php";
        
        parent::__construct($file_name, $mime_type, $file_path);
    }
    
    public function get_thumbnail()
    {
        global $settings;
        
        $width  = $settings->get("engine.thumbnail_width");
        if( empty($width) ) $width = 460;
        
        $compression = $settings->get("engine.thumbnail_jpg_compression");
        if( empty($compression) ) $compression = 90;
    
        return "{$this->relative_path}/" . gfuncs_getmakethumbnail(
            $this->file_path,
            $this->save_path,
            $width,
            0,
            THUMBNAILER_USE_WIDTH,
            false,
            $compression
        );
    }
}