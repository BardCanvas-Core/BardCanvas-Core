<?php
/**
 * Web helper functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */
use hng2_base\accounts_repository;
use hng2_base\config;

/**
 * Echoes a fake "403 - Forbidden" error and quits the program.
 */
function throw_fake_403()
{
    header("Content-Type: text/html; charset=utf-8");
    header("HTTP/1.0 403 Forbidden");
    # echo "<pre>\$_SERVER := " . print_r($_SERVER, true) . "</pre>";
    
    die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
        <html><head>
        <title>403 Forbidden</title>
        </head><body>
        <h1>Forbidden</h1>
        <p>Access denied to ' . $_SERVER["REQUEST_URI"] . '</p>
        <hr>
        <address>' . trim($_SERVER["SERVER_SIGNATURE"]) . '</address>
        </body></html>');
}

/**
 * Echoes a fake "404 - Not found" error and quits the program.
 */
function throw_fake_404()
{
    header("Content-Type: text/html; charset=utf-8");
    header("HTTP/1.0 404 Not Found");
    # echo "<pre>\$_SERVER := " . print_r($_SERVER, true) . "</pre>";
    
    die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
        <html><head>
        <title>404 Not Found</title>
        </head><body>
        <h1>Not Found</h1>
        <p>The requested URL ' . $_SERVER["REQUEST_URI"] . ' was not found on this server.</p>
        <hr>
        <address>' . trim($_SERVER["SERVER_SIGNATURE"]) . '</address>
        </body></html>');
}

/**
 * Echoes a fake "401 - Unauthorized" error and quits the program.
 */
function throw_fake_401()
{
    header("Content-Type: text/html; charset=utf-8");
    header("HTTP/1.0 401 Unauthorized");
    # echo "<pre>\$_SERVER := " . print_r($_SERVER, true) . "</pre>";
    
    die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
        <html><head>
        <title>401 Unauthorized</title>
        </head><body>
        <h1>Unauthorized</h1>
        <p>You are trying to access a page using an invalid login method.</p>
        <hr>
        <address>' . trim($_SERVER["SERVER_SIGNATURE"]) . '</address>
        </body></html>');
}

/**
 * Echoes a fake "501 - Not implemented" error and quits the program.
 */
function throw_fake_501()
{
    header("Content-Type: text/html; charset=utf-8");
    header("HTTP/1.0 501 Not implemented");
    # echo "<pre>\$_SERVER := " . print_r($_SERVER, true) . "</pre>";
    
    die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
        <html><head>
        <title>501 Not implemented</title>
        </head><body>
        <h1>Not implemented</h1>
        <p>You are trying to access a page with invalid or unknown request arguments.</p>
        <hr>
        <address>' . trim($_SERVER["SERVER_SIGNATURE"]) . '</address>
        </body></html>');
}

/**
 * Echoes a fake "500 - Internal server error" error and quits the program.
 */
function throw_fake_500()
{
    header("Content-Type: text/html; charset=utf-8");
    header("HTTP/1.0 501 Not implemented");
    # echo "<pre>\$_SERVER := " . print_r($_SERVER, true) . "</pre>";
    
    die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
        <html><head>
        <title>500 Internal server error</title>
        </head><body>
        <h1>Internal server error</h1>
        <p>There was a problem processing your request.</p>
        <hr>
        <address>' . trim($_SERVER["SERVER_SIGNATURE"]) . '</address>
        </body></html>');
}

/**
 * Check if a given ip is in a network
 * @see https://gist.github.com/tott/7684443
 *
 * @param string $ip    IP to check in IPV4 format eg. 127.0.0.1
 * @param string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 *
 * @return boolean true if the ip is in this range / false if not.
 */
