<?php
/**
 * User account class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base;

use hng2_cache\disk_cache;

class account
{
    public $id_account;
    public $user_name;
    public $password;
    public $display_name;
    public $email;
    public $alt_email;
    public $birthdate;
    public $avatar;
    public $profile_banner;
    public $signature;
    public $bio;
    public $homepage_url;
    public $country;
    public $level = 1;
    public $engine_prefs = array();
    public $state = "new";
    public $creation_host;
    public $creation_date;
    public $last_update;
    public $last_activity;
    
    # Dynamically loaded
    public $country_name;
    
    # Temporary and control
    public $_raw_password;
    public $_exists    = false;
    public $_is_admin  = false;
    public $_is_locked = false;
    
    /**
     * @var disk_cache
     */
    private $engine_prefs_cache;
    
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
        $res   = $database->query("
            select
                account.*,
                (select name from countries where alpha_2 = account.country) as country_name
            from
                account
            where
                id_account = '$input' or user_name = '$input'
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
     *
     * @return $this
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
        $this->id_account = uniqid();
    }
    
    /**
     * Loads an account from a cookie -if set-
     */
    public function load_session()
    {
        global $config, $settings, $database, $mem_cache;
        
        if( empty($_COOKIE[$settings->get("engine.user_session_cookie")]) ) return;
        
        $user_session_acccount = decrypt(
            $_COOKIE[$settings->get("engine.user_session_cookie")],
            $config->encryption_key
        );
        
        $cached_row = $mem_cache->get("account:{$user_session_acccount}");
        if( ! empty($cached_row) )
        {
            $this->assign_from_object($cached_row);
            $this->_exists = true;
            
            if( ! $this->_is_locked && $this->level >= config::COADMIN_USER_LEVEL )
                    $this->_is_admin = true;
            
            return;
        }
        
        $res = $database->query("select * from account where id_account = '".addslashes(trim($user_session_acccount))."'");
        
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
        
        if( isset($_COOKIE[$settings->get("engine.user_online_cookie")]) )
        {
            # The "online" session cookie is set, let's check if it corresponds to the same user
            $online_user_cookie = decrypt(
                $_COOKIE[$settings->get("engine.user_online_cookie")],
                $config->encryption_key
            );
    
            if( $online_user_cookie != $this->id_account ) throw_fake_401();
        }
        else
        {
            # Let's do an auto-login if the "online" cookie is not set
            setcookie(
                $settings->get("engine.user_online_cookie"),
                encrypt( $this->id_account, $config->encryption_key ),
                0, "/"
            );
            
            setcookie(
                $device_cookie_key,
                encrypt( $device->id_device, $config->encryption_key ),
                0, "/"
            );
            
            $database->exec("
                insert into account_logins set
                `id_account` = '$this->id_account',
                `id_device`  = '$device->id_device',
                `login_date` = '".date("Y-m-d H:i:s")."',
                `ip`         = '".get_remote_address()."',
                `hostname`   = '".gethostbyaddr(get_remote_address())."',
                `location`   = '".forge_geoip_location(get_remote_address())."'
            ");
            
            $this->extend_session_cookie($device);
        }
        
        if( ! is_null($device) ) $device->ping();
    }
    
    /**
     * Pings the account and sets a cookie with the account id.
     * 
     * @param device $device
     */
    public function open_session($device)
    {
        global $config, $settings, $database;
        
        # Inits
        $now = date("Y-m-d H:i:s");
        
        # First we set the cookie
        $this->extend_session_cookie($device);
        
        # Set the online session cookie
        setcookie(
            $settings->get("engine.user_online_cookie"),
            encrypt( $this->id_account, $config->encryption_key ),
            0, "/"
        );
        
        # Now we insert the record in the logins table
        $database->exec("
            insert into account_logins set
            `id_account` = '$this->id_account',
            `id_device`  = '$device->id_device',
            `login_date` = '$now',
            `ip`         = '".get_remote_address()."',
            `hostname`   = '".gethostbyaddr(get_remote_address())."',
            `location`   = '".forge_geoip_location(get_remote_address())."'
        ");
        
        # Let's ping the device
        $device->ping();
    }
    
    protected function extend_session_cookie(device $device)
    {
        global $config, $settings;
    
        if( $device->_exists ) $session_time = time() + (86400*7);
        else                   $session_time = 0;
        
        setcookie(
            $settings->get("engine.user_session_cookie"),
            encrypt( $this->id_account, $config->encryption_key ),
            $session_time, "/"
        );
    }
    
    public function close_session()
    {
        global $settings, $mem_cache, $account;
        
        setcookie( $settings->get("engine.user_session_cookie"), "", 0, "/" );
        setcookie( $settings->get("engine.user_online_cookie"), "", 0, "/" );
        unset( $_COOKIE[$settings->get("engine.user_session_cookie")], $_COOKIE[$settings->get("engine.user_online_cookie")] );
        $mem_cache->delete("account:{$account->id_account}");
    }
    
    /**
     * Saves account to database
     */
    public function save()
    {
        global $database, $mem_cache;
        
        $now = date("Y-m-d H:i:s");
        if( ! $this->_exists )
        {
            $this->creation_host    = get_remote_address() . "; " . gethostbyaddr(get_remote_address());
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
        
        return $res;
    }
    
    /**
     * Sends a confirmation email to new account creator with a token
     */
    public function send_new_account_confirmation_email()
    {
        global $config, $settings, $current_module;
        
        $limit        = date("Y-m-d H:i:s", strtotime("now + 70 minutes"));
        $token        = encrypt( $this->id_account."\t".$limit, $config->encryption_key );
        $token_url    = (empty($_SERVER["HTTPS"]) ? "http://" : "https://").$_SERVER["HTTP_HOST"]."/confirm_account?token=".urlencode($token);
        $ip           = get_remote_address();
        $hostname     = gethostbyaddr(get_remote_address());
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = $settings->get("engine.mail_sender_name")."<".$settings->get("engine.mail_sender_email").">";
        $mail_to      = "$this->display_name<$this->email>";
        $mail_alt     = "$this->display_name alternate email<$this->email>";
        
        $request_location = forge_geoip_location($ip);
        
        $mail_subject = replace_escaped_vars(
                            $current_module->language->email_templates->confirm_account->subject,
                            array('{$user_name}', '{$website_name}'),
                            array($this->user_name, $settings->get("engine.website_name"))
                        );
        $mail_body = replace_escaped_vars(
                         $current_module->language->email_templates->confirm_account->body,
                         array('{$website_name}',                       '{$display_name}',     '{$token_url}', '{$main_email}', '{$alt_email}',     '{$date_sent}', '{$request_ip}', '{$request_hostname}', '{$request_location}', '{$request_user_agent}'      ),
                         array(  $settings->get("engine.website_name"),   $this->display_name,   $token_url,     $this->email,    $this->alt_email,   $fecha_envio,   $ip,             $hostname,             $request_location,     $_SERVER["HTTP_USER_AGENT"])
                     );
        $mail_body = str_replace("<br />", "", preg_replace('/\n\s*/', "\n", nl2br($mail_body)));
        return @mail(
            $mail_to, $mail_subject, $mail_body, 
            "From: ".$mail_from . "\r\n" . 
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=utf-8\r\n" .
            (empty($this->alt_email) ? "" : "CC: ".$mail_alt."\r\n")
        );
    }
    
    /**
     * Activate an account that has been created and confirmed
     */
    public function activate()
    {
        global $database;
        
        $now              = date("Y-m-d H:i:s");
        $this->state      = "enabled";
        $query = "
            update account set
                state       = '".addslashes($this->state)."',
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
        
        return $database->exec($query);
    }
    
    public function enable()
    {
        global $database;
        
        $now              = date("Y-m-d H:i:s");
        $this->state      = "enabled";
        $query = "
            update account set
                state       = '".addslashes($this->state)."',
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
    
        return $database->exec($query);
    }
    
    public function disable()
    {
        global $database;
        
        $now              = date("Y-m-d H:i:s");
        $this->state      = "disabled";
        $query = "
            update account set
                state       = '".addslashes($this->state)."',
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
    
        return $database->exec($query);
    }
    
    public function set_admin()
    {
        global $database;
        
        $this->_is_admin = true;
        
        $now              = date("Y-m-d H:i:s");
        $query = "
            update account set
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
        
        return $database->exec($query);
    }
    
    public function unset_admin()
    {
        global $database;
        
        $this->_is_admin = false;
        
        $now              = date("Y-m-d H:i:s");
        $this->state      = "disabled";
        $query = "
            update account set
                last_update = '$now'
            where
                id_account  = '".addslashes($this->id_account)."'
        ";
        
        return $database->exec($query);
    }
    
    private function load_engine_prefs()
    {
        global $database;
    
        $this->init_engine_prefs_cache();
        if( $this->engine_prefs_cache->loaded )
        {
            $this->engine_prefs = $this->engine_prefs_cache->get_all();
            
            return;
        }
        
        $this->engine_prefs = array();
        $res = $database->query("
            select * from account_engine_prefs where id_account = '$this->id_account' order by `name` asc
        ");
        
        if( ! $res ) return;
        if( $database->num_rows($res) == 0 )
        {
            $this->engine_prefs_cache->set("_none_", "Left here on purpose. Discardable.");
            
            return;
        }
        
        while( $row = $database->fetch_object($res) )
            $this->engine_prefs[$row->name] = json_decode($row->value);
        
        $this->engine_prefs_cache->prefill($this->engine_prefs);
    }
    
    public function set_engine_pref($key, $value)
    {
        global $database;
        
        if( empty($value) )
        {
            if( isset($this->engine_prefs[$key]) )
            {
                unset( $this->engine_prefs[$key] );
                $this->engine_prefs_cache->set($key, "");
            }
    
            $database->exec("
                delete from account_engine_prefs where
                    id_account = '".addslashes($this->id_account)."' and
                    `name`  = '$key'
            ");
        }
        else
        {
            $this->engine_prefs[$key] = $value;
            $this->engine_prefs_cache->set($key, $value);
    
            $database->exec("
                insert into account_engine_prefs set
                    id_account = '".addslashes($this->id_account)."',
                    `name`     = '$key',
                    `value`    = '".json_encode($value)."'
                on duplicate key update
                    `value`    = '".json_encode($value)."'
            ");
        }
    }
    
    private function init_engine_prefs_cache()
    {
        global $config;
        
        if( ! is_object($this->engine_prefs_cache) )
            $this->engine_prefs_cache = new disk_cache(
                "{$config->datafiles_location}/cache/account_prefs_{$this->user_name}.dat"
            );
    }
    
    public function get_processed_display_name()
    {
        $contents = $this->display_name;
        $contents = convert_emojis($contents);
        
        return $contents;
    }
    
    public function get_role()
    {
        global $config;
        
        return $config->user_levels_by_level[$this->level];
    }
    
    public function set_level($new_level)
    {
        global $database, $config;
        
        if( $new_level >= $config::COADMIN_USER_LEVEL )
            $this->_is_admin = true;
        
        $now              = date("Y-m-d H:i:s");
        $this->level      = $new_level;
        
        return $database->exec("
            update account set
                level       = '$new_level',
                last_update = '$now'
            where
                id_account  = '$this->id_account'
        ");
    }
    
    public function set_avatar_from_post()
    {
        global $messages, $errors, $current_module, $config, $settings;
        
        if( $_POST["use_gravatar"] == "true" )
        {
            $this->avatar = "@gravatar";
            
            return;
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
        
        $tmp_file = "/tmp/avatar-" . sanitize_file_name($_FILES["uploaded_avatar"]["name"]);
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
        
        $this->avatar = $new_avatar;
        $messages[]   = $current_module->language->user_account_form->messages->avatar_set_ok;
    }
    
    public function set_banner_from_post()
    {
        global $messages, $errors, $current_module, $config, $settings;
        
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
        
        $tmp_file = "/tmp/banner-" . sanitize_file_name($_FILES["uploaded_profile_banner"]["name"]);
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
        
        $this->profile_banner = $new_banner;
        $messages[]           = $current_module->language->user_account_form->messages->banner_set_ok;
    }
    
    public function get_avatar_url($fully_qualified = false)
    {
        global $config;
        
        if( $this->avatar == "@gravatar" )
            return "https://www.gravatar.com/avatar/" . md5(trim(strtolower($this->email)));
        
        $file = empty($this->avatar) ? "media/default_avatar.jpg" : "user/{$this->user_name}/avatar";
        
        if( $fully_qualified ) return "{$config->full_root_url}/{$file}";
        
        return "{$config->full_root_path}/{$file}";
    }
    
    function get_profile_banner_url($fully_qualified = false)
    {
        global $config;
        
        $file = empty($this->profile_banner) ? "media/default_user_banner.jpg" : "user/{$this->user_name}/profile_banner";
        
        if( $fully_qualified ) return "{$config->full_root_url}/{$file}";
        
        return "{$config->full_root_path}/{$file}";
    }
    
    public function get_last_activity($as_elapsed_string = false)
    {
        global $database, $language;
        
        $res = $database->query("
            select last_activity from account_devices
            where account_devices.id_account = '{$this->id_account}'
            order by last_activity desc limit 1
        ");
        
        if( $database->num_rows($res) == 0 )
        {
            if($as_elapsed_string) return $language->never;
            else                   return "";
        }
        
        $row = $database->fetch_object($res);
        if( $as_elapsed_string ) return time_elapsed_string($row->last_activity);
        else                     return $row->last_activity;
    }
}
