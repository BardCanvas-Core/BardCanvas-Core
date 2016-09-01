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
            $this->ffmpeg_bin = "ffmpeg";
        else
            $this->ffmpeg_bin = rtrim($settings->get("engine.ffmpeg_path"), "/") . "/ffmpeg";
        
        $this->check_ffmpeg();
        
        if( $this->needs_conversion ) $this->convert();
    }
    
    private function check_ffmpeg()
    {
        $res = shell_exec("{$this->ffmpeg_bin} -version");
        
        if( ! stristr($res, "libavcodec") )
            throw new \Exception(
                "Can't get ffmpeg version! It is possible that ffmpeg is not installed or configured, " .
                "or the binary is not executable. " .
                "Shell call result: $res"
            );
    }
    
    private function convert()
    {
        $source  = $this->file_path;
        $target  = "/tmp/convert-" . basename($this->file_path) . ".mp4";
        $res     = shell_exec("{$this->ffmpeg_bin} -i '{$source}' '{$target}'");
        
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
        $source = $this->file_path;
        $target = dirname($source) . "/" . basename($source) . ".jpg";
        
        shell_exec("{$this->ffmpeg_bin} -i '{$source}' -vframes 1 -f image2 '{$target}'");
        
        if( ! file_exists($target) ) return "";
        
        $parts = @getimagesize($target);
        if( $parts ) $this->dimensions = "{$parts[0]}x{$parts[1]}";
        
        return $this->relative_path . "/" . basename($target);
    }
}
