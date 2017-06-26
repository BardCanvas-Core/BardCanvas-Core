<?php
/**
 * Text helper functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

/**
 * Extracts a part of the specified text, delimited by starter and finisher
 *
 * @param   string $starter
 * @param   string $finisher
 * @param   string $contents
 *
 * @returns string
 */
function extract_text_part($starter, $finisher, $contents)
{
    if( empty($starter) || empty($finisher) || empty($contents) ) return "";
    
    $start  = strpos($contents, $starter) + strlen($starter);
    $end    = strpos($contents, $finisher);
    $return = substr($contents, $start, $end - $start);
    
    return trim($return);
}

/**
 * Replaces searched eleements with replaced elements into a string
 *
 * @param string $text
 * @param mixed  $search
 * @param mixed  $replace
 *
 * @returns string
 */
function replace_escaped_vars($text, $search, $replace)
{
    $replaced_text = trim($text);
    if( empty($replaced_text) ) return "";
    
    $replaced_text = str_replace($search, $replace, $text);
    
    return $replaced_text;
} # end function

/**
 * Replaces all instances of the key elements of the passed array
 * with their corresponding values.
 *
 * @param string $text
 * @param array  $collection
 *
 * @returns string
 */
function replace_escaped_objects($text, array $collection)
{
    $replaced_text = trim($text);
    if( empty($replaced_text) ) return "";
    
    foreach($collection as $search => $replace)
        $replaced_text = str_replace($search, $replace, $replaced_text);
    
    return $replaced_text;
}

/**
 * Convert date to "time ago" string
 *
 * @see http://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
 *
 * @param   $date
 *
 * @returns string
 */
function time_elapsed_string($date)
{
    global $language;
    
    if( empty($date) ) return "";
    if( stristr($date, "0000-00-00") ) return "";
    
    $ptime = strtotime($date);
    $etime = time() - $ptime;
    
    if( $etime < 1 )
    {
        return '0 seconds';
    }
    
    $a = array(
        12 * 30 * 24 * 60 * 60 => $language->time->years,
        30 * 24 * 60 * 60      => $language->time->months,
        24 * 60 * 60           => $language->time->days,
        60 * 60                => $language->time->hours,
        60                     => $language->time->minutes,
        1                      => $language->time->seconds,
    );
    
    $return = "";
    foreach( $a as $secs => $str )
    {
        $d = $etime / $secs;
        if( $d >= 1 )
        {
            $r      = round($d);
            $return = $r . ' ' . $str . ($r > 1 ? '' : '') . ' ';
            break;
        }
    }
    
    $prefix = trim($language->time->ago_prefix); if( $prefix != "" ) $prefix = "{$prefix} ";
    $suffix = trim($language->time->ago);        if( $suffix != "" ) $suffix = " {$suffix}";
    
    return $prefix . trim($return) . $suffix;
}

/**
 * Convert date to "time ago" alternate string (1y 2mo 3d 4h 5m 6s)
 *
 * @see http://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
 *
 * @param   $date
 *
 * @returns string
 */
function time_elapsed_string_alternate($date)
{
    global $language;
    
    if( empty($date) ) return "";
    if( stristr($date, "0000-00-00") ) return "";
    
    $ptime = strtotime($date);
    $etime = time() - $ptime;
    
    if( $etime < 1 ) return '0s';
    
    $a = array(
        12 * 30 * 24 * 60 * 60 => $language->time->short->years,
        30 * 24 * 60 * 60      => $language->time->short->months,
        24 * 60 * 60           => $language->time->short->days,
        60 * 60                => $language->time->short->hours,
        60                     => $language->time->short->minutes,
        1                      => $language->time->short->seconds,
    );
    
    $return = "";
    foreach( $a as $secs => $str )
    {
        $d = $etime / $secs;
        if( $d >= 1 )
        {
            $r      = round($d);
            $return = $r . '' . $str . ($r > 1 ? '' : '') . ' ';
            break;
        }
    }
    
    return trim($return);
}

/**
 * Returns time of given date or short day version if today
 *
 * @param $input
 *
 * @return string
 */
function time_mini_string($input)
{
    $timestamp = strtotime($input);
    
    if( date("Y") > substr($input, 0, 4) )        # Another year = full date
        return utf8_encode(strftime("%b %e, %G", $timestamp));
    if( date("Y-m-d") == substr($input, 0, 10) )  # Today = time
        return utf8_encode(strftime("%H:%M", $timestamp));
    else                                          # Other day = day
        return utf8_encode(strftime("%b %e", $timestamp));
}

function time_today_string($input)
{
    global $language;
    
    $timestamp = strtotime($input);
    
    /** @noinspection PhpUnusedLocalVariableInspection */
    $prefix = "";
    $ampm   = date("H", $timestamp) < 12 ? $language->time->am : $language->time->pm;
    
    if( substr($input, 0, 10) == date("Y-m-d") )
        $prefix = $language->time->today;        # today
    elseif( substr($input, 0, 10) == date("Y-m-d", strtotime("yesterday")) )
        $prefix = $language->time->yesterday;    # yesterday
    else
        $prefix = utf8_encode(strftime("%b %e", $timestamp)); # other day
    
    return $prefix . " " . utf8_encode(strftime("%l:%M", $timestamp)) . " " . $ampm;
}

