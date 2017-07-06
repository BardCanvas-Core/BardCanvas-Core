<?php
namespace hng2_media;

abstract class abstract_video_manager extends abstract_item_manager
{
    protected $media_type  = "video";
    protected $ffmpeg_bin  = "";
    
    protected $enforced_mime_type = "video/mp4";
    
    /**
     * This needs to be overriden to true for non-web-standard input files
     * 
     * @var bool
     */
    protected $needs_conversion = false;
    
    public function __construct($file_name, $mime_type, $file_path)
    {
        global $settings;
        
        parent::__construct($file_name, $mime_type, $file_path);
        
        if( $settings->get("engine.ffmpeg_path") == "" )
            $this->ffmpeg_bin = "";
        else
            $this->ffmpeg_bin = rtrim($settings->get("engine.ffmpeg_path"), "/") . "/ffmpeg";
        
        if( empty($this->ffmpeg_bin) )
        {
            $this->mime_type  = "";
            
            if( function_exists("mime_content_type") )
                $this->mime_type = mime_content_type($this->file_name);
            
            if( empty($this->mime_type) )
            {
                $parts = explode(".", $this->file_name);
                $ext   = array_pop($parts);
                if( empty($ext) ) $this->mime_type = "application/octet-stream";
                else              $this->mime_type = "video/$ext";
            }
            
            $this->enforced_mime_type = $this->mime_type;
            $this->needs_conversion   = false;
        }
        
        if( $this->needs_conversion ) $this->convert();
    }
    
    private function convert()
    {
        $source  = $this->file_path;
        $target  = "/tmp/convert-" . basename($this->file_path) . ".mp4";
        $res     = shell_exec("{$this->ffmpeg_bin} -i '{$source}' '{$target}' > /dev/null 2>&1");
        
        if( ! file_exists($target) )
            throw new \Exception("Can't convert {$this->file_name} to mp4! ffmepg output: {$res}");
        
        $this->mime_type = $this->enforced_mime_type;
        $this->file_path = $target;
    }
    
    public function move_to_repository($new_file_name)
    {
        if( $this->needs_conversion )
        {
            $parts         = explode(".", $new_file_name); array_pop($parts);
            $new_file_name = implode(".", $parts) . ".mp4";
        }
        
        parent::move_to_repository($new_file_name);
    }
    
    /**
     * Note: thumbnails here keep aspect ratio without cropping.
     * 
     * @return string
     */
    public function get_thumbnail()
    {
        if( empty($this->ffmpeg_bin) ) return "";
        
        $source = $this->file_path;
        $target = dirname($source) . "/" . basename($source) . ".jpg";
        
        shell_exec("{$this->ffmpeg_bin} -i '{$source}' -vframes 1 -f image2 '{$target}' > /dev/null 2>&1");
        
        if( ! file_exists($target) ) return "";
        
        $parts = @getimagesize($target);
        if( $parts ) $this->dimensions = "{$parts[0]}x{$parts[1]}";
        
        return $this->relative_path . "/" . basename($target);
    }
}
