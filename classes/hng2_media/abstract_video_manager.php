<?php
namespace hng2_media;

abstract class abstract_video_manager extends abstract_item_manager
{
    protected $media_type = "video";
    
    public function __construct($file_name, $mime_type, $file_path)
    {
        parent::__construct($file_name, $mime_type, $file_path);
        
        throw new \Exception("Not yet implemented.");
    }
    
    public function get_thumbnail()
    {
        // TODO: Implement get_thumbnail() method.
    }
}