function time_complete_string($input)
{
    global $language;
    
    $timestamp = strtotime($input);
    
    return utf8_encode(strftime("%b %e, %G %l:%M ", $timestamp)) 
        . ( date("H", $timestamp) < 12 ? $language->time->am : $language->time->pm );
}

/**
 * Depending on the input:
 * - if today, returns hour
 * - if this month, returns weekday and hour
 * - if this year, returns full date
 *
 * @param      $input
 * @param bool $time_elapsed_if_today if true and today, will return "time ago" instead of hour
 *
 * @return string
 */
function get_selective_date($input, $time_elapsed_if_today = false)
{
    $timestamp = strtotime($input);
    
    if( date("Y-m-d") == substr($input, 0, 10) )        # Today = time
        return $time_elapsed_if_today
            ? time_elapsed_string($input)
            : utf8_encode(strftime("%H:%M", $timestamp));
    if( date("Y-m") == substr($input, 0, 7) )           # This month = weekday, time 
        return utf8_encode(strftime("%a %e, %H:%M", $timestamp));
    else                                                # Other day = full time
        return utf8_encode(strftime("%b %e %G, %H:%M", $timestamp));
}

/**
 * Return remaining time to reach a given date
 *
 * @param      $date
 * @param bool $in_time_format
 *
 * @return string
 */
function time_remaining_string($date, $in_time_format = false)
{
    global $language;
    
    $d = $language->time->short->days;
    $h = $language->time->short->hours;
    $m = $language->time->short->minutes;
    $s = $language->time->short->seconds;
    
    $now         = new DateTime();
    $future_date = new DateTime($date);
    
    $interval = $future_date->diff($now);
    
    $return = $interval->format("%a{$d} %h{$h} %i{$m} %s{$s}");
    $return = str_replace("0{$d}", "", $return);
    $return = str_replace(" 0{$h}", "", $return);
    $return = str_replace(" 0{$m}", "", $return);
    
    if( $in_time_format )
    {
        $return = str_replace(array($d, $h, $m, $s), ":", $return);
        $return = str_replace(" ", "", $return);
        
        $sanitized = "";
        $parts     = explode(":", $return);
        
        foreach( $parts as $part ) $sanitized .= sprintf("%02.0f", $part) . ":";
        
        $sanitized = trim($sanitized, ":");
        $return    = trim($sanitized);
    }
    
    return trim($return);
}

function timeago_span($date, $added_style = "")
{
    list($date, $time) = explode(" ", $date);
    
    return "<span class='timeago' title='{$date}T{$time}Z' style='{$added_style}'></span>";
}

function rounded_trimming($amount, $digits = 0, $comma_separated = false)
{
    $amount = number_format($amount, $digits);
    if( ! $comma_separated ) $amount = str_replace(",", "", $amount);
    if( stristr($amount, ".") !== false ) $amount = rtrim($amount, "0");
    $amount = rtrim($amount, ".");
    if( $amount == "" ) $amount = 0;
    
    return $amount;
}

function convert_to_hashtag($input)
{
    $input = remove_filesystem_invalid_chars($input);
    $input = str_replace("-", "", $input);
    $input = str_replace(".", "", $input);
    $input = str_replace(" ", "", $input);
    
    return $input;
}

function remove_filesystem_invalid_chars($filename)
{
    $special_chars = array(
        "…",
        "?",
        "%",
        "[",
        "]",
        "/",
        "\\",
        "=",
        "<",
        ">",
        ":",
        ";",
        ",",
        "'",
        "\"",
        "&",
        "$",
        "#",
        "*",
        "(",
        ")",
        "|",
        "~",
        "`",
        "!",
        "{",
        "}",
        chr(0),
    );
    $filename      = remove_quotation_marks($filename);
    $filename      = wp_remove_accents($filename);
    $filename      = preg_replace("#\x{00a0}#siu", ' ', $filename);
    $filename      = str_replace($special_chars, '', $filename);
    $filename      = str_replace(array('%20', '+'), ' ', $filename);
    $filename      = preg_replace('/[\r\n\t]+/', ' ', $filename);
    $filename      = preg_replace('/ +/', ' ', $filename);
    $filename      = trim($filename, '.-_ ');
    
    return $filename;
}

/**
 * Sanitizes a filename, replacing whitespace with dashes.
 *
 * Removes special characters that are illegal in filenames on certain
 * operating systems and special characters requiring special escaping
 * to manipulate at the command line. Replaces spaces and consecutive
 * dashes with a single dash. Trims period, dash and underscore from beginning
 * and end of filename.
 *
 * @see wordpress/formating.php
 *
 * @param string $filename The filename to be sanitized
 * @param bool   $to_lower_case
 *
 * @return string The sanitized filename
 */
