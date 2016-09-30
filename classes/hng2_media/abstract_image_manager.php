<?php
namespace hng2_media;

abstract class abstract_image_manager extends abstract_item_manager
{
    protected $media_type = "image";
    
    protected $jpeg_resample_compression = 90;
    
    public function get_dimensions()
    {
        if( ! empty($this->dimensions) ) return $this->dimensions;
        
        $parts = @getimagesize($this->file_path);
        if( $parts ) $this->dimensions = "{$parts[0]}x{$parts[1]}";
        
        return $this->dimensions;
    }
    
    /**
     * @see http://stackoverflow.com/questions/7489742/php-read-exif-data-and-adjust-orientation
     */
    protected function fix_orientation()
    {
        $res = gfuncs_fix_jpeg_orientation($this->file_path, $this->jpeg_resample_compression);
        if( $res ) $this->size = filesize($this->file_path);
    }
}
