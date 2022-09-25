<?php
/**
 * User account class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base;

class account extends account_toolbox
{
    public $level = 1;
    public $state = "new";
    
    # Temporary and control
    public $_raw_password;
    public $_is_locked = false;
    
    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * User account
     *
     * @param mixed $input Previously fetched row or id_account|user_name to search
     */
    public function __construct($input = "")
    {
        global $database;
        
        if( is_object($input) )
        {
            $this->assign_from_object($input);
            if( $this->level >= config::COADMIN_USER_LEVEL ) $this->_is_admin = true;
            $this->_exists = true;
            
            return;
        }
        
        if( empty($input) )
        {
            $this->_exists = false;
            
            return;
        }
        
        $input = addslashes(trim(stripslashes($input)));
        
        $where = is_numeric($input) ? "id_account = '$input'" : "user_name = '$input' or display_name = '$input'";
        
        $res   = $database->query("
            select
                account.*,
                (select name from countries where alpha_2 = account.country) as country_name
            from
                account
            where
                $where
        ");
        
        if( ! $res ) return;
        if( $database->num_rows($res) == 0 ) return;
        
        $row = $database->fetch_object($res);
        $this->assign_from_object($row);
        $this->_exists = true;
        
        if( $this->level >= config::COADMIN_USER_LEVEL ) $this->_is_admin = true;
    }
    
    /**
     * Assigns the current class properties from an incoming database query
     *
     * @param object $object
     */ 
    protected function assign_from_object($object)
    {
        $this->id_account     = $object->id_account    ;
        $this->user_name      = $object->user_name     ;
        $this->password       = $object->password      ;
        $this->display_name   = $object->display_name  ;
        $this->email          = $object->email         ;
        $this->alt_email      = $object->alt_email     ;
        $this->birthdate      = $object->birthdate     ;
        $this->avatar         = $object->avatar        ;
        $this->profile_banner = $object->profile_banner;
        $this->signature      = $object->signature     ;
        $this->bio            = $object->bio           ;
        $this->homepage_url   = $object->homepage_url  ;
        $this->country        = $object->country       ;
        $this->level          = $object->level         ;
        $this->state          = $object->state         ;
        $this->creation_host  = $object->creation_host ;
        $this->creation_date  = $object->creation_date ;
        $this->last_update    = $object->last_update   ;
        $this->country_name   = $object->country_name  ;
        
        if($this->birthdate       == "0000-00-00")          $this->birthdate      = "";
        if($this->creation_date   == "0000-00-00 00:00:00") $this->creation_date  = "";
        if($this->last_update     == "0000-00-00 00:00:00") $this->last_update    = "";
        
        $this->load_engine_prefs();
    }
    
    /**
     * Sets all account fields from $_POST
     */
    public function assign_from_posted_form()
    {
        $this->user_name      = trim(stripslashes($_POST["user_name"]))     ;
        $this->_raw_password  = trim(stripslashes($_POST["password"]))      ;
        $this->display_name   = trim(stripslashes($_POST["display_name"]))  ;
        $this->email          = trim(stripslashes($_POST["email"]))         ;
        $this->alt_email      = trim(stripslashes($_POST["alt_email"]))     ;
        $this->birthdate      = trim(stripslashes($_POST["birthdate"]))     ;
        $this->avatar         = trim(stripslashes($_POST["avatar"]))        ;
        $this->profile_banner = trim(stripslashes($_POST["profile_banner"]));
        $this->signature      = trim(stripslashes($_POST["signature"]))     ;
        $this->bio            = trim(stripslashes($_POST["bio"]))           ;
        $this->homepage_url   = trim(stripslashes($_POST["homepage_url"]))  ;
        $this->country        = trim(stripslashes($_POST["country"]))       ;
    }
    
    public function set_new_id()
    {
        parent::set_new_id();
    }
    
    /**
     * Loads an account from a cookie -if set-
     */
    public function load_session()
    {
        global $config, $settings, $database, $mem_cache, $modules;
        
        $user_session_cookie_key = $settings->get("engine.user_session_cookie");
        
        if( empty($_COOKIE[$user_session_cookie_key]) ) return;
        
        $raw_cookied_token = sys_decrypt( $_COOKIE[$user_session_cookie_key] );
        if( empty($raw_cookied_token) ) return;
        
        $cached_token = $mem_cache->get("@!ust_{$raw_cookied_token}");
        if( empty($cached_token) ) return;
        
        $user_session_acccount = sys_decrypt($cached_token);
        if( ! is_numeric($user_session_acccount) ) return;
        
        $cached_row = $mem_cache->get("account:{$user_session_acccount}");
        if( ! empty($cached_row) )
        {
            $this->assign_from_object($cached_row);
            $this->_exists = true;
            
            if( ! $this->_is_locked && $this->level >= config::COADMIN_USER_LEVEL )
                    $this->_is_admin = true;
            
            # IP change detection
            if( $this->_is_admin ) $this->check_last_login_ip();
            
            return;
        }
        
        $res = $database->query("select * from account where id_account = '$user_session_acccount'");
        
        if( ! $res ) return;
        if( $database->num_rows($res) == 0 ) return;
        
        # Record loading
        $row = $database->fetch_object($res);
        $mem_cache->set("account:{$user_session_acccount}", $row, 0, 600);
        
        # Validation
        if($row->state != "enabled") return;
        
        # Device identification
        $device_cookie_key = "_" . $config->website_key . "_DIC";
        
        $device = null;
        if( empty($_COOKIE[$device_cookie_key]) )
        {
            $device = new device($row->id_account);
            if( ! $device->_exists               ) return;
            if( $device->state == "disabled"     ) return;
            if( $device->state == "deleted"      ) return;
            if( $device->state == "unregistered" ) $this->_is_locked = true;
        }
        
        # Integration
        $this->assign_from_object($row);
        $this->_exists = true;
        
        # Admin identification
        if( ! $this->_is_locked && $this->level >= config::COADMIN_USER_LEVEL )
                $this->_is_admin = true;
        
        # IP change detection
        if( $this->_is_admin ) $this->check_last_login_ip();
        
        $user_online_cookie_key = $settings->get("engine.user_online_cookie");
        
        if( isset($_COOKIE[$user_online_cookie_key]) )
        {
            # The "online" session cookie is set, let's check if it corresponds to the same user
            $raw_cookied_token = sys_decrypt( $_COOKIE[$user_online_cookie_key] );
            if( empty($raw_cookied_token) ) throw_fake_401();
            
            $cached_token = $mem_cache->get("@!uot_{$raw_cookied_token}");
            if( empty($cached_token) ) throw_fake_401();
            
            $tokenized_user_id = sys_decrypt( $cached_token );
            if( $tokenized_user_id != $this->id_account ) throw_fake_401();
        }
        else
        {
            # Let's do an auto-login
            
            $session_token = $this->build_session_token();
            $mem_cache->set("@!uot_{$session_token}", sys_encrypt($this->id_account), 0, time() + (86400 * 3));
            setcookie(
                $user_online_cookie_key,
                sys_encrypt( $session_token ),
                0, "/", $config->cookies_domain,
                (bool) $_SERVER["HTTPS"],
                true
            );
            
            $session_token = $this->build_session_token();
            $mem_cache->set("@!udt_{$session_token}", sys_encrypt($this->id_account), 0, time() + (86400 * 3));
            setcookie(
                $device_cookie_key,
                sys_encrypt( $session_token ),
                0, "/", $config->cookies_domain,
                (bool) $_SERVER["HTTPS"],
                true
            );
            
            $min_loggin_level = (int) $settings->get("engine.min_user_level_for_ip_dismissal");
            if( $min_loggin_level > 0 && $this->level >= $min_loggin_level )
            {
                $ip       = "";
                $host     = "";
                $location = "";
            }
            else
            {
                $ip       = get_remote_address();
                $host     = addslashes(@gethostbyaddr($ip));
                $location = addslashes(get_geoip_location_with_isp($ip));
            }
            
            $config->globals["@accounts:account_id_logging_in"] = $this->id_account;
            $modules["accounts"]->load_extensions("login", "before_inserting_login_record");
            
            $database->exec("
                insert ignore into account_logins set
                `id_account` = '$this->id_account',
                `id_device`  = '$device->id_device',
                `login_date` = '".date("Y-m-d H:i:s")."',
                `ip`         = '$ip',
                `hostname`   = '$host',
                `location`   = '$location'
            ");
            
            $this->extend_session_cookie($device);
        }
        
        if( ! is_null($device) ) $device->ping();
    }
    
    /**
     * Checks if the user IP has changed. Normally used after a session being opened from a cookie
     * (user_session set, user_online unset).
     * 
     * @return void
     * @throws \Exception
     */
    protected function check_last_login_ip()
    {
        global $config, $settings;
        
        /** @var module[] $modules */
        global $modules;
        
        if( $settings->get("modules:accounts.track_last_login_ip") != "true" ) return;
        
        $last_login_ip = $this->get_engine_pref("!core:last_login_ip");
        if( empty($last_login_ip) ) return;
        
        $current_ip = get_user_ip();
        if( empty($current_ip) ) return;
        
        # Check this IP
        
        if( $current_ip == $last_login_ip ) return;
        
        # Checks for regular users - admins are enforced to strict IPs or whitelist.
        if( ! $this->_is_admin )
        {
            # Check this seg (first 3 octets)
            
            $parts = explode(".", $current_ip); array_pop($parts);
            $current_segment = implode(".", $parts);
            
            $parts = explode(".", $last_login_ip); array_pop($parts);
            $last_login_segment = implode(".", $parts);
            
            if( $current_segment == $last_login_segment ) return;
            
            # Check this subnet (first 2 octets)
            
            $parts = explode(".", $current_ip); array_pop($parts); array_pop($parts);
            $current_network = implode(".", $parts);
            
            $parts = explode(".", $last_login_ip); array_pop($parts); array_pop($parts);
            $last_login_network = implode(".", $parts);
            
            if( $current_network == $last_login_network ) return;
        }
        
        # Check among whitelisted IPs
        
        $ips_whitelist = $this->get_engine_pref("@accounts:ips_whitelist");
        if( ! empty($ips_whitelist) )
        {
            $ip    = $current_ip;
            $lines = explode("\n", $ips_whitelist);
            $found = false;
            foreach($lines as $line)
            {
                $listed_ip = trim($line);
                if( empty($listed_ip) ) continue;
                if( substr($listed_ip, 0, 1) == "#" ) continue;
                
                if( stristr($listed_ip, "*") )
                {
                    $pattern = str_replace(".", "\\.", $listed_ip);
                    $pattern = str_replace("*", ".*",  $pattern);
                    
                    if(preg_match("/$pattern/", $ip) )
                    {
                        $found = true;
                        break;
                    }
                }
                else
                {
                    if( $ip == $listed_ip )
                    {
                        $found = true;
                        break;
                    }
                }
            }
            
            if( $found ) return;
        }
        
        # All tests failed - all stop.
        
        $logdate  = date("Ymd");
        $logfile  = "{$config->logfiles_location}/sessions_closed-$logdate.log";
        $lognowd  = date("H:i:s");
        $location = get_geoip_location($current_ip);
        $isp      = get_geoip_isp($current_ip);
        $agent    = $_SERVER["HTTP_USER_AGENT"];
        $logmsg   = "[$lognowd] - #{$this->id_account} ({$this->user_name}) Session closed.\n"
                  . "             Last login IP: $last_login_ip\n"
                  . "             Current IP:    $current_ip\n"
                  . "             Location:      $location\n"
                  . "             ISP:           $isp\n"
                  . "             User agent:    $agent\n\n"
        ;
        @file_put_contents($logfile, $logmsg, FILE_APPEND);
        
        $config->globals["@accounts:session_autoclose_account_record"] = $this;
        $config->globals["@accounts:session_autoclose_last_login_ip"]  = $last_login_ip;
        $config->globals["@accounts:session_autoclose_this_login_ip"]  = $current_ip;
        $modules["accounts"]->load_extensions("check_last_login_ip", "before_session_autoclose");
        
        setcookie("ip_changed", "true", 0, "/", $config->cookies_domain);
        $this->close_session("{$config->full_root_path}/?show_login_form=true");
        die();
    }
    
    /**
     * Pings the account and sets a cookie with the account id.
     * 
     * @param device $device
     * 
     * @throws \Exception
     */
    public function open_session($device)
    {
        global $config, $settings, $database, $modules, $mem_cache;
        
        # Inits
        $now = date("Y-m-d H:i:s");
        
        # First we set the cookie
        $this->extend_session_cookie($device);
        
        # Set a token for the user
        $session_token = $this->build_session_token();
        $mem_cache->set("@!uot_{$session_token}", sys_encrypt($this->id_account), 0, time() + (86400 * 3));
        
        # Set the online session cookie
        setcookie(
            $settings->get("engine.user_online_cookie"),
            sys_encrypt( $session_token ),
            0, "/", $config->cookies_domain,
            (bool) $_SERVER["HTTPS"],
            true
        );
        
        if( $settings->get("modules:accounts.track_last_login_ip") == "true" )
        {
            $current_ip = get_user_ip();
            $this->set_engine_pref("!core:last_login_ip", $current_ip);
        }
        
        $min_loggin_level = (int) $settings->get("engine.min_user_level_for_ip_dismissal");
        if( $min_loggin_level > 0 && $this->level >= $min_loggin_level )
        {
            $ip       = "";
            $host     = "";
            $location = "";
        }
        else
        {
            $ip       = get_remote_address();
            $host     = addslashes(@gethostbyaddr($ip));
            $location = addslashes(get_geoip_location_with_isp($ip));
        }
        
        $config->globals["@accounts:account_id_logging_in"] = $this->id_account;
        $modules["accounts"]->load_extensions("login", "before_inserting_login_record");
        
        # Now we insert the record in the logins table
        $database->exec("
            insert ignore into account_logins set
            `id_account` = '$this->id_account',
            `id_device`  = '$device->id_device',
            `login_date` = '$now',
            `ip`         = '$ip',
            `hostname`   = '$host',
            `location`   = '$location'
        ");
        
        # Let's ping the device
        $device->ping();
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->after_opening_session) )
                include "{$module->abspath}/{$module->php_includes->after_opening_session}";
    }
    
    protected function extend_session_cookie($device)
    {
        global $config, $settings, $mem_cache;
        
        if( is_null($device) ) return;
        
        if( $device->_exists ) $session_time = time() + ( 86400 * 30 );
        else                   $session_time = 0;
        
        $session_token = $this->build_session_token();
        $mem_cache->set("@!ust_{$session_token}", sys_encrypt($this->id_account), 0, time() + (86400 * 30));
        
        setcookie(
            $settings->get("engine.user_session_cookie"),
            sys_encrypt( $session_token ),
            $session_time, "/", $config->cookies_domain,
            (bool) $_SERVER["HTTPS"],
            true
        );
    }
    
    public function close_session($redirect_to = "")
    {
        global $settings, $mem_cache, $account, $config, $modules;
        
        $ust = $_COOKIE[$settings->get("engine.user_session_cookie")];
        if( ! empty($ust) ) $mem_cache->delete("@!ust_{$ust}");
        
        $uot = $_COOKIE[$settings->get("engine.user_online_cookie")];
        if( ! empty($uot) ) $mem_cache->delete("@!uot_{$uot}");
        
        $device_cookie_key = "_" . $config->website_key . "_DIC";
        $udt = $_COOKIE[$device_cookie_key];
        if( ! empty($udt) ) $mem_cache->delete("@!udt_{$udt}");
        
        setcookie( $settings->get("engine.user_session_cookie"), "", 0, "/", $config->cookies_domain );
        setcookie( $settings->get("engine.user_online_cookie"),  "", 0, "/", $config->cookies_domain );
        setcookie( $device_cookie_key                         ,  "", 0, "/", $config->cookies_domain );
        unset(
            $_COOKIE[$settings->get("engine.user_session_cookie")],
            $_COOKIE[$settings->get("engine.user_online_cookie")],
            $_COOKIE[$device_cookie_key]
        );
        $mem_cache->delete("account:{$account->id_account}");
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->after_closing_session) )
                include "{$module->abspath}/{$module->php_includes->after_closing_session}";
        
        if( ! empty($redirect_to) )
        {
            header("Location: $redirect_to");
            die();
        }
    }
    
    /**
     * Saves account to database
     */
    public function save()
    {
        global $database, $mem_cache, $modules;
        
        $now = date("Y-m-d H:i:s");
        if( ! $this->_exists )
        {
            $address = get_remote_address();
            if( ! empty($address) ) $address .= "; " . @gethostbyaddr($address);
            
            $this->creation_host    = $address;
            $this->creation_date    =
            $this->last_update      =
            $this->last_activity    = $now;
            $query = "
                insert into account set
                id_account    = '".addslashes($this->id_account)."'     ,
                user_name     = '".addslashes($this->user_name)."'      ,
                password      = '".addslashes($this->password)."'       ,
                display_name  = '".addslashes($this->display_name)."'   ,
                email         = '".addslashes($this->email)."'          ,
                alt_email     = '".addslashes($this->alt_email)."'      ,
                birthdate     = '".addslashes($this->birthdate)."'      ,
                homepage_url  = '".addslashes($this->homepage_url)."'   ,
                country       = '".addslashes($this->country)."'        ,
                level         = '".addslashes($this->level)."'          ,
                state         = '".addslashes($this->state)."'          ,
                creation_host = '".addslashes($this->creation_host)."'  ,
                creation_date = '$now'                                  ,
                last_update   = '$now'
            ";
        }
        else
        {
            $query = "
                update account set
                    password       = '".addslashes($this->password)."'      ,
                    display_name   = '".addslashes($this->display_name)."'  ,
                    email          = '".addslashes($this->email)."'         ,
                    alt_email      = '".addslashes($this->alt_email)."'     ,
                    birthdate      = '".addslashes($this->birthdate)."'     ,
                    avatar         = '".addslashes($this->avatar)."'        ,
                    profile_banner = '".addslashes($this->profile_banner)."',
                    signature      = '".addslashes($this->signature)."'     ,
                    bio            = '".addslashes($this->bio)."'           ,
                    homepage_url   = '".addslashes($this->homepage_url)."'  ,
                    country        = '".addslashes($this->country)."'       ,
                    last_update    = '$now'
                where
                    id_account     = '".addslashes($this->id_account)."'
            ";
        }
        
        $res = $database->exec($query);
        if( $res ) $mem_cache->delete("account:{$this->id_account}");
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->after_saving_account) )
                include "{$module->abspath}/{$module->php_includes->after_saving_account}";
        
        return $res;
    }
    
    public function add_to_changelog($caption, $details = "")
    {
        global $database;
        
        $date = date("Y-m-d H:i:s");
        $text = "• [$date] - $caption";
        if( empty($details) ) $text .= "\n\n";
        else                  $text .= ":\n$details\n\n";
        
        $text = addslashes($text);
        
        $database->exec("
            update account set changelog = concat(changelog, '$text')
            where id_account = '$this->id_account'
        ");
    }
    
    /**
     * Sends a confirmation email to new account creator with a token
     */
    public function send_new_account_confirmation_email()
    {
        global $config, $settings, $current_module;
        
        $limit        = date("Y-m-d H:i:s", strtotime("now + 70 minutes"));
        $token        = encrypt( $this->id_account."\t".$limit, $config->encryption_key );
        $token_url    = "{$config->full_root_url}/confirm_account?token=".urlencode($token);
        $ip           = get_remote_address();
        $hostname     = @gethostbyaddr($ip);
        $fecha_envio  = date("Y-m-d H:i:s");
        
        $recipients = array($this->display_name => $this->email);
        if( ! empty($this->alt_email) ) $recipients["$this->display_name (2)"] = $this->alt_email;
        
        $request_location = get_geoip_disclosable_location($ip);
        
        $mail_subject = replace_escaped_vars(
            $current_module->language->email_templates->confirm_account->subject,
            array('{$user_name}', '{$website_name}'),
            array($this->user_name, $settings->get("engine.website_name"))
        );
        $mail_body = replace_escaped_vars(
            $current_module->language->email_templates->confirm_account->body,
            array(
                '{$website_name}',
                '{$display_name}',
                '{$token_url}',
                '{$main_email}',
                '{$alt_email}',
                '{$date_sent}',
                '{$request_ip}',
                '{$request_hostname}',
                '{$request_location}',
                '{$request_user_agent}',
                '{$user_name}',
                '{$password}',
            ),
            array(
                $settings->get("engine.website_name"),
                $this->display_name,
                "<a href=\"$token_url\">$token_url</a>",
                $this->email,
                $this->alt_email,
                $fecha_envio,
                $ip,
                $hostname,
                $request_location,
                $_SERVER["HTTP_USER_AGENT"],
                $this->user_name,
                $config->globals["@accounts:show_raw_password_in_confirmation_email"]
                    ? $this->_raw_password
                    : $current_module->language->password_encrypted
            )
        );
        $mail_body = unindent($mail_body);
        
        return send_mail($mail_subject, nl2br($mail_body), $recipients);
    }
    
    /**
     * Activate an account that has been created and confirmed
     *
     * @param bool|int $set_user_level If specified, it must be integer
     *
     * @return int
     * @throws \Exception
     */
    public function activate($set_user_level = false)
    {
        global $database, $modules, $mem_cache;
        
        $now         = date("Y-m-d H:i:s");
        $this->state = "enabled";
        
        if( $set_user_level === false )
            $query = "
                update account set
                    state       = '".addslashes($this->state)."',
                    last_update = '$now'
                where
                    id_account  = '".addslashes($this->id_account)."'
            ";
        else
            $query = "
                update account set
                    state       = '".addslashes($this->state)."',
                    level       = '$set_user_level',
                    last_update = '$now'
                where
                    id_account  = '".addslashes($this->id_account)."'
            ";
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->after_activating_account) )
                include "{$module->abspath}/{$module->php_includes->after_activating_account}";
        
        $mem_cache->delete("account:{$this->id_account}");
        
        return $database->exec($query);
    }
    
    public function enable()
    {
        global $database, $modules, $mem_cache;
        
        $now              = date("Y-m-d H:i:s");
        $this->state      = "enabled";
        $query = "
            update account set
                state       = '".addslashes($this->state)."',
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->after_enabling_account) )
                include "{$module->abspath}/{$module->php_includes->after_enabling_account}";
        
        $mem_cache->delete("account:{$this->id_account}");
        
        return $database->exec($query);
    }
    
    public function disable()
    {
        global $database, $modules, $mem_cache;
        
        $now              = date("Y-m-d H:i:s");
        $this->state      = "disabled";
        $query = "
            update account set
                state       = '".addslashes($this->state)."',
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->after_disabling_account) )
                include "{$module->abspath}/{$module->php_includes->after_disabling_account}";
        
        $mem_cache->delete("account:{$this->id_account}");
            
        return $database->exec($query);
    }
    
    /**
     * NO LONGER USED
     * @deprecated
     * 
     * @return int
     * 
     * @throws \Exception
     */
    public function set_admin()
    {
        global $database, $mem_cache;
        
        $this->_is_admin = true;
        
        $now              = date("Y-m-d H:i:s");
        $query = "
            update account set
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
        
        $mem_cache->delete("account:{$this->id_account}");
        
        return $database->exec($query);
    }
    
    /**
     * NO LONGER USED
     * @deprecated
     * 
     * @return int
     * 
     * @throws \Exception
     */
    public function unset_admin()
    {
        global $database, $mem_cache;
        
        $this->_is_admin = false;
        
        $now              = date("Y-m-d H:i:s");
        $this->state      = "disabled";
        $query = "
            update account set
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
        
        $mem_cache->delete("account:{$this->id_account}");
        
        return $database->exec($query);
    }
    
    public function set_level($new_level)
    {
        global $database, $config, $modules, $mem_cache;
        
        if( $new_level >= $config::COADMIN_USER_LEVEL )
            $this->_is_admin = true;
        
        $now              = date("Y-m-d H:i:s");
        $this->level      = $new_level;
        
        $return = $database->exec("
            update account set
                level       = '$new_level',
                last_update = '$now'
            where
                id_account  = '$this->id_account'
        ");
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->after_changing_account_level) )
                include "{$module->abspath}/{$module->php_includes->after_changing_account_level}";
        
        $mem_cache->delete("account:{$this->id_account}");
        
        return $return;
    }
    
    public function set_avatar_from_post()
    {
        global $messages, $errors, $current_module, $config, $settings, $mem_cache;
        
        if( $_POST["use_gravatar"] == "true" )
        {
            $this->avatar = "@gravatar";
            
            return;
        }
        else
        {
            if( $this->avatar == "@gravatar" ) $this->avatar = "";
        }
        
        if( empty($_FILES["uploaded_avatar"]) ) return;
        if( empty($_FILES["uploaded_avatar"]["tmp_name"]) ) return;
        
        if( ! preg_match('/(.jpg|.jpeg|.png|.gif)$/i', $_FILES["uploaded_avatar"]["name"]) )
        {
            $errors[] = $current_module->language->user_account_form->messages->invalid_avatar;
            
            return;
        }
        
        if( ! is_uploaded_file($_FILES["uploaded_avatar"]["tmp_name"]) )
        {
            $errors[] = $current_module->language->user_account_form->messages->invalid_avatar_uploaded;
            
            return;
        }
        
        $target_dir = "{$config->datafiles_location}/user_avatars/{$this->user_name}";
        if( ! is_dir($target_dir) )
        {
            if( ! @mkdir($target_dir, 0777, true) )
            {
                $errors[] = $current_module->language->user_account_form->messages->cant_create_avatars_dir;
                
                return;
            }
            
            @chmod($target_dir, 0777);
        }
        
        $tmp_file = "/tmp/avatar-" . wp_sanitize_filename($_FILES["uploaded_avatar"]["name"]);
        if( ! @move_uploaded_file($_FILES["uploaded_avatar"]["tmp_name"], $tmp_file) )
        {
            $errors[] = $current_module->language->user_account_form->messages->cant_move_uploaded_file;
            
            return;
        }
        
        try
        {
            $jpeg_quality = $settings->get("engine.thumbnail_jpg_compression");
            $png_quality  = $settings->get("engine.thumbnail_png_compression");
            
            if( empty($jpeg_quality) ) $jpeg_quality = 90;
            if( empty($png_quality)  ) $png_quality  = 9;
            
            list($width, $height) = getimagesize($tmp_file);
            $dimension  = $width > $height ? THUMBNAILER_USE_HEIGHT : THUMBNAILER_USE_WIDTH;
            $new_avatar = preg_match('/(.jpg|.jpeg)$/i', $_FILES["uploaded_avatar"]["name"])
                        ? gfuncs_resample_jpg($tmp_file, $target_dir, 300, 300, $dimension, false, $jpeg_quality,        true, 300, 300)
                        : gfuncs_resample_png($tmp_file, $target_dir, 300, 300, $dimension, false, $png_quality,  false, true, 300, 300)
            ;
        }
        catch(\Exception $e)
        {
            $errors[] = $e->getMessage();
    
            return;
        }
        
        $mem_cache->delete("account:{$this->id_account}");
        
        $this->avatar = $new_avatar;
        $messages[]   = $current_module->language->user_account_form->messages->avatar_set_ok;
    }
    
    public function set_banner_from_post()
    {
        global $messages, $errors, $current_module, $config, $settings, $mem_cache;
        
        if( empty($_FILES["uploaded_profile_banner"]) ) return;
        if( empty($_FILES["uploaded_profile_banner"]["tmp_name"]) ) return;
        
        if( ! preg_match('/(.jpg|.jpeg|.png|.gif)$/i', $_FILES["uploaded_profile_banner"]["name"]) )
        {
            $errors[] = $current_module->language->user_account_form->messages->invalid_banner;
            
            return;
        }
        
        if( ! is_uploaded_file($_FILES["uploaded_profile_banner"]["tmp_name"]) )
        {
            $errors[] = $current_module->language->user_account_form->messages->invalid_banner_uploaded;
            
            return;
        }
        
        $target_dir = "{$config->datafiles_location}/user_profile_banners/{$this->user_name}";
        if( ! is_dir($target_dir) )
        {
            if( ! @mkdir($target_dir, 0777, true) )
            {
                $errors[] = $current_module->language->user_account_form->messages->cant_create_banners_dir;
                
                return;
            }
            
            @chmod($target_dir, 0777);
        }
        
        $tmp_file = "/tmp/banner-" . wp_sanitize_filename($_FILES["uploaded_profile_banner"]["name"]);
        if( ! @move_uploaded_file($_FILES["uploaded_profile_banner"]["tmp_name"], $tmp_file) )
        {
            $errors[] = $current_module->language->user_account_form->messages->cant_move_uploaded_file;
            
            return;
        }
        
        try
        {
            $jpeg_quality = $settings->get("engine.thumbnail_jpg_compression");
            $png_quality  = $settings->get("engine.thumbnail_png_compression");
            
            if( empty($jpeg_quality) ) $jpeg_quality = 90;
            if( empty($png_quality)  ) $png_quality  = 9;
            
            $new_banner = preg_match('/(.jpg|.jpeg)$/i', $_FILES["uploaded_profile_banner"]["name"])
                ? gfuncs_resample_jpg($tmp_file, $target_dir, 900, 300, THUMBNAILER_USE_WIDTH, false, $jpeg_quality,        true, 900, 300)
                : gfuncs_resample_png($tmp_file, $target_dir, 900, 300, THUMBNAILER_USE_WIDTH, false, $png_quality,  false, true, 900, 300)
            ;
        }
        catch(\Exception $e)
        {
            $errors[] = $e->getMessage();
            
            return;
        }
        
        $mem_cache->delete("account:{$this->id_account}");
        
        $this->profile_banner = $new_banner;
        $messages[]           = $current_module->language->user_account_form->messages->banner_set_ok;
    }
    
    protected function build_session_token()
    {
        $time = microtime(true);
        $rand = mt_rand(1000000000,9999999999);
        return sha1("{$this->id_account},{$this->creation_date},{$time},{$rand}");
    }
    
    public function set_expirable_token($prefix, $ttl_mins = 5)
    {
        global $mem_cache, $account;
        
        $time  = date("Y-m-d H:i:s", strtotime("now + 3 minutes"));
        $rand  = mt_rand(1000000000,9999999999);
        $token = sys_encrypt("{$this->id_account},{$this->creation_date},{$rand},{$time}");
        
        $mem_key = "{$prefix}:csrf_token.{$account->id_account}";
        $mem_cache->set($mem_key, $token, 0, 60 * $ttl_mins);
    }
    
    public function is_expirable_token_valid($prefix)
    {
        global $mem_cache, $account;
        
        $mem_key = "{$prefix}:csrf_token.{$account->id_account}";
        $token   = $mem_cache->get($mem_key);
        
        if( empty($token) ) return false;
        
        $token = sys_decrypt($token);
        if( ! is_string($token) ) return false;
        
        list($tida, $tacd, $trnd, $texp) = explode(",", $token);
        if( ! is_numeric($tida) || empty($tida) || empty($tacd) || empty($trnd) || empty($texp) )
            return false;
        
        try { new \DateTime($texp); }
        catch(\Exception $e) { return false; }
        
        if(date("Y-m-d H:i:s") > $texp) return false;
        
        return true;
    }
}