function wp_sanitize_filename($filename, $to_lower_case = true)
{
    $special_chars = array(
        "…",
        "?",
        "[",
        "]",
        "/",
        "\\",
        "=",
        "<",
        ">",
        ":",
        ";",
        ",",
        "'",
        "\"",
        "&",
        "$",
        "#",
        "*",
        "(",
        ")",
        "|",
        "~",
        "`",
        "!",
        "{",
        "}",
        chr(0),
    );
    $filename      = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', "", $filename);
    $filename      = remove_quotation_marks($filename);
    $filename      = wp_remove_accents($filename);
    $filename      = preg_replace("#\x{00a0}#siu", ' ', $filename);
    $filename      = str_replace($special_chars, '', $filename);
    $filename      = str_replace(array('%20', '+'), '-', $filename);
    $filename      = preg_replace('/[\r\n\t -]+/', '-', $filename);
    $filename      = str_replace("-", "_", $filename);
    $filename      = trim($filename, '.-_ ');
    
    if( $to_lower_case ) $filename = strtolower($filename);
    
    return $filename;
}

/**
 * http://stackoverflow.com/questions/20025030/convert-all-types-of-smart-quotes-with-php
 *
 * @param $str
 *
 * @return string
 */
function remove_quotation_marks($str)
{
    $chr_map = array(
        // Windows codepage 1252
        "\xC2\x82"     => "", // U+0082⇒U+201A single low-9 quotation mark
        "\xC2\x84"     => '', // U+0084⇒U+201E double low-9 quotation mark
        "\xC2\x8B"     => "", // U+008B⇒U+2039 single left-pointing angle quotation mark
        "\xC2\x91"     => "", // U+0091⇒U+2018 left single quotation mark
        "\xC2\x92"     => "", // U+0092⇒U+2019 right single quotation mark
        "\xC2\x93"     => '', // U+0093⇒U+201C left double quotation mark
        "\xC2\x94"     => '', // U+0094⇒U+201D right double quotation mark
        "\xC2\x9B"     => "", // U+009B⇒U+203A single right-pointing angle quotation mark
        
        // Regular Unicode     // U+0022 quotation mark (")
        // U+0027 apostrophe     (')
        "\xC2\xAB"     => '', // U+00AB left-pointing double angle quotation mark
        "\xC2\xBB"     => '', // U+00BB right-pointing double angle quotation mark
        "\xE2\x80\x98" => "", // U+2018 left single quotation mark
        "\xE2\x80\x99" => "", // U+2019 right single quotation mark
        "\xE2\x80\x9A" => "", // U+201A single low-9 quotation mark
        "\xE2\x80\x9B" => "", // U+201B single high-reversed-9 quotation mark
        "\xE2\x80\x9C" => '', // U+201C left double quotation mark
        "\xE2\x80\x9D" => '', // U+201D right double quotation mark
        "\xE2\x80\x9E" => '', // U+201E double low-9 quotation mark
        "\xE2\x80\x9F" => '', // U+201F double high-reversed-9 quotation mark
        "\xE2\x80\xB9" => "", // U+2039 single left-pointing angle quotation mark
        "\xE2\x80\xBA" => "", // U+203A single right-pointing angle quotation mark
    );
    $chr     = array_keys($chr_map); // but: for efficiency you should
    $rpl     = array_values($chr_map); // pre-calculate these two arrays
    $str     = str_replace($chr, $rpl, html_entity_decode($str, ENT_QUOTES, "UTF-8"));
    
    return $str;
}

/**
 * Converts all accent characters to ASCII characters.
 *
 * If there are no accent characters, then the string given is just returned.
 *
 * @see wordpress/formating.php
 *
 * @param string $string Text that might have accent characters
 *
 * @return string Filtered string with replaced "nice" characters.
 */
