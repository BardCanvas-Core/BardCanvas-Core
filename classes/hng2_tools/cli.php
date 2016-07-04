<?php
/**
 * Helper para shell scripts, extracto de kohana::cli_reloaded
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_tools;

class cli
{
    static $encode_to_utf8 = false;
    public static $output_file = "";
    public static $output_to_file_only = false;
    
    protected static $foreground_colors = array
    (
        'black'         => '0;30',
        'dark_gray'     => '1;30',
        'blue'          => '0;34',
        'light_blue'    => '1;34',
        'green'         => '0;32',
        'light_green'   => '1;32',
        'cyan'          => '0;36',
        'light_cyan'    => '1;36',
        'red'           => '0;31',
        'light_red'     => '1;31',
        'purple'        => '0;35',
        'light_purple'  => '1;35',
        'brown'         => '0;33',
        'yellow'        => '1;33',
        'light_gray'    => '0;37',
        'white'         => '1;37',
    );
    
    protected static $html_foreground_colors = array
    (
        'black'          => '0;30',
        'gray'           => '1;30',
        'blue'           => '0;34',
        'CornflowerBlue' => '1;34',
        'green'          => '0;32',
        'lime'           => '1;32',
        'teal'           => '0;36',
        'cyan'           => '1;36',
        'red'            => '0;31',
        'LightCoral'     => '1;31',
        'purple'         => '0;35',
        'magenta'        => '1;35',
        'brown'          => '0;33',
        'yellow'         => '1;33',
        'silver'         => '0;37',
        'white'          => '1;37',
    );
    
    public static $forecolor_black         = "black";
    public static $forecolor_blue          = "blue";
    public static $forecolor_light_blue    = "light_blue";
    public static $forecolor_green         = "green";
    public static $forecolor_light_green   = "light_green";
    public static $forecolor_cyan          = "cyan";
    public static $forecolor_light_cyan    = "light_cyan";
    public static $forecolor_red           = "red";
    public static $forecolor_light_red     = "light_red";
    public static $forecolor_purple        = "purple";
    public static $forecolor_light_purple  = "light_purple";
    public static $forecolor_brown         = "brown";
    public static $forecolor_yellow        = "yellow";
    public static $forecolor_light_gray    = "light_gray";
    public static $forecolor_white         = "white";
    
    protected static $background_colors = array
    (
        'black'         => '40',
        'red'           => '41',
        'green'         => '42',
        'yellow'        => '43',
        'blue'          => '44',
        'magenta'       => '45',
        'cyan'          => '46',
        'light_gray'    => '47',
    );
    
    protected static $html_background_colors = array
    (
        'black'         => '40',
        'red'           => '41',
        'green'         => '42',
        'yellow'        => '43',
        'blue'          => '44',
        'magenta'       => '45',
        'cyan'          => '46',
        'silver'        => '47',
    );
    
    public static $backcolor_black         = "black";
    public static $backcolor_red           = "red";
    public static $backcolor_green         = "green";
    public static $backcolor_yellow        = "yellow";
    public static $backcolor_blue          = "blue";
    public static $backcolor_magenta       = "magenta";
    public static $backcolor_cyan          = "cyan";
    public static $backcolor_light_gray    = "light_gray";
    
    /**
     * Returns the given text with the correct color codes for a foreground and
     * optionally a background color.
     *
     * @author     Fuel Development Team
     * @license    MIT License
     * @copyright  2010 - 2011 Fuel Development Team
     * @link       http://fuelphp.com
     *
     * @param string $text       the text to color
     * @param string $foreground the foreground color
     * @param string $background the background color
     *
     * @return string the color coded string
     *                
     * @throws \Exception
     */
    public static function color($text, $foreground, $background = NULL)
    {
        
        if (!array_key_exists($foreground, self::$foreground_colors))
            throw new \Exception('Invalid CLI foreground color: '.$foreground);
        
        if ($background !== NULL and !array_key_exists($background, self::$background_colors))
            throw new \Exception('Invalid CLI background color: '.$background);
        
        $string = "\033[".self::$foreground_colors[$foreground]."m";
        
        if ($background !== NULL)
            $string .= "\033[".self::$background_colors[$background]."m";
        
        $string .= $text."\033[0m";
        
        if(self::$encode_to_utf8) return utf8_encode($string);
        else                return $string;
    }
    
    /**
     * Outputs a string to the cli. If you send an array it will implode them
     * with a line break.
     *
     * @author     Fuel Development Team
     * @license    MIT License
     * @copyright  2010 - 2011 Fuel Development Team
     * @link       http://fuelphp.com
     * 
     * @param string|array $text the text to output, or array of lines
     * @param string $foreground black, dark_gray, blue, light_blue, green, light_green, cyan, light_cyan, red, light_red, purple, light_purple, brown, yellow, light_gray, white
     * @param string $background black, red, green, yellow, blue, magenta, cyan, light_gray 
     */
    public static function write($text = '', $foreground = NULL, $background = NULL)
    {
        if (is_array($text))
            $text = implode(PHP_EOL, $text);
        
        if ($foreground OR $background)
            $text = cli::color($text, $foreground, $background);
        
        $output = $text;
        if  (self::$encode_to_utf8 AND ($foreground OR $background)) $output = $text;
        elseif(self::$encode_to_utf8)                                $output = utf8_encode($text);
        
        if( ! self::$output_to_file_only ) echo $output;
        
        if( empty(self::$output_file) ) return;
        
        @file_put_contents(self::$output_file, $output, FILE_APPEND);
    }
    
    /**
    * Convierte codigo escapeado a HTML con spans de color
    * 
    * @param string $escaped_text
    * 
    * @return string
    */
    public static function to_html($escaped_text)
    {
        $return = $escaped_text;
        
        $return = htmlspecialchars($return);
        
        foreach(self::$html_foreground_colors as $name => $code)
            $return = str_replace("\033[" . $code . "m", "<span style=\"color: $name;\">", $return);
        
        foreach(self::$html_background_colors as $name => $code)
            $return = str_replace("\033[" . $code . "m", "<span style=\"background-color: $name;\">", $return);
        
        $return = preg_replace('/\<span style\="(.*)"\>\<span style\="(.*)"\>/', '<span style="$1 $2">', $return);
        
        $return = str_replace("\033[0m", "</span>", $return);
        
        $return = str_replace("\r", "", $return);
        $return = str_replace("\n", "<br>\n", $return);
        
        return $return;
    }
    
}
