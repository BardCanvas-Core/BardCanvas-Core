<?php
/**
 * Password reset utility
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_base\account;

include "../config.php";
include "../includes/bootstrap.inc";

if( ! empty($_POST["email"]) )
{
    $email = addslashes(trim(stripslashes($_POST["email"])));
    $query = "
        select id_account, user_name, display_name, email, alt_email, state
        from account where email = '$email' or alt_email = '$email'
    ";
    $res = $database->query($query);
    if( $database->num_rows($res) == 0 ) die( $current_module->language->reset_form->unexisting_email );
    
    $limit = date("Y-m-d H:i:s", strtotime("now + 70 minutes"));
    $tied_accounts = $target_emails = $token_urls = array();
    while($row = $database->fetch_object($res))
    {
        if( $row->state == 'disabled' || $row->state == 'new' ) continue;
        $tied_accounts[] = "• $row->user_name ~ $row->display_name ($row->email".(empty($row->alt_email) ? "" : ", $row->alt_email").")";
        $token        = encrypt( $row->id_account."\t".$limit, $config->encryption_key );
        $token_urls[] = "• $row->user_name: " . (empty($_SERVER["HTTPS"]) ? "http://" : "https://").$_SERVER["HTTP_HOST"]."/reset_password?token=".urlencode($token);
        if( ! in_array($row->email, $target_emails) ) $target_emails[] = $row->email;
        if( ! empty($row->alt_email) && ! in_array($row->email, $target_emails) ) $target_emails[] = $row->alt_email;
    }
    
    # Let's send the email
    $ip           = get_remote_address();
    $hostname     = gethostbyaddr(get_remote_address());
    $fecha_envio  = date("Y-m-d H:i:s");
    $mail_from    = $settings->get("engine.mail_sender_name")."<".$settings->get("engine.mail_sender_email").">";
    $mail_to      = implode(", ", $target_emails);
    
    $request_location = forge_geoip_location($ip);
    
    $mail_subject = replace_escaped_vars(
        $current_module->language->email_templates->reset_password->subject,
        '{$website_name}',
        $settings->get("engine.website_name")
    );
    $mail_body = replace_escaped_vars(
        $current_module->language->email_templates->reset_password->body,
        array('{$website_name}',                       '{$account_list}',                 '{$token_urls}',                '{$email}', '{$date_sent}', '{$request_ip}', '{$request_hostname}', '{$request_location}', '{$request_user_agent}'      ),
        array(  $settings->get("engine.website_name"),   implode("\n\n", $tied_accounts),   implode("\n\n", $token_urls),   $email,     $fecha_envio,   $ip,             $hostname,             $request_location,     $_SERVER["HTTP_USER_AGENT"])
    );
    $mail_body = str_replace("<br />", "", preg_replace('/\n\s*/', "\n", nl2br($mail_body)));
    @mail(
        $mail_to, $mail_subject, $mail_body, 
        "From: ".$mail_from . "\r\n" . 
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=utf-8\r\n"
    );
    
    die("OK");
}

if( ! empty($_GET["token"]) )
{
    $messages = $errors = array();
    $token = decrypt( trim(stripslashes($_REQUEST["token"])), $config->encryption_key );
    
    list($id_account, $limit) = explode("\t", $token);
    if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        $errors[] = $current_module->language->reset_form->invalid_token;
    
    if( count($errors) == 0 )
        if( date("Y-m-d H:i:s") > $limit )
            $errors[] = $current_module->language->reset_form->expired_token;
    
    if( count($errors) == 0 )
    {
        $xaccount = new account($id_account);
        if( ! $xaccount->_exists )
            $errors[] = $current_module->language->reset_form->invalid_account;
    }
    
    if( count($errors) == 0 )
    {
        if( $xaccount->state == 'disabled' || $xaccount->state == 'new' )
            $errors[] = $current_module->language->reset_form->account_disabled;
    }
    
    if( count($errors) == 0 )
    {
        $raw_password = randomPassword();
        $xaccount->password = md5($raw_password);
        $xaccount->save();
        $messages[] = replace_escaped_vars(
            $current_module->language->reset_form->password_updated,
            array('{$password}', '{$website_name}'),
            array($raw_password, $settings->get("engine.website_name"))
        );
    }
    
    $template->page_contents_include = "reset_password_output.tpl.inc";
    include "{$template->abspath}/popup.php";
    die();
}

echo $current_module->language->reset_form->incorrect_call;
