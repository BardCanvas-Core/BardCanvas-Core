<?php
namespace hng2_media;

use hng2_tools\cli;

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
    
    /**
     * @param string $file_name
     * @param string $mime_type
     * @param string $file_path
     * 
     * @throws \Exception
     */
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
                $this->mime_type = mime_content_type($this->file_path);
            
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
        global $config;
        
        $source  = $this->file_path;
        $target  = "{$config->datafiles_location}/tmp/convert-" . basename($this->file_path) . ".mp4";
        $fflog   = "{$config->datafiles_location}/tmp/convert-" . basename($this->file_path) . ".log";
        $command = "{$this->ffmpeg_bin} -i '{$source}' -vcodec libx264 -preset veryfast -movflags +faststart '{$target}' > '$fflog' 2>&1";
        
        $start = time();
        shell_exec($command);
        $res = @file_get_contents($fflog);
        
        if( ! empty($res) ) $this->log_conversion($source, $target, $command, $res, $start);
        
        if( ! file_exists($target) )
        {
            $this->notify_error($source, $target, $command, $res, $start);
            @unlink($fflog);
            throw new \Exception("Can't convert {$this->file_name} to mp4!");
        }
        
        @unlink($fflog);
        $this->mime_type = $this->enforced_mime_type;
        $this->file_path = $target;
    }
    
    private function log_conversion($source, $target, $command, $ffmpeg_output, $start)
    {
        global $config, $account;
        
        if( empty($ffmpeg_output) ) $ffmpeg_output = "N/A";
        
        $command       = wordwrap($command, 130, "\n         ");
        $ffmpeg_output = wordwrap($ffmpeg_output, 130);
        
        $user_id   = $account->_exists ? $account->id_account   : "N/A";
        $user_name = $account->_exists ? $account->user_name    : "N/A";
        $user_nick = $account->_exists ? $account->display_name : "N/A";
        
        $ip   = get_remote_address();
        $host = @gethostbyaddr($ip);
        $loc  = get_geoip_location_with_isp($ip);
        $now  = date("Y-m-d H:i:s");
        $logd = date("Ymd");
        $logf = "{$config->logfiles_location}/video_conversions_log-{$logd}.log";
        $agnt = $_SERVER["HTTP_USER_AGENT"];
        $time = time() - $start;
    
        $opening = cli::color("=======================================================", cli::$forecolor_light_cyan) . "\n"
                 . cli::color("[$now] Video file converted successfully",                cli::$forecolor_light_cyan) . "\n"
                 . cli::color("=======================================================", cli::$forecolor_light_cyan);
        
        $message = unindent("
            $opening
             
            Owner:   #{$user_id} (@{$user_name} -{$user_nick})
            Input:   $source
            Output:  $target
            Command: $command
             
            File info:
            - Name: {$this->file_name}
            - Type: {$this->mime_type} ({$this->media_type})
            - Path: {$this->file_path}
             
            FFMPEG output:
            ---------------------------
            $ffmpeg_output
            ---------------------------
            Conversion time: $time seconds.
             
            Sent from: $ip
            Host:      $host
            Location:  $loc
            Browser:   $agnt
        ");
        
        @file_put_contents($logf, "$message\n\n", FILE_APPEND);
    }
    
    private function notify_error($source, $target, $command, $ffmpeg_output, $start)
    {
        global $config, $account;
        
        if( empty($ffmpeg_output) ) $ffmpeg_output = "N/A";
        
        $command       = wordwrap($command, 130, "\n         ");
        $ffmpeg_output = wordwrap($ffmpeg_output, 130);
        
        $now  = date("Ymd-His");
        $newt = "{$config->datafiles_location}/tmp/falied-upload-{$now}-{$this->file_name}";
        $copy = @copy($source, $newt) ? $newt : "N/A";
        
        $user_id   = $account->_exists ? $account->id_account   : "N/A";
        $user_name = $account->_exists ? $account->user_name    : "N/A";
        $user_nick = $account->_exists ? $account->display_name : "N/A";
        
        $ip   = get_remote_address();
        $host = @gethostbyaddr($ip);
        $loc  = get_geoip_location_with_isp($ip);
        $snm  = basename($source);
        $now  = date("Y-m-d H:i:s");
        $logd = date("Ymd");
        $logf = "{$config->logfiles_location}/video_conversion_errors-{$logd}.log";
        $agnt = $_SERVER["HTTP_USER_AGENT"];
        $time = time() - $start;
        
        $backtrace = "";
        foreach(debug_backtrace() as $backtrace_item) $backtrace .= $backtrace_item["file"] . ":" . $backtrace_item["line"] . "\n";
        $backtrace = trim($backtrace);
        
        $opening = cli::color("=======================================================", cli::$forecolor_yellow) . "\n"
                 . cli::color("[$now] Error attempting to convert video",                cli::$forecolor_yellow) . "\n"
                 . cli::color("=======================================================", cli::$forecolor_yellow);
        
        $message = unindent("
            $opening
             
            Owner:   #{$user_id} (@{$user_name} -{$user_nick})
            Input:   $source
            Output:  $target
            Command: $command
             
            File info:
            - Name: {$this->file_name}
            - Type: {$this->mime_type} ({$this->media_type})
            - Path: {$this->file_path}
             
            FFMPEG output:
            ---------------------------
            $ffmpeg_output
            ---------------------------
            Conversion time: $time seconds.
             
            Debug Backtrace:
            ---------------------------
            $backtrace
            ---------------------------
             
            Source saved as $copy
             
            Sent from: $ip
            Host:      $host
            Location:  $loc
            Browser:   $agnt
        ");
        
        $notification = unindent("
            Couldn't convert video '$snm' sent by $user_name.
            Please check the Video Conversion Errors log.
        ");
        
        @file_put_contents($logf, "$message\n\n", FILE_APPEND);
        broadcast_to_moderators("warning", $notification);
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