function wp_remove_accents($string)
{
    global $config;
    if( ! preg_match('/[\x80-\xff]/', $string) )
        return $string;
    
    if( wp_seems_like_utf8($string) )
    {
        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(194) . chr(170)            => 'a',
            chr(194) . chr(186)            => 'o',
            chr(195) . chr(128)            => 'A',
            chr(195) . chr(129)            => 'A',
            chr(195) . chr(130)            => 'A',
            chr(195) . chr(131)            => 'A',
            chr(195) . chr(132)            => 'A',
            chr(195) . chr(133)            => 'A',
            chr(195) . chr(134)            => 'AE',
            chr(195) . chr(135)            => 'C',
            chr(195) . chr(136)            => 'E',
            chr(195) . chr(137)            => 'E',
            chr(195) . chr(138)            => 'E',
            chr(195) . chr(139)            => 'E',
            chr(195) . chr(140)            => 'I',
            chr(195) . chr(141)            => 'I',
            chr(195) . chr(142)            => 'I',
            chr(195) . chr(143)            => 'I',
            chr(195) . chr(144)            => 'D',
            chr(195) . chr(145)            => 'N',
            chr(195) . chr(146)            => 'O',
            chr(195) . chr(147)            => 'O',
            chr(195) . chr(148)            => 'O',
            chr(195) . chr(149)            => 'O',
            chr(195) . chr(150)            => 'O',
            chr(195) . chr(153)            => 'U',
            chr(195) . chr(154)            => 'U',
            chr(195) . chr(155)            => 'U',
            chr(195) . chr(156)            => 'U',
            chr(195) . chr(157)            => 'Y',
            chr(195) . chr(158)            => 'TH',
            chr(195) . chr(159)            => 's',
            chr(195) . chr(160)            => 'a',
            chr(195) . chr(161)            => 'a',
            chr(195) . chr(162)            => 'a',
            chr(195) . chr(163)            => 'a',
            chr(195) . chr(164)            => 'a',
            chr(195) . chr(165)            => 'a',
            chr(195) . chr(166)            => 'ae',
            chr(195) . chr(167)            => 'c',
            chr(195) . chr(168)            => 'e',
            chr(195) . chr(169)            => 'e',
            chr(195) . chr(170)            => 'e',
            chr(195) . chr(171)            => 'e',
            chr(195) . chr(172)            => 'i',
            chr(195) . chr(173)            => 'i',
            chr(195) . chr(174)            => 'i',
            chr(195) . chr(175)            => 'i',
            chr(195) . chr(176)            => 'd',
            chr(195) . chr(177)            => 'n',
            chr(195) . chr(178)            => 'o',
            chr(195) . chr(179)            => 'o',
            chr(195) . chr(180)            => 'o',
            chr(195) . chr(181)            => 'o',
            chr(195) . chr(182)            => 'o',
            chr(195) . chr(184)            => 'o',
            chr(195) . chr(185)            => 'u',
            chr(195) . chr(186)            => 'u',
            chr(195) . chr(187)            => 'u',
            chr(195) . chr(188)            => 'u',
            chr(195) . chr(189)            => 'y',
            chr(195) . chr(190)            => 'th',
            chr(195) . chr(191)            => 'y',
            chr(195) . chr(152)            => 'O',
            // Decompositions for Latin Extended-A
            chr(196) . chr(128)            => 'A',
            chr(196) . chr(129)            => 'a',
            chr(196) . chr(130)            => 'A',
            chr(196) . chr(131)            => 'a',
            chr(196) . chr(132)            => 'A',
            chr(196) . chr(133)            => 'a',
            chr(196) . chr(134)            => 'C',
            chr(196) . chr(135)            => 'c',
            chr(196) . chr(136)            => 'C',
            chr(196) . chr(137)            => 'c',
            chr(196) . chr(138)            => 'C',
            chr(196) . chr(139)            => 'c',
            chr(196) . chr(140)            => 'C',
            chr(196) . chr(141)            => 'c',
            chr(196) . chr(142)            => 'D',
            chr(196) . chr(143)            => 'd',
            chr(196) . chr(144)            => 'D',
            chr(196) . chr(145)            => 'd',
            chr(196) . chr(146)            => 'E',
            chr(196) . chr(147)            => 'e',
            chr(196) . chr(148)            => 'E',
            chr(196) . chr(149)            => 'e',
            chr(196) . chr(150)            => 'E',
            chr(196) . chr(151)            => 'e',
            chr(196) . chr(152)            => 'E',
            chr(196) . chr(153)            => 'e',
            chr(196) . chr(154)            => 'E',
            chr(196) . chr(155)            => 'e',
            chr(196) . chr(156)            => 'G',
            chr(196) . chr(157)            => 'g',
            chr(196) . chr(158)            => 'G',
            chr(196) . chr(159)            => 'g',
            chr(196) . chr(160)            => 'G',
            chr(196) . chr(161)            => 'g',
            chr(196) . chr(162)            => 'G',
            chr(196) . chr(163)            => 'g',
            chr(196) . chr(164)            => 'H',
            chr(196) . chr(165)            => 'h',
            chr(196) . chr(166)            => 'H',
            chr(196) . chr(167)            => 'h',
            chr(196) . chr(168)            => 'I',
            chr(196) . chr(169)            => 'i',
            chr(196) . chr(170)            => 'I',
            chr(196) . chr(171)            => 'i',
            chr(196) . chr(172)            => 'I',
            chr(196) . chr(173)            => 'i',
            chr(196) . chr(174)            => 'I',
            chr(196) . chr(175)            => 'i',
            chr(196) . chr(176)            => 'I',
            chr(196) . chr(177)            => 'i',
            chr(196) . chr(178)            => 'IJ',
            chr(196) . chr(179)            => 'ij',
            chr(196) . chr(180)            => 'J',
            chr(196) . chr(181)            => 'j',
            chr(196) . chr(182)            => 'K',
            chr(196) . chr(183)            => 'k',
            chr(196) . chr(184)            => 'k',
            chr(196) . chr(185)            => 'L',
            chr(196) . chr(186)            => 'l',
            chr(196) . chr(187)            => 'L',
            chr(196) . chr(188)            => 'l',
            chr(196) . chr(189)            => 'L',
            chr(196) . chr(190)            => 'l',
            chr(196) . chr(191)            => 'L',
            chr(197) . chr(128)            => 'l',
            chr(197) . chr(129)            => 'L',
            chr(197) . chr(130)            => 'l',
            chr(197) . chr(131)            => 'N',
            chr(197) . chr(132)            => 'n',
            chr(197) . chr(133)            => 'N',
            chr(197) . chr(134)            => 'n',
            chr(197) . chr(135)            => 'N',
            chr(197) . chr(136)            => 'n',
            chr(197) . chr(137)            => 'N',
            chr(197) . chr(138)            => 'n',
            chr(197) . chr(139)            => 'N',
            chr(197) . chr(140)            => 'O',
            chr(197) . chr(141)            => 'o',
            chr(197) . chr(142)            => 'O',
            chr(197) . chr(143)            => 'o',
            chr(197) . chr(144)            => 'O',
            chr(197) . chr(145)            => 'o',
            chr(197) . chr(146)            => 'OE',
            chr(197) . chr(147)            => 'oe',
            chr(197) . chr(148)            => 'R',
            chr(197) . chr(149)            => 'r',
            chr(197) . chr(150)            => 'R',
            chr(197) . chr(151)            => 'r',
            chr(197) . chr(152)            => 'R',
            chr(197) . chr(153)            => 'r',
            chr(197) . chr(154)            => 'S',
            chr(197) . chr(155)            => 's',
            chr(197) . chr(156)            => 'S',
            chr(197) . chr(157)            => 's',
            chr(197) . chr(158)            => 'S',
            chr(197) . chr(159)            => 's',
            chr(197) . chr(160)            => 'S',
            chr(197) . chr(161)            => 's',
            chr(197) . chr(162)            => 'T',
            chr(197) . chr(163)            => 't',
            chr(197) . chr(164)            => 'T',
            chr(197) . chr(165)            => 't',
            chr(197) . chr(166)            => 'T',
            chr(197) . chr(167)            => 't',
            chr(197) . chr(168)            => 'U',
            chr(197) . chr(169)            => 'u',
            chr(197) . chr(170)            => 'U',
            chr(197) . chr(171)            => 'u',
            chr(197) . chr(172)            => 'U',
            chr(197) . chr(173)            => 'u',
            chr(197) . chr(174)            => 'U',
            chr(197) . chr(175)            => 'u',
            chr(197) . chr(176)            => 'U',
            chr(197) . chr(177)            => 'u',
            chr(197) . chr(178)            => 'U',
            chr(197) . chr(179)            => 'u',
            chr(197) . chr(180)            => 'W',
            chr(197) . chr(181)            => 'w',
            chr(197) . chr(182)            => 'Y',
            chr(197) . chr(183)            => 'y',
            chr(197) . chr(184)            => 'Y',
            chr(197) . chr(185)            => 'Z',
            chr(197) . chr(186)            => 'z',
            chr(197) . chr(187)            => 'Z',
            chr(197) . chr(188)            => 'z',
            chr(197) . chr(189)            => 'Z',
            chr(197) . chr(190)            => 'z',
            chr(197) . chr(191)            => 's',
            // Decompositions for Latin Extended-B
            chr(200) . chr(152)            => 'S',
            chr(200) . chr(153)            => 's',
            chr(200) . chr(154)            => 'T',
            chr(200) . chr(155)            => 't',
            // Euro Sign
            chr(226) . chr(130) . chr(172) => 'E',
            // GBP (Pound) Sign
            chr(194) . chr(163)            => '',
            // Vowels with diacritic (Vietnamese)
            // unmarked
            chr(198) . chr(160)            => 'O',
            chr(198) . chr(161)            => 'o',
            chr(198) . chr(175)            => 'U',
            chr(198) . chr(176)            => 'u',
            // grave accent
            chr(225) . chr(186) . chr(166) => 'A',
            chr(225) . chr(186) . chr(167) => 'a',
            chr(225) . chr(186) . chr(176) => 'A',
            chr(225) . chr(186) . chr(177) => 'a',
            chr(225) . chr(187) . chr(128) => 'E',
            chr(225) . chr(187) . chr(129) => 'e',
            chr(225) . chr(187) . chr(146) => 'O',
            chr(225) . chr(187) . chr(147) => 'o',
            chr(225) . chr(187) . chr(156) => 'O',
            chr(225) . chr(187) . chr(157) => 'o',
            chr(225) . chr(187) . chr(170) => 'U',
            chr(225) . chr(187) . chr(171) => 'u',
            chr(225) . chr(187) . chr(178) => 'Y',
            chr(225) . chr(187) . chr(179) => 'y',
            // hook
            chr(225) . chr(186) . chr(162) => 'A',
            chr(225) . chr(186) . chr(163) => 'a',
            chr(225) . chr(186) . chr(168) => 'A',
            chr(225) . chr(186) . chr(169) => 'a',
            chr(225) . chr(186) . chr(178) => 'A',
            chr(225) . chr(186) . chr(179) => 'a',
            chr(225) . chr(186) . chr(186) => 'E',
            chr(225) . chr(186) . chr(187) => 'e',
            chr(225) . chr(187) . chr(130) => 'E',
            chr(225) . chr(187) . chr(131) => 'e',
            chr(225) . chr(187) . chr(136) => 'I',
            chr(225) . chr(187) . chr(137) => 'i',
            chr(225) . chr(187) . chr(142) => 'O',
            chr(225) . chr(187) . chr(143) => 'o',
            chr(225) . chr(187) . chr(148) => 'O',
            chr(225) . chr(187) . chr(149) => 'o',
            chr(225) . chr(187) . chr(158) => 'O',
            chr(225) . chr(187) . chr(159) => 'o',
            chr(225) . chr(187) . chr(166) => 'U',
            chr(225) . chr(187) . chr(167) => 'u',
            chr(225) . chr(187) . chr(172) => 'U',
            chr(225) . chr(187) . chr(173) => 'u',
            chr(225) . chr(187) . chr(182) => 'Y',
            chr(225) . chr(187) . chr(183) => 'y',
            // tilde
            chr(225) . chr(186) . chr(170) => 'A',
            chr(225) . chr(186) . chr(171) => 'a',
            chr(225) . chr(186) . chr(180) => 'A',
            chr(225) . chr(186) . chr(181) => 'a',
            chr(225) . chr(186) . chr(188) => 'E',
            chr(225) . chr(186) . chr(189) => 'e',
            chr(225) . chr(187) . chr(132) => 'E',
            chr(225) . chr(187) . chr(133) => 'e',
            chr(225) . chr(187) . chr(150) => 'O',
            chr(225) . chr(187) . chr(151) => 'o',
            chr(225) . chr(187) . chr(160) => 'O',
            chr(225) . chr(187) . chr(161) => 'o',
            chr(225) . chr(187) . chr(174) => 'U',
            chr(225) . chr(187) . chr(175) => 'u',
            chr(225) . chr(187) . chr(184) => 'Y',
            chr(225) . chr(187) . chr(185) => 'y',
            // acute accent
            chr(225) . chr(186) . chr(164) => 'A',
            chr(225) . chr(186) . chr(165) => 'a',
            chr(225) . chr(186) . chr(174) => 'A',
            chr(225) . chr(186) . chr(175) => 'a',
            chr(225) . chr(186) . chr(190) => 'E',
            chr(225) . chr(186) . chr(191) => 'e',
            chr(225) . chr(187) . chr(144) => 'O',
            chr(225) . chr(187) . chr(145) => 'o',
            chr(225) . chr(187) . chr(154) => 'O',
            chr(225) . chr(187) . chr(155) => 'o',
            chr(225) . chr(187) . chr(168) => 'U',
            chr(225) . chr(187) . chr(169) => 'u',
            // dot below
            chr(225) . chr(186) . chr(160) => 'A',
            chr(225) . chr(186) . chr(161) => 'a',
            chr(225) . chr(186) . chr(172) => 'A',
            chr(225) . chr(186) . chr(173) => 'a',
            chr(225) . chr(186) . chr(182) => 'A',
            chr(225) . chr(186) . chr(183) => 'a',
            chr(225) . chr(186) . chr(184) => 'E',
            chr(225) . chr(186) . chr(185) => 'e',
            chr(225) . chr(187) . chr(134) => 'E',
            chr(225) . chr(187) . chr(135) => 'e',
            chr(225) . chr(187) . chr(138) => 'I',
            chr(225) . chr(187) . chr(139) => 'i',
            chr(225) . chr(187) . chr(140) => 'O',
            chr(225) . chr(187) . chr(141) => 'o',
            chr(225) . chr(187) . chr(152) => 'O',
            chr(225) . chr(187) . chr(153) => 'o',
            chr(225) . chr(187) . chr(162) => 'O',
            chr(225) . chr(187) . chr(163) => 'o',
            chr(225) . chr(187) . chr(164) => 'U',
            chr(225) . chr(187) . chr(165) => 'u',
            chr(225) . chr(187) . chr(176) => 'U',
            chr(225) . chr(187) . chr(177) => 'u',
            chr(225) . chr(187) . chr(180) => 'Y',
            chr(225) . chr(187) . chr(181) => 'y',
            // Vowels with diacritic (Chinese, Hanyu Pinyin)
            chr(201) . chr(145)            => 'a',
            // macron
            chr(199) . chr(149)            => 'U',
            chr(199) . chr(150)            => 'u',
            // acute accent
            chr(199) . chr(151)            => 'U',
            chr(199) . chr(152)            => 'u',
            // caron
            chr(199) . chr(141)            => 'A',
            chr(199) . chr(142)            => 'a',
            chr(199) . chr(143)            => 'I',
            chr(199) . chr(144)            => 'i',
            chr(199) . chr(145)            => 'O',
            chr(199) . chr(146)            => 'o',
            chr(199) . chr(147)            => 'U',
            chr(199) . chr(148)            => 'u',
            chr(199) . chr(153)            => 'U',
            chr(199) . chr(154)            => 'u',
            // grave accent
            chr(199) . chr(155)            => 'U',
            chr(199) . chr(156)            => 'u',
        );
        
        // Used for locale-specific rules
        $language_cookie_name = $config->language_cookie_var;
        $locale = $_COOKIE[$language_cookie_name];
        
        if( 'de_DE' == $locale )
        {
            $chars[chr(195) . chr(132)] = 'Ae';
            $chars[chr(195) . chr(164)] = 'ae';
            $chars[chr(195) . chr(150)] = 'Oe';
            $chars[chr(195) . chr(182)] = 'oe';
            $chars[chr(195) . chr(156)] = 'Ue';
            $chars[chr(195) . chr(188)] = 'ue';
            $chars[chr(195) . chr(159)] = 'ss';
        }
        elseif( 'da_DK' === $locale )
        {
            $chars[chr(195) . chr(134)] = 'Ae';
            $chars[chr(195) . chr(166)] = 'ae';
            $chars[chr(195) . chr(152)] = 'Oe';
            $chars[chr(195) . chr(184)] = 'oe';
            $chars[chr(195) . chr(133)] = 'Aa';
            $chars[chr(195) . chr(165)] = 'aa';
        }
        
        $string = strtr($string, $chars);
    }
    else
    {
        $chars = array();
        // Assume ISO-8859-1 if not UTF-8
        $chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158)
            . chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194)
            . chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202)
            . chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210)
            . chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218)
            . chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227)
            . chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235)
            . chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243)
            . chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251)
            . chr(252) . chr(253) . chr(255);
        
        $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
        
        $string              = strtr($string, $chars['in'], $chars['out']);
        $double_chars        = array();
        $double_chars['in']  = array(
            chr(140),
            chr(156),
            chr(198),
            chr(208),
            chr(222),
            chr(223),
            chr(230),
            chr(240),
            chr(254),
        );
        $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
        $string              = str_replace($double_chars['in'], $double_chars['out'], $string);
    }
    
    return $string;
}

