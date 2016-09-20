<?php
namespace hng2_media;

use GifFrameExtractor\GifFrameExtractor;

class item_manager_gif extends abstract_image_manager
{
    public function __construct($file_name, $mime_type, $file_path)
    {
        if( ! function_exists("gfuncs_getmakePNGthumbnail") )
            include_once ROOTPATH . "/includes/guncs.php";
        
        parent::__construct($file_name, $mime_type, $file_path);
    }
    
    public function get_thumbnail()
    {
        global $settings;
        
        $width = current(explode("x", $settings->get("engine.thumbnail_size", "460x220")));
        if( empty($width) ) $width = 460;
        
        $compression = $settings->get("engine.thumbnail_png_compression");
        if( empty($compression) ) $compression = 9;
        
        if( ! GifFrameExtractor::isAnimatedGif($this->file_path) )
        {
            /*
            $parts = @getimagesize($this->file_path);
            if( $parts ) $this->dimensions = "{$parts[0]}x{$parts[1]}";
            
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
            */
            
            return "{$this->relative_path}/"
                . $this->build_cropped_thumbnail($this->file_path, $this->save_path, THUMBNAILER_USE_WIDTH);
        }
        
        $gfe = new GifFrameExtractor();
        $gfe->extract($this->file_path);
        $frame = current($gfe->getFrameImages());
        
        if( ! is_dir($this->save_path) )
            if( ! @mkdir($this->save_path) )
                throw new \Exception("GIF Thumbnailer: Can't create target directory $this->save_path.");
        @chmod($this->save_path, 0777);
        
        $filename_parts = explode(".", basename($this->file_path)); array_pop($filename_parts);
        $file_name      = implode(".", $filename_parts);
        $thumbnail_file = $file_name . "-preview.png";
        
        if( ! @imagepng($frame, "{$this->save_path}/$thumbnail_file", $compression) )
            throw new \Exception("Thumbnailer: Can't save target file {$this->save_path}/$thumbnail_file");
        
        @chmod("{$this->save_path}/$thumbnail_file", 0777);
        
        $parts = @getimagesize("{$this->save_path}/$thumbnail_file");
        if( $parts ) $this->dimensions = "{$parts[0]}x{$parts[1]}";
        
        # return "{$this->relative_path}/{$thumbnail_file}";
        return "{$this->relative_path}/"
            . $this->build_cropped_thumbnail("{$this->save_path}/$thumbnail_file", $this->save_path, THUMBNAILER_USE_WIDTH);
    }
}
