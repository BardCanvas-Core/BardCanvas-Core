<?php
namespace hng2_media;

abstract class abstract_ffmpeg_manager extends abstract_item_manager
{
    public function __construct($file_name, $mime_type, $file_path)
    {
        parent::__construct($file_name, $mime_type, $file_path);
        
        throw new \Exception("Not yet implemented.");
    }
    
    public function get_thumbnail()
    {
        
    }
}
