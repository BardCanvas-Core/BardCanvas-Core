<?php
/**
 * Web helper functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

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
    if( strstr($remoteIP, ",") ) $remoteIP = trim(end(explode(",", $remoteIP)));
    
    if( ! function_exists("getallheaders") ) return $remoteIP;
    
    $headers  = getallheaders();
    
    if( $headers["CF-Connecting-IP"] )
    {
        $remoteIP = $headers["CF-Connecting-IP"];
        if( strstr($remoteIP, ",") ) $remoteIP = trim(end(explode(",", $remoteIP)));
        
        return $remoteIP;
    }
    
    if( $headers["X-Forwarded-For"] )
    {
        $remoteIP = $headers["X-Forwarded-For"];
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
    global $config;
    
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
    file_put_contents($target_file, json_encode(array("message_type" => $type, "message" => $message_contents)));
    @chmod($target_file, 0777);
    usleep(10000);
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
    global $config;
    
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
            
            $return["n_$id"] = json_decode(file_get_contents($file));
        }
    }
    
    return $return;
}
