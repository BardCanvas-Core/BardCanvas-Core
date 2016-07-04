<?php
/**
 * Account device class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace wcms_base;

class device
{
    var $id_device;
    var $id_account;
    var $device_label;
    var $device_header;
    var $creation_date;
    var $state;
    var $last_activity;
    
    var $_exists = false;
    
    /**
     * Per-account device maintenance for users
     * 
     * @param mixed  $id_account_or_id_device_or_object 
     * @param string $user_agent                        Automatically obtained
     * 
     * @return device
     */
    function __construct($id_account_or_id_device_or_object = "", $user_agent = "")
    {
        global $database;
        
        if( is_object($id_account_or_id_device_or_object) )
        {
            $this->assign_from_object($id_account_or_id_device_or_object);
            $this->_exists = true;
            
            return;
        }
        
        if( ! empty($id_account_or_id_device_or_object) && empty($user_agent) )
        {
            $res = $database->query("
                select * from account_devices where id_device = '$id_account_or_id_device_or_object'
            ");
            if( $database->num_rows($res) > 0 )
            {
                $row = $database->fetch_object($res);
                $this->assign_from_object($row);
                $this->_exists = true;
                
                return;
            }
        }
        
        if( empty($user_agent) ) $user_agent = $_SERVER["HTTP_USER_AGENT"];
        
        if( empty($id_account_or_id_device_or_object) && empty($user_agent) )
        {
            $this->_exists = false;
            
            return;
        }
        
        $id_account = addslashes(trim(stripslashes($id_account_or_id_device_or_object)));
        $user_agent = addslashes(trim(stripslashes($user_agent)));
        
        $res = $database->query("
            select * from account_devices where id_account = '$id_account' and device_header = '$user_agent'
        ");
        if( $database->num_rows($res) > 0 )
        {
            $row = $database->fetch_object($res);
            $this->assign_from_object($row);
            $this->_exists = true;
        }
    }
    
    /**
     * Assigns the current class properties from an incoming database query
     *
     * @param object $object
     *
     * @return $this
     */
    function assign_from_object($object)
    {
        foreach($object as $key => $val) $this->{$key} = $val;
        
        if($this->creation_date   == "0000-00-00 00:00:00") $this->creation_date  = "";
        if($this->last_activity   == "0000-00-00 00:00:00") $this->last_activity  = "";
    }
    
    /**
     * Set new account parameters
     * 
     * @param account $account
     */
    function set_new($account)
    {
        $this->id_device     = uniqid(true);
        $this->id_account    = $account->id_account;
        $this->device_label  = "N/A";
        $this->device_header = $_SERVER["HTTP_USER_AGENT"];
        $this->state         = "unregistered";
    }
    
    /**
     * Send an email to the owner of this device with a token to authorize it
     *
     * @param account $account
     *
     * @return bool
     */
    function send_auth_token($account)
    {
        global $config, $settings, $current_module;
        
        $limit        = date("Y-m-d H:i:s", strtotime("now + 70 minutes"));
        $token        = encrypt( $this->id_account."\t".$this->id_device."\t".$limit, $config->encryption_key );
        $token_url    = (empty($_SERVER["HTTPS"]) ? "http://" : "https://").$_SERVER["HTTP_HOST"]."/confirm_device?token=".urlencode($token);
        $ip           = get_remote_address();
        $hostname     = gethostbyaddr(get_remote_address());
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = $settings->get("engine.mail_sender_name")."<".$settings->get("engine.mail_sender_email").">";
        $mail_to      = "$account->display_name<$account->email>";
        $mail_alt     = "$account->display_name alternate email<$account->email>";
    
        $request_location = forge_geoip_location($ip);
        
        # header("X-Auth-Token: $token_url");
        
        $mail_subject = replace_escaped_vars(
                            $current_module->language->email_templates->confirm_new_device->subject,
                            array('{$user_name}', '{$website_name}'),
                            array($account->user_name, $settings->get("engine.website_name"))
                        );
        $mail_body = replace_escaped_vars(
                         $current_module->language->email_templates->confirm_new_device->body,
                         array('{$website_name}',                       '{$display_name}',        '{$device_info}',       '{$token_url}', '{$main_email}',    '{$alt_email}',        '{$date_sent}', '{$request_ip}', '{$request_hostname}', '{$request_location}', '{$request_user_agent}'      ),
                         array(  $settings->get("engine.website_name"),   $account->display_name,   $this->device_header,   $token_url,     $account->email,    $account->alt_email,   $fecha_envio,   $ip,             $hostname,             $request_location,     $_SERVER["HTTP_USER_AGENT"])
                     );
        $mail_body = str_replace("<br />", "", preg_replace('/\n\s*/', "\n", nl2br($mail_body)));
        return @mail(
            $mail_to, $mail_subject, $mail_body, 
            "From: ".$mail_from . "\r\n" . 
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=utf-8\r\n" .
            (empty($account->alt_email) ? "" : "CC: ".$mail_alt."\r\n")
        );
    }
    
    function enable()
    {
        global $database;
        
        $this->state         = "enabled";
        $this->last_activity = date("Y-m-d H:i:s");
        
        $database->exec("
            update account_devices set
                state            = '$this->state',
                last_activity    = '$this->last_activity'
            where
                id_device        = '$this->id_device'
        ");
    }
    
    /**
     * Sets the last_activity of the device
     */
    function ping()
    {
        global $database;
        
        $this->last_activity = date("Y-m-d H:i:s");
        $database->exec("
            update account_devices set
                last_activity    = '$this->last_activity'
            where
                id_device        = '$this->id_device'
        ");
    }
    
    function save()
    {
        global $database;
        
        $now = date("Y-m-d H:i:s");
        if( ! $this->_exists )
        {
            $this->creation_date    =
            $this->last_activity    = $now;
            $query = "
                insert into account_devices set
                    `id_device`      = '".addslashes($this->id_device)."',
                    `id_account`     = '".addslashes($this->id_account)."',
                    `device_label`   = '".addslashes($this->device_label)."',
                    `device_header`  = '".addslashes($this->device_header)."',
                    `creation_date`  = '".addslashes($this->creation_date)."',
                    `state`          = '".addslashes($this->state)."',
                    `last_activity`  = '".addslashes($this->last_activity)."'
            ";
        }
        else
        {
            $this->last_activity    = $now;
            $query = "
                update account_devices set
                    `device_label`   = '".addslashes($this->device_label)."',
                    `state`          = '".addslashes($this->state)."',
                    `last_activity`  = '".addslashes($this->last_activity)."'
                where
                    `id_device`      = '".addslashes($this->id_device)."'
            ";
        }
        
        return $database->exec($query);
    }
    
    function delete()
    {
        global $database;
        
        $database->exec("
            delete from account_devices
            where `id_device` = '".addslashes($this->id_device)."'
        ");
    }
}
