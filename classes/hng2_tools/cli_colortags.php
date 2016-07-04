<?php
/**
 * Cli extender with pseudo-tag support
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_tools;

class cli_colortags extends cli
{
    /**
     * Outputs a string to the cli. If you send an array it will implode them
     * with a line break.
     *
     * You may use FOREGROUND color tags as shown below:
     *     cli_colortags::write("Some <purple>colored text</purple> to write!")
     *     cli_colortags::write("Some <purple>colored text with <green>nested</green> color</purple> to write!")
     *
     * Important: use $foreground and/or $background to properly wrap the entire output.
     *
     * Instead of this:
     *     cli_colortags::write("<yellow>Some <purple>colored text with <green>nested</green> color</purple> to write!</yellow>")
     *
     * Do this:
     *     cli_colortags::write("Some <purple>colored text with <green>nested</green> color</purple> to write!", cli::$forecolor_yellow)
     *
     * Tags are defined as per cli::$forecolor_*
     *
     * @author     Alejandro Caballero
     * @license    GPL License
     * @copyright  2015 Alejandro Caballero
     * @link       http://www.lavasoftworks.com
     *
     * @param string|array $text the text to output, or array of lines
     * @param string $foreground black, dark_gray, blue, light_blue, green, light_green, cyan, light_cyan, red, light_red, purple, light_purple, brown, yellow, light_gray, white
     * @param string $background black, red, green, yellow, blue, magenta, cyan, light_gray
     */
    public static function write($text = '', $foreground = NULL, $background = NULL)
    {
        $text = self::convert_colortags($text, $foreground, $background);
        parent::write($text, $foreground, $background);
    } # end function

    /**
     * Converts a color-tagged string to CLI colors. If you send an array it will implode them
     * with a line break.
     *
     * You may use FOREGROUND color tags as shown below:
     *     cli_colortags::write("Some <purple>colored text</purple> to write!")
     *     cli_colortags::write("Some <purple>colored text with <green>nested</green> color</purple> to write!")
     *
     * Important: use $foreground and/or $background to properly wrap the entire output.
     *
     * Instead of this:
     *     cli_colortags::write("<yellow>Some <purple>colored text with <green>nested</green> color</purple> to write!</yellow>")
     *
     * Do this:
     *     cli_colortags::write("Some <purple>colored text with <green>nested</green> color</purple> to write!", cli::$forecolor_yellow)
     *
     * Tags are defined as per cli::$forecolor_*
     *
     * @author     Alejandro Caballero
     * @license    GPL License
     * @copyright  2015 Alejandro Caballero
     * @link       http://www.lavasoftworks.com
     *
     * @param string|array $text the text to output, or array of lines
     * @param string $foreground black, dark_gray, blue, light_blue, green, light_green, cyan, light_cyan, red, light_red, purple, light_purple, brown, yellow, light_gray, white
     * @param string $background black, red, green, yellow, blue, magenta, cyan, light_gray
     *
     * @return string
     */
    public static function color($text = '', $foreground = NULL, $background = NULL)
    {
        $text = self::convert_colortags($text, $foreground, $background);
        return parent::color($text, $foreground, $background);
    }

    protected static function convert_colortags($text = '', $foreground = NULL, $background = NULL)
    {
        if (is_array($text)) $text = implode(PHP_EOL, $text);

        while (true) {
            if (! preg_match('#<(.*)>.*</\1>#i', $text, $matches) ) break;
            $color = $matches[1];
            $open_tag  = "<$color>";
            $close_tag = "</$color>";
            if (isset(parent::$foreground_colors[$color])) {
                $base_color     = $foreground ? "\033[".parent::$foreground_colors[$foreground]."m" : "\033[0m";
                $replaced_color = "\033[".parent::$foreground_colors[$color]."m";
                $text = str_replace($open_tag,  $replaced_color, $text);
                $text = str_replace($close_tag, "\033[0m",        $text);
            } else {
                $text = str_replace($open_tag,  "", $text);
                $text = str_replace($close_tag, "", $text);
            }
        }

        return $text;
    }
} # end class