/**
 * Checks to see if a string is utf8 encoded.
 *
 * NOTE: This function checks for 5-Byte sequences, UTF8
 *       has Bytes Sequences with a maximum length of 4.
 *
 * @author bmorel at ssi dot fr (modified)
 * @see    wordpress/formating.php
 *
 * @param string $str The string to be checked
 *
 * @return bool True if $str fits a UTF-8 model, false otherwise.
 */
function wp_seems_like_utf8($str)
{
    wp_mbstring_binary_safe_encoding();
    $length = strlen($str);
    wp_reset_mbstring_encoding();
    for( $i = 0; $i < $length; $i++ )
    {
        $c = ord($str[$i]);
        if( $c < 0x80 ) $n = 0; // 0bbbbbbb
        elseif( ($c & 0xE0) == 0xC0 ) $n = 1; // 110bbbbb
        elseif( ($c & 0xF0) == 0xE0 ) $n = 2; // 1110bbbb
        elseif( ($c & 0xF8) == 0xF0 ) $n = 3; // 11110bbb
        elseif( ($c & 0xFC) == 0xF8 ) $n = 4; // 111110bb
        elseif( ($c & 0xFE) == 0xFC ) $n = 5; // 1111110b
        else return false; // Does not match any model
        for( $j = 0; $j < $n; $j++ )
        { // n bytes matching 10bbbbbb follow ?
            if( (++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80) )
                return false;
        }
    }
    
    return true;
}

