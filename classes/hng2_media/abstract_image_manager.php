<?php
namespace hng2_media;

abstract class abstract_image_manager extends abstract_item_manager
{
    protected $media_type = "image";
    
    public function get_dimensions()
    {
        if( ! empty($this->dimensions) ) return $this->dimensions;
        
        $parts = @getimagesize($this->file_path);
        if( $parts ) $this->dimensions = "{$parts[0]}x{$parts[1]}";
        
        return $this->dimensions;
    }
}
