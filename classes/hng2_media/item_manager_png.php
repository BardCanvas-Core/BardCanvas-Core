<?php
namespace hng2_media;

class item_manager_png extends abstract_item_manager
{
    public function __construct($file_name, $mime_type, $file_path)
    {
        if( ! function_exists("gfuncs_getmakePNGthumbnail") )
            include_once ABSPATH . "/includes/guncs.php";
        
        parent::__construct($file_name, $mime_type, $file_path);
    }
    
    public function get_thumbnail()
    {
        global $settings;
        
        $width  = $settings->get("engine.thumbnail_width");
        if( empty($width) ) $width = 460;
        
        $compression = $settings->get("engine.thumbnail_png_compression");
        if( empty($compression) ) $compression = 9;
        
        return "{$this->relative_path}/" . gfuncs_getmakePNGthumbnail(
            $this->file_path,
            $this->save_path,
            $width,
            0,
            THUMBNAILER_USE_WIDTH,
            false,
            $compression,
            true
        );
    }
}