/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 * @see wordpress/formating.php
 * @see wp_reset_mbstring_encoding()
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 *                    Default false.
 */
function wp_mbstring_binary_safe_encoding($reset = false)
{
    static $encodings = array();
    static $overloaded = null;
    
    if( is_null($overloaded) )
        $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
    
    if( false === $overloaded )
        return;
    
    if( ! $reset )
    {
        $encoding = mb_internal_encoding();
        array_push($encodings, $encoding);
        mb_internal_encoding('ISO-8859-1');
    }
    
    if( $reset && $encodings )
    {
        $encoding = array_pop($encodings);
        mb_internal_encoding($encoding);
    }
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see   wp_mbstring_binary_safe_encoding()
 *
 * @since 3.7.0
 */
function wp_reset_mbstring_encoding()
{
    wp_mbstring_binary_safe_encoding(true);
}

function make_excerpt_of($string, $length = 30, $with_ellipsis = true)
{
    # Uncomment below to allow a single image go through (usual when posting reactions in comments)
    # if( strlen($string) <= $length )
    # {
    #     $test = trim(strip_tags($string, "<img>"));
    #     if( ! empty($test) ) return $test;
    #     else                 return trim($string);
    # }
    
    $excerpt = strip_tags($string);
    $excerpt = preg_replace('/\s+/', " ", $excerpt);
    $excerpt = trim($excerpt);
    
    if( strlen($excerpt) <= $length ) return $excerpt;
    
    $excerpt = substr($excerpt, 0, $length - 1);
    if( stristr($excerpt, " ") !== false) $excerpt = substr($excerpt, 0, strrpos($excerpt, " "));
    $excerpt = rtrim($excerpt, "-_.:,;");
    if( $with_ellipsis) $excerpt .= "…";
    
    return $excerpt;
}

function unindent($text)
{
    $text = "\n    $text";
    $text = preg_replace('/\n\s+/', "\n", $text);
    $text = trim($text);
    
    return $text;
}

/**
 * Adds a CDATA property to a SimpleXML node
 * 
 * @see http://stackoverflow.com/a/27306705
 * 
 * @param string $name
 * @param string $value
 * @param \SimpleXMLElement $parent
 *
 * @return \SimpleXMLElement
 */
function add_cdata_node($name, $value, &$parent)
{
    $child = $parent->addChild($name);
    
    if( ! is_null($child) )
    {
        $child_node = dom_import_simplexml($child);
        $child_owner = $child_node->ownerDocument;
        $child_node->appendChild($child_owner->createCDATASection($value));
    }
    
    return $child;
}

/**
 * Modified version of WordPress get_shortcode_regex
 *
 * @see https://developer.wordpress.org/reference/functions/get_shortcode_regex/
 *
 * @param $tagname
 * @return string
 */
function wp_get_shortcode_regex($tagname)
{
    return
        '\\['                              // Opening bracket
        . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
        . "($tagname)"                       // 2: Shortcode name
        . '(?![\\w-])'                       // Not followed by word character or hyphen
        . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
        .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
        .     '(?:'
        .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
        .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
        .     ')*?'
        . ')'
        . '(?:'
        .     '(\\/)'                        // 4: Self closing tag ...
        .     '\\]'                          // ... and closing bracket
        . '|'
        .     '\\]'                          // Closing bracket
        .     '(?:'
        .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
        .             '[^\\[]*+'             // Not an opening bracket
        .             '(?:'
        .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
        .                 '[^\\[]*+'         // Not an opening bracket
        .             ')*+'
        .         ')'
        .         '\\[\\/\\2\\]'             // Closing shortcode tag
        .     ')?'
        . ')'
        . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
}

/**
 * Retrieve the shortcode attributes regex.
 * Imported from Wordpress
 * 
 * @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/shortcodes.php
 *
 * @since 4.4.0
 *
 * @return string The shortcode attribute regular expression
 */
function wp_get_shortcode_atts_regex()
{
    return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
}

/**
 * Modified version of Wordpress shortcode_parse_atts
 * Retrieve all attributes from the shortcodes tag.
 *
 * The attributes list has the attribute name as the key and the value of the
 * attribute as the value in the key/value pair. This allows for easier
 * retrieval of the attributes, since all attributes have to be known.
 *
 * @see https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/shortcodes.php
 * 
 * @since 2.5.0
 *
 * @param string $text
 * @return array|string List of attribute values.
 *                      Returns empty array if trim( $text ) == '""'.
 *                      Returns empty string if trim( $text ) == ''.
 *                      All other matches are checked for not empty().
 */
function wp_parse_shortcode_attributes($text)
{
    $atts = array();
    $pattern = wp_get_shortcode_atts_regex();
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
    if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
        foreach ($match as $m) {
            if (!empty($m[1]))
                $atts[strtolower($m[1])] = stripcslashes($m[2]);
            elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
            elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
            elseif (isset($m[7]) && strlen($m[7]))
                $atts[] = stripcslashes($m[7]);
            elseif (isset($m[8]))
                $atts[] = stripcslashes($m[8]);
        }
        
        // Reject any unclosed HTML elements
        foreach( $atts as &$value ) {
            if ( false !== strpos( $value, '<' ) ) {
                if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                    $value = '';
                }
            }
        }
    } else {
        $atts = ltrim($text);
    }
    return $atts;
}

/**
 * Scans all modules that have shortcode handlers and passes the contents for parsing
 * 
 * @param string $contents
 *
 * @return string
 */
function convert_shortcodes($contents)
{
    /** @noinspection PhpUnusedLocalVariableInspection */
    global $modules, $current_module, $config, $account, $settings;
    
    foreach($modules as $this_module)
    {
        if( empty($this_module->shortcode_handlers) ) continue;
        
        foreach($this_module->shortcode_handlers->handler as $handler)
        {
            $name    = trim($handler["name"]);
            $include = trim($handler->file);
            if( empty($include) ) continue;
            
            $file = ROOTPATH  . "/$this_module->name/$include";
            if( ! file_exists($file) ) continue;
            
            $pattern = wp_get_shortcode_regex($name);
            if( ! preg_match_all("@$pattern@", $contents, $matches) ) continue;
            # echo "<pre>" . print_r($matches, true) . "</pre>";
            
            foreach($matches[0] as $index => $matched_str)
            {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $attributes = wp_parse_shortcode_attributes($matches[3][$index]);
                # echo "<pre>" . print_r($attributes, true) . "</pre>";
                include $file;
            }
        }
    }
    
    return $contents;
}
