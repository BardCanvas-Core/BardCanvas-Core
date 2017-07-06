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
 * Detects if the engine is behind a proxy that provides the x-forwarded-for header and get the IP from it or the
 * standard remote address
 *
 * @returns string
 */
function get_remote_address()
{
    # header("X-Remote-Address: {$_SERVER['REMOTE_ADDR']}");
    $remoteIP = $_SERVER['REMOTE_ADDR'];
    if(     strstr($remoteIP, ",") ) $remoteIP = trim(end(explode(",", $remoteIP)));
    elseif( strstr($remoteIP, " ") ) $remoteIP = trim(end(explode(" ", $remoteIP)));
    
    if( ! function_exists("getallheaders") ) return $remoteIP;
    
    $headers  = getallheaders();
    
    if( $headers["X-Forwarded-For"] )
    {
        $remoteIP = $headers["X-Forwarded-For"];
        if( strstr($remoteIP, ",") ) $remoteIP = trim(end(explode(",", $remoteIP)));
        
        return $remoteIP;
    }
    
    if( $headers["CF-Connecting-IP"] )
    {
        $remoteIP = $headers["CF-Connecting-IP"];
        if( strstr($remoteIP, ",") ) $remoteIP = trim(end(explode(",", $remoteIP)));
        
        return $remoteIP;
    }
    
    return $remoteIP;
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
 * @param string $type       alert, success, error, warning, information, confirm
 * @param string $contents
 */
function send_notification($id_account, $type, $contents)
{
    global $config, $settings;
    
    if($config->globals["engine.notifications_disabled"]) return;
    
    $notifications_dir = "{$config->datafiles_location}/notifications/$id_account";
    $usec              = end(explode(" ", microtime()));
    $now               = date("Ymd.His.") . $usec;
    $target_file       = "$notifications_dir/$now.json";
    if( ! is_dir($notifications_dir) )
    {
        @mkdir($notifications_dir, 0777, true);
        @chmod($notifications_dir, 0777);
    }
    
    $message_contents
        = "<span data-message-archive='{$id_account}/{$now}'>"
        . trim($contents)
        . "</span>";
    
    $contents = json_encode(array("message_type" => $type, "message" => $message_contents));
    if( empty($contents) )
    {
        $accounts_repository = new accounts_repository();
        $account             = $accounts_repository->get($id_account);
        $webmaster_mail      = ucwords($settings->get("engine.webmaster_address"));
        $webmaster_name      = current(explode("@", $webmaster_mail));
        $recipients          = array($webmaster_name => $webmaster_mail);
        $subject             = "[{$settings->get("engine.website_name")}/notifications_sender] Malformed notification detected!";
        $contents            = htmlspecialchars($message_contents);
        
        $backtrace = debug_backtrace();
        foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        $backtrace = implode("\n                ", $backtrace);
        
        $body = unindent("
            <p><b>Error while attempting to send notification for {$account->display_name}</b>!</p>
            
            <p>The notification has malformed contents. Please review them
            and double check with the engine tech support staff about the module that is
            generating it:</p>
            
            <blockquote>{$contents}</blockquote>
            
            <p>The notification has wont be delivered.</p>
            
            <p><b>Stack Trace:</b></p>
            <pre>
                $backtrace
            </pre>
        ");
        send_mail($subject, $body, $recipients);
        
        return;
    }
    
    @file_put_contents($target_file, $contents);
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
 *
 * @returns array
 */
function get_notifications($id_account)
{
    global $config, $settings;
    
    $accounts_repository = new accounts_repository();
    $account = $accounts_repository->get($id_account);
    
    $notifications_dir = "{$config->datafiles_location}/notifications/$id_account";
    $return            = array();
    if( ! is_dir($notifications_dir) ) return $return;
    
    $files = glob("$notifications_dir/*.json");
    if( count($files) )
    {
        foreach( $files as $file )
        {
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
            
            $return["n_$id"] = $contents;
        }
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
        $contents = str_replace($search, $replace, $contents);
    
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
    if( ! $mail->send() ) return $mail->ErrorInfo;
    
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

function externalize_urls($text)
{
    global $config;
    
    $replacements = array();
    
    $doc = new DOMDocument();
    @$doc->loadHTML($text);
    
    /**
     * @var DOMNodeList $tags
     * @var DOMElement  $tag
     */
    
    $tags = $doc->getElementsByTagName('img');
    foreach($tags as $tag)
    {
        $src = trim($tag->getAttribute('src'));
        if( empty($src) ) continue;
        
        if( ! preg_match('/^http:|https:/i', $src) )
            $replacements[$src] = $config->full_root_url . $src;
    }
    
    $tags = $doc->getElementsByTagName('a');
    foreach($tags as $tag)
    {
        $src = trim($tag->getAttribute('href'));
        if( empty($src) ) continue;
        
        if( ! preg_match('/^http:|https:/i', $src) )
            $replacements[$src] = $config->full_root_url . $src;
    }
    
    $text = str_replace(array_keys($replacements), $replacements, $text);
    
    return $text;
}

function is_mobile()
{
    return preg_match(
        "/iphone|ipad|ios|mobile|phone|android|blackberry|kindle|opera mini|opera mobi/i",
        $_SERVER["HTTP_USER_AGENT"]
    );
}