function is_ip_in_range($ip, $range)
{
    if( strpos($range, '/') == false ) $range .= '/32';
    
    // $range is in IP/CIDR format eg 127.0.0.1/24
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal    = ip2long($range);
    $ip_decimal       = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal  = ~$wildcard_decimal;
    
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

/**
 * Detects if the engine is behind a proxy and get the IP from it or the standard remote address
 *
 * @returns string
 */
function get_remote_address()
{
    global $settings;
    
    $headers = array();
    if( function_exists("getallheaders") ) $headers = getallheaders();
    
    # Note: Cloudflare ranges list updated as of April 8, 2021
    #       Source: https://www.cloudflare.com/ips/
    $cloudflare_ranges = explode("\n", unindent("
        173.245.48.0/20
        103.21.244.0/22
        103.22.200.0/22
        103.31.4.0/22
        141.101.64.0/18
        108.162.192.0/18
        190.93.240.0/20
        188.114.96.0/20
        197.234.240.0/22
        198.41.128.0/17
        162.158.0.0/15
        104.16.0.0/13
        104.24.0.0/14
        172.64.0.0/13
        131.0.72.0/22
    "));
    
    $valid_proxy_ranges = array();
    if( ! empty($settings) ) $valid_proxy_ranges = $settings->get_lines("engine.valid_proxys");
    
    $real_ip  = $_SERVER["REMOTE_ADDR"];
    $input_ip = $headers["CF-Connecting-IP"];
    if( empty($input_ip) ) $input_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
    if( empty($input_ip) ) $input_ip = $headers["X-Forwarded-For"];
    if( empty($input_ip) ) $input_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    if( empty($input_ip) ) $input_ip = $_SERVER["REMOTE_ADDR"];
    
    if( $headers["CF-Connecting-IP"] || $_SERVER["HTTP_CF_CONNECTING_IP"] )
    {
        $is_valid_proxy = false;
        
        $checking_ranges = $cloudflare_ranges;
        if( ! empty($valid_proxy_ranges) ) $checking_ranges = array_merge($cloudflare_ranges, $valid_proxy_ranges);
        
        foreach($checking_ranges as $range)
        {
            if( is_ip_in_range($real_ip, $range) )
            {
                $is_valid_proxy = true;
                break;
            }
        }
        
        if( ! $is_valid_proxy ) return $real_ip;
    }
    elseif( ($headers["X-Forwarded-For"] || $_SERVER["HTTP_X_FORWARDED_FOR"]) && ! empty($valid_proxy_ranges) )
    {
        $is_valid_proxy = false;
        foreach($valid_proxy_ranges as $range)
        {
            if( is_ip_in_range($real_ip, $range) )
            {
                $is_valid_proxy = true;
                break;
            }
        }
        
        if( ! $is_valid_proxy ) return $real_ip;
    }
    
    if( stristr($input_ip, ":") !== false ) return $real_ip;
    
    $input_ips = explode(",", $input_ip);
    $final_ips = array();
    foreach($input_ips as $ip)
    {
        $ip = trim($ip);
        if( stristr($ip, ":") !== false ) continue;
        if( ! filter_var($ip, FILTER_VALIDATE_IP) ) continue;
        if( is_ip_in_range($ip, "10.0.0.0/8") ) continue;
        if( is_ip_in_range($ip, "172.16.0.0/12") ) continue;
        if( is_ip_in_range($ip, "192.168.0.0/16") ) continue;
        
        $checking_ranges = $cloudflare_ranges;
        if( ! empty($valid_proxy_ranges) ) $checking_ranges = array_merge($cloudflare_ranges, $valid_proxy_ranges);
        
        $add_to_finals = true;
        foreach($checking_ranges as $px_range)
        {
            if( is_ip_in_range($ip, $px_range) )
            {
                $add_to_finals = false;
                break;
            }
        }
        
        if( $add_to_finals ) $final_ips[] = $ip;
    }
    
    if( empty($final_ips) ) return $real_ip;
    
    return array_pop($final_ips);
}

/**
 * Alias of get_remote_address()
 *
 * @return string
 */
function get_user_ip()
{
    return get_remote_address();
}

/**
 * Send a notification to a user
 *
 * @param string $id_account Target account
 * @param string $type       = ["alert", "success", "error", "warning", "information", "confirm"][$any]
 * @param string $contents
 */
function send_notification($id_account, $type, $contents)
{
    global $config;
    
    if($config->globals["engine.notifications_disabled"]) return;
    
    $notifications_dir = "{$config->datafiles_location}/notifications/$id_account";
    $time              = explode(".", microtime(true));
    $usec              = end($time);
    $now               = date("Ymd.His.") . $usec;
    $target_file       = "$notifications_dir/$now.json";
    $sent              = date("Y-m-d H:i:s");
    if( ! is_dir($notifications_dir) )
    {
        @mkdir($notifications_dir, 0777, true);
        @chmod($notifications_dir, 0777);
    }
    
    $contents = trim($contents);
    
    $message_contents
        = "<span data-message-archive='{$id_account}/{$now}' data-sent='$sent'>"
        . trim($contents)
        . "</span>";
    
    $json_contents = @json_encode(array("message_type" => $type, "message" => $message_contents, "sent" => $sent));
    if( strip_tags(trim($contents)) == "" || empty($json_contents) )
    {
        $accounts_repo  = new accounts_repository();
        $account        = $accounts_repo->get($id_account);
        
        $backtrace = debug_backtrace();
        foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        
        $logdate  = date("Ymd");
        $logtime  = date("Y-m-d H:i:s");
        $logentry = "[$logtime] Empty notification for {$account->display_name}. Backtrace:\n";
        foreach($backtrace as $line) $logentry .= " • $line\n";
        $logentry .= "\n";
        @file_put_contents("{$config->logfiles_location}/failed_notifications-{$logdate}.log", $logentry, FILE_APPEND);
        
        broadcast_to_moderators("error", "Empty notification registered. Please check the log.");
        
        return;
    }
    
    @file_put_contents($target_file, $json_contents);
    @chmod($target_file, 0777);
    usleep(10000);
}

/**
 * Send a notification to all admins
 *
 * @param string $type       alert, success, error, warning, information, confirm
 * @param string $contents
 */
function broadcast_to_moderators($type, $contents)
{
    $repository = new accounts_repository();
    $res = $repository->get_basics_above_level(config::MODERATOR_USER_LEVEL);
    $ids = array_keys($res);
    foreach($ids as $id) send_notification($id, $type, $contents);
    sleep(1);
}

/**
 * Get all notifications for a user and return them as array.
 * Note: notifications are deleted upon reading!
 *
 * @param string $id_account
 * @param int    $limit
 *
 * @return array
 * @throws phpmailerException
 */
function get_notifications($id_account, $limit = 0)
{
    global $config, $settings;
    
    $accounts_repository = new accounts_repository();
    $account = $accounts_repository->get($id_account);
    
    $notifications_dir = "{$config->datafiles_location}/notifications/$id_account";
    $return            = array();
    if( ! is_dir($notifications_dir) ) return $return;
    
    $index = 0;
    $files = glob("$notifications_dir/*.json");
    if( count($files) )
    {
        if( $limit > 0 ) rsort($files);
        
        foreach( $files as $file )
        {
            $index++;
            
            $id = basename($file);
            $id = str_replace(".json", "", $id);
            $id = str_replace(".", "_", $id);
            
            $contents = file_get_contents($file);
            if( empty($contents) )
            {
                @unlink($file);
                
                continue;
            }
            
            $contents = json_decode($contents);
            if( empty($contents) )
            {
                $webmaster_mail = ucwords($settings->get("engine.webmaster_address"));
                $webmaster_name = current(explode("@", $webmaster_mail));
                $recipients     = array($webmaster_name => $webmaster_mail);
                $subject        = "[{$settings->get("engine.website_name")}/notifications_getter] Malformed notification detected!";
                $contents       = htmlspecialchars(file_get_contents($file));
                $body = unindent("
                    <p><b>Error while reading notification for {$account->display_name}</b>!</p>
                    
                    <p>The notification file  {$file} has malformed contents. Please review them
                    and double check with the engine tech support staff about the module that is
                    generating it:</p>
                    
                    <blockquote>{$contents}</blockquote>
                    
                    <p>The notification has been deleted before delivering it.</p>
                ");
                send_mail($subject, $body, $recipients);
                @unlink($file);
                
                continue;
            }
            
            if( empty($contents->message) )
            {
                @unlink($file);
                
                continue;
            }
            
            if( ! empty($contents->sent) )
            {
                $sent = time_elapsed_string($contents->sent);
                $contents->message = "<span style='font-size: 8pt; position: absolute; top: 2px; line-height: normal;'>"
                                   . "<i class='fa fa-clock-o'></i> $sent"
                                   . "</span>"
                                   . "<br>{$contents->message}";
            }
            
            $return["n_$id"] = $contents;
            
            if( $limit > 0 && $index >= $limit ) break;
        }
        
        if( $limit > 0 ) krsort($return);
    }
    
    return $return;
}

function convert_emojis($input)
{
    global $emojione, $config;
    
    if( empty($emojione) )
    {
        require_once ROOTPATH . "/lib/emojione-2.2.5/lib/php/autoload.php";
        $emojione = new Emojione\Client(new Emojione\Ruleset());
        
        $emojione->imageType    = "png";
        $emojione->imagePathPNG = "{$config->full_root_url}/lib/emojione-2.2.5/png/";
        $emojione->imagePathSVG = "{$config->full_root_url}/lib/emojione-2.2.5/svg/";
    }
    
    return $emojione->unicodeToImage($input);
}

function load_tinymce_addons()
{
    global $modules;
    
    foreach($modules as $module)
        if( ! empty($module->language->tinymce_addon) )
            foreach($module->language->tinymce_addon as $addon)
                register_tinymce_addon(
                    trim($addon->title),
                    trim($addon["function"]),
                    trim($addon["icon"]),
                    trim($addon->caption),
                    trim($addon->related_field)
                );
                
}

function register_tinymce_addon($title, $function, $icon, $caption, $related_field)
{
    global $config;
    
    $config->globals["tinymce_addons"][] = (object) array(
        "title"         => trim($title),
        "function"      => trim($function),
        "icon"          => trim($icon),
        "caption"       => trim($caption),
        "related_field" => trim($related_field),
    );
}

/**
 * Extracts hashtags from contents and returns them in an array
 *
 * @param string $contents
 *
 * @return array
 */
function extract_hash_tags($contents)
{
    $contents = strip_tags($contents);
    $count = preg_match_all('/#([a-z0-9]+)/i', $contents, $matches);
    if( empty($count) ) return array();
    
    $return = array_unique($matches[1]);
    return $return;
}

/**
 * Converts hashtags into links
 * 
 * @param string $contents   Markup to process
 * @param string $url_prefix URL prefix to view tag index
 * @param string $url_suffix Added to the URL if defined
 * 
 * @return string
 */
function autolink_hash_tags($contents, $url_prefix, $url_suffix = "")
{
    global $config, $modules;
    
    $count = preg_match_all('/(#[a-z0-9]+)/i', strip_tags($contents), $matches);
    
    if( $count == 0 ) return $contents;
    
    $list = array();
    foreach($matches[1] as $match)
    {
        $raw_match = str_replace("#", "", $match);
        $tag = "<a href='{$url_prefix}{$raw_match}{$url_suffix}'>$match</a>";
        # $contents = str_replace($match, $tag, $contents);
        $list[$match] = $tag;
    }
    
    $config->globals["webhelper/filtering_tags_list"] = $list;
    foreach($modules as $module)
        if( ! empty($module->php_includes->webhelper_autolink_hash_tags_filter) )
            include "{$module->abspath}/{$module->php_includes->webhelper_autolink_hash_tags_filter}";
    $list = $config->globals["webhelper/filtering_tags_list"];
    
    if( empty($list) ) return $contents;
    
    foreach($list as $search => $replace)
        $contents = preg_replace("/$search\\b/", $replace, $contents);
    
    return $contents;
}

/**
 * Cloud builder
 *
 * @author http://stackoverflow.com/users/73673/berkes
 * @see    http://stackoverflow.com/questions/227/whats-the-best-way-to-generate-a-tag-cloud-from-an-array-using-h1-through-h6-fo
 *
 * @param array $items
 * @param int   $steps
 * @param int   $min_font_size
 * @param int   $max_font_size
 *
 * @return array
 */
function build_cloud(array $items, $steps = 10, $min_font_size = 10, $max_font_size = 28)
{
    if( empty($items) ) return array();
    
    $tags = array();
    $min  = 1e9;
    $max  = -1e9;
    
    $font_size_factor = ($max_font_size - $min_font_size) / $steps;
    
    foreach($items as $key => $count)
    {
        $tag        = (object) array();
        $tag->tag   = $key;
        $tag->count = $count;
        
        $tag->hits       = $tag->count; #sets the amount of items a certain tag has attached to it
        $tag->count      = log($tag->count);
        $min             = min($min, $tag->count);
        $max             = max($max, $tag->count);
        $tags[$tag->tag] = $tag;
    }
    
    // Note: we need to ensure the range is slightly too large to make sure even
    // the largest element is rounded down.
    $range = max(.01, $max - $min) * 1.0001;
    
    foreach ($tags as $key => $value)
    {
        $tags[$key]->weight = 1 + floor($steps * ($value->count - $min) / $range);
        $tags[$key]->size   = round($min_font_size + (($tags[$key]->weight - 1) * $font_size_factor));
    }
    
    # ksort($tags);
    return $tags;
}

/**
 * @param       $subject
 * @param       $body
 * @param       $recipients [name => address, name => address, ...]
 * @param array $sender     [name => address] (One single item.) If empty, default sender will be used
 * @param array $attachments
 *
 * @return string
 * @throws phpmailerException
 */
function send_mail($subject, $body, $recipients, $sender = null, $attachments = array())
{
    global $settings, $modules, $config;
    
    if($config->globals["engine.emails_disabled"]) return "OK";
    if($settings->get("engine.emails_disabled") == "true") return "OK";
    
    foreach($modules as $module)
    {
        if( empty($module->php_includes->web_helper_before_send_mail) ) continue;
        
        $include = "{$module->abspath}/{$module->php_includes->web_helper_before_send_mail}";
        if( ! file_exists($include) ) continue;
        
        include $include;
    }
    
    # These may have changed by an extension
    if( empty($recipients) ) return "OK";
    
    if( ! class_exists("PHPMailer") )
    {
        include_once ROOTPATH . "/lib/PHPMailer/class.phpmailer.php";
        include_once ROOTPATH . "/lib/PHPMailer/class.smtp.php";
    }
    
    $mail = new PHPMailer();
    $mail->isHTML(true);
    $mail->CharSet = "utf-8";
    $mail->Body    = externalize_urls($body);
    $mail->AltBody = strip_tags($body);
    $mail->Subject = $subject;
    
    if( $config->globals["phpmailer_debug_mode_enabled"] )
        $mail->SMTPDebug = 2;
    
    $smtp_enabled  = $settings->get("engine.mail_sender_smtp_enabled");
    $smtp_host     = $settings->get("engine.mail_sender_smtp_host");
    $smtp_port     = $settings->get("engine.mail_sender_smtp_port");
    $smtp_user     = $settings->get("engine.mail_sender_smtp_user");
    $smtp_password = $settings->get("engine.mail_sender_smtp_password");
    $smtp_security = $settings->get("engine.mail_sender_smtp_security");
    
    if( $smtp_enabled != "true" || empty($smtp_host) || empty($smtp_port) || empty($smtp_user) || empty($smtp_password) )
    {
        $mail->isMail();
        
        if( $config->globals["phpmailer_debug_mode_enabled"] )
            echo "Sending using PHP mail() function.\n";
    }
    else
    {
        $mail->isSMTP();
        
        $mail->SMTPSecure = $smtp_security;
        $mail->Host       = $smtp_host;
        $mail->Port       = $smtp_port;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_password;
        
        foreach($modules as $module)
        {
            if( empty($module->php_includes->web_helper_after_initializing_smtp_auth) ) continue;
            
            $include = "{$module->abspath}/{$module->php_includes->web_helper_after_initializing_smtp_auth}";
            if( ! file_exists($include) ) continue;
            
            include $include;
        }
        
        if( $config->globals["phpmailer_debug_mode_enabled"] )
            echo "Sending using SMTP. Details:\n"
            .    " • SMTPSecure = $smtp_security\n"
            .    " • Host       = $smtp_host\n"
            .    " • Port       = $smtp_port\n"
            .    " • SMTPAuth   = true\n"
            .    " • Username   = $smtp_user\n"
            .    " • Password   = $smtp_password\n"
            ;
    }
    
    foreach($recipients as $name => $address)
        $mail->addAddress($address, $name);
    
    $mail->From     = $settings->get("engine.mail_sender_email");
    $mail->FromName = $settings->get("engine.mail_sender_name");
    
    if( ! empty($attachments) )
    {
        foreach($attachments as $path)
        {
            if( ! is_file($path) ) continue;
            
            $filename = basename($path);
            $mail->addAttachment($path, $filename);
        }
    }
    
    if( ! is_null($sender) )
    {
        $mail->Sender   = $settings->get("engine.mail_sender_email");
        $mail->From     = current($sender);
        $mail->FromName = key($sender);
        # $mail->addReplyTo(current($sender), key($sender));
    }
    
    if( $config->globals["phpmailer_debug_mode_enabled"] )
        echo "Init complete. Sending now.\n"
        .    "---------------------------\n"
        ;
    
    $ip       = get_remote_address(); if( empty($ip) ) $ip = "127.0.0.1";
    $host     = $ip == "127.0.0.1" ? "localhost" : @gethostbyaddr($ip); if( empty($host) ) $host = $ip;
    $location = get_geoip_location_with_isp($ip);
    $agent    = $_SERVER["HTTP_USER_AGENT"]; if( empty($agent) ) $agent = "N/A";
    $logtime  = date("Y-m-d H:i:s");
    $logdate  = date("Ymd");
    $logfile  = "{$config->logfiles_location}/mail_log-$logdate.log";
    $recipsl  = implode(", ", $recipients);
    $excerpt  = make_excerpt_of($body, 80);
    $bcktrace = debug_backtrace();
    $invoker  = str_replace(ROOTPATH, "", $bcktrace[1]["file"]) . ":" . $bcktrace[1]["line"];
    $logmsg   = "[$logtime] From {$mail->From} to {$recipsl}:\n"
              . " • Subject:   {$subject}\n"
              . " • Sender IP: {$ip} ({$host})\n"
              . " • Location:  {$location}\n"
              . " • Using:     {$agent}\n"
              . " • Excerpt:   {$excerpt}\n"
              . " • Invoker:   {$invoker}\n"
              ;
    
    if( ! $mail->send() )
    {
        $logmsg .= " ! ERROR:     {$mail->ErrorInfo}\n\n";
        @file_put_contents($logfile, $logmsg, FILE_APPEND);
        return $mail->ErrorInfo;
    }
    
    @file_put_contents($logfile, "{$logmsg}\n", FILE_APPEND);
    return "OK";
}

function broadcast_mail_to_moderators($subject, $body, $prefs_key = "", $exclude_mod_ids = array())
{
    $repository = new accounts_repository();
    $mods       = $repository->get_basics_above_level(config::MODERATOR_USER_LEVEL, 255, true);
    
    if( ! empty($exclude_mod_ids) )
        foreach($exclude_mod_ids as $id_account)
            unset( $mods[$id_account] );
    
    if( empty($mods) ) return;
    
    if( ! empty($prefs_key) )
    {
        $prefs = $repository->get_multiple_engine_prefs(array_keys($mods), $prefs_key);
        if( ! empty($prefs) )
            foreach($prefs as $id_account => $value)
                if( $value == "false" )
                    unset( $mods[$id_account] );
    }
    
    if( empty($mods) ) return;
    
    $recipients = array();
    foreach($mods as $mod) $recipients[$mod->display_name] = $mod->email;
    send_mail($subject, $body, $recipients);
}

function broadcast_mail_between_levels(
    $start_level, $end_level, $subject, $body, $prefs_key = "", $exclude_mod_ids = array()
) {
    global $config;
    
    if( $start_level == 0 || $end_level == 0 ) return;
    
    $repository = new accounts_repository();
    $targets    = $repository->get_basics_above_level($start_level, $end_level, true);
    
    if( ! empty($exclude_mod_ids) )
        foreach($exclude_mod_ids as $id_account)
            unset( $targets[$id_account] );
    
    if( empty($targets) ) return;
    
    if( ! empty($prefs_key) )
    {
        $prefs = $repository->get_multiple_engine_prefs(array_keys($targets), $prefs_key);
        if( ! empty($prefs) )
            foreach($prefs as $id_account => $value)
                if( $value == "false" )
                    unset( $targets[$id_account] );
    }
    
    if( empty($targets) ) return;
    
    $recipients = array();
    $config->globals["trailing_output_array"][] = "Sending mail \"{$subject}\" to:";
    foreach($targets as $target)
    {
        $recipients[$target->display_name] = $target->email;
        $config->globals["trailing_output_array"][] = "► {$target->display_name} ({$target->email})";
    }
    send_mail($subject, $body, $recipients);
}

/** @noinspection PhpParamsInspection */
function externalize_urls($text)
{
    /** @noinspection PhpUnusedLocalVariableInspection */
    global $config;
    
    if( ! class_exists('phpQuery') )
        include_once(ROOTPATH . "/lib/phpQuery-onefile.php");
    
    $pq = phpQuery::newDocument($text);
    
    $pq->find('img')->each(function($element) {
        global $config;
        
        $tag = pq($element);
        $src = trim($tag->attr('src'));
        if( empty($src) ) return;
        
        if( ! preg_match('/^http:|https:/i', $src) )
            $tag->attr("src", $config->full_root_url . $src);
    });
    
    $pq->find('a')->each(function($element) {
        global $config;
        
        $tag = pq($element);
        $src = trim($tag->attr('href'));
        if( empty($src) ) return;
        
        if( ! preg_match('/^http:|https:/i', $src) )
            $tag->attr("href", $config->full_root_url . $src);
    });
    
    return $pq->html();
}

function is_visitor_a_bot()
{
    $botlist = array(
        "googlebot", "slurp", "msnbot", "mediapartners-google", "yahoo-mmcrawler", "bingbot", "spider", "crawl",
        "ia_archiver",
    );
    
    foreach($botlist as $this_bot)
        if(stristr(strtolower($_SERVER["HTTP_USER_AGENT"]), $this_bot) !== false)
            return true;
    
    return false;
}

function is_mobile()
{
    return preg_match(
        "/iphone|ipad|ios|mobile|phone|android|blackberry|kindle|opera mini|opera mobi/i",
        $_SERVER["HTTP_USER_AGENT"]
    );
}

/**
 * Checks if a passed string contains SQL injection code.
 * If found, an error is thrown.
 * 
 * @param string|array $input
 * 
 * @throws Exception
 */
function check_sql_injection($input)
{
    global $modules, $config, $DATABASES;
    
    if( is_string($input) ) $input = array($input);
    
    static $patt = null;
    if( is_null($patt) )
        $patt = '#'
              . '(select|union select|describe|show|insert)|(distinct)'
              . '|'
              . '(from|into|sysdate)'
              . '|'
              . '(where|like|case when)'
              . '|'
              . '(char|ascii|cast|convert_to|ifnull|count|concat)'
              . '|'
              . '(information_schema|information_schema.plugins|geography_area)'
              . '|'
              . "({$DATABASES[0]['db']}|\\.account|\\.settings|\\.account_devices|\\.account_engine_prefs)"
              . '|'
              . '(security_blacklisted_country_hits|security_emails_blacklist|security_failed_logins|security_hosts_blacklist|security_hosts_whitelist|security_ips_blacklist|security_ips_whitelist)'
              . '|'
              . '(having|union|table|tables|join|group)'
              . '|'
              . '(drop|truncate|delete from)'
              . '|'
              . '(perl -e)'
              . '|'
              . '(gethostbyname|nslookup|shell_exec|pg_sleep)'
              . '|'
              . '(/\*|\*/|0x0[a-f0-8]|0x1[a-f0-9]|1=0|1=1|1--)'
              . '#i';
    
    foreach($input as $string)
    {
        if( is_array($string) )
        {
            check_sql_injection($string);
            continue;
        }
        
        $string = trim(stripslashes($string));
        if( empty($string) ) continue;
        
        $hits = preg_match_all($patt, $string, $matches);
        if( $hits )
        {
            $matches_list = array();
            foreach($matches as $key => $minfo)
            {
                $minfo = array_unique($minfo);
                foreach($minfo as $mkey => $mval)
                {
                    $mval = trim($mval);
                    if( empty($mval) ) continue;
                    
                    $matches_list[] = $mval;
                }
            }
            unset($matches_list[0]);
            $matches_list = array_unique($matches_list);
            
            $sanitized = array();
            foreach($matches_list as $key => $val) if( ! empty($val) ) $sanitized[] = $val;
            $matches_list = array_unique($sanitized);
            
            if( count($matches_list) < 3 ) continue;
            
            $config->globals["!sql_injection.matches_list"] = $matches_list;
            
            # Extenders
            if( ! $config->globals["@webhelper/no_extensions"] && is_array($modules) )
            {
                foreach($modules as $module)
                {
                    if( ! isset($module->php_includes->sql_injection_prelog) ) continue;
                    
                    foreach($module->php_includes->sql_injection_prelog as $script)
                    {
                        $this_module = $module;
                        $include = ROOTPATH . "/{$module->name}/$script";
                        
                        if( ! file_exists($include) ) continue;
                        
                        include $include;
                    }
                }
            }
            
            $logdate   = date("Ymd");
            $logtstamp = date("Y-m-d H:i:s");
            $logip     = get_user_ip();
            $loghost   = @gethostbyaddr($logip);
            $loglocatn = get_geoip_location_with_isp($logip);
            $logfile   = ROOTPATH . "/logs/sql_injection_attempts-$logdate.log";
            $log_entry = sprintf(
                "%s - %s (%s, %s) - %s %s - \"%s\"\n",
                $logtstamp,
                $logip,
                $loghost,
                $loglocatn,
                $_SERVER["REQUEST_METHOD"],
                $_SERVER["REQUEST_URI"],
                implode(", ", $matches_list)
            );
            file_put_contents($logfile, $log_entry, FILE_APPEND);
            throw new \Exception("SQL injection detected");
        }
    }
}

function get_sanitized_user_agent($string = "")
{
    if( empty($string) ) $string = $_SERVER["HTTP_USER_AGENT"];
    
    return preg_replace('@(/[0-9]+)(\\.?[0-9.]+)@', '\\1', $string);
}

function has_injected_scripts($input)
{
    $input = html_entity_decode($input);
    $input = trim(stripslashes($input));
    
    if( empty($input) ) return false;
    
    if( stristr($input, "<script") ) return true;
    if( stristr($input, "<link") ) return true;
    if( stristr($input, "<style") ) return true;
    if( stristr($input, "<form") ) return true;
    if( stristr($input, "<input") ) return true;
    if( stristr($input, "<button") ) return true;
    if( stristr($input, "<select") ) return true;
    if( stristr($input, "<layer") ) return true;
    if( stristr($input, "<iframe") ) return true;
    if( stristr($input, "<!--") ) return true;
    if( stristr($input, "-->") ) return true;
    if( stristr($input, "javascript:") ) return true;
    if( preg_match('/on(hover|click|dblclick)=/i', $input) ) return true;
    if( preg_match('/onmouse(down|move|out|over|up|wheel)|onwheel=/i', $input) ) return true;
    if( preg_match('/key(down|up|press)=/i', $input) ) return true;
    if( preg_match('/on(focus|blur|submit|copy|cut|paste)=/i', $input) ) return true;
    if( preg_match('@url\(\s*[\'"]?(https?|mailto|ftp|data)://.+[\'"]?\s*\)@', $input) ) return true;
    if( stristr($input, "data:") ) return true;
    
    return false;
}
