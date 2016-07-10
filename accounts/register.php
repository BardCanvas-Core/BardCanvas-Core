<?php
/**
 * User registration page
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_base\account;

include "../config.php";
include "../includes/bootstrap.inc";
include "../lib/recaptcha-php-1.11/recaptchalib.php";

$errors = array();
if( $_POST["mode"] == "create" )
{
    # This keeps the whole posted data prepped for output on the form
    $xaccount = new account();
    $xaccount->assign_from_posted_form();
    
    # Validations: missing fields
    foreach( array("display_name", "user_name", "country", "email", "password", "password2", "recaptcha_response_field") as $field )
        if( trim(stripslashes($_POST[$field])) == "" ) $errors[] = $current_module->language->errors->registration->missing->{$field};
    
    # Validations: invalid entries
    if( ! filter_var(trim(stripslashes($_POST["email"])), FILTER_VALIDATE_EMAIL) )
        $errors[] = $current_module->language->errors->registration->invalid->email;
    
    if( trim(stripslashes($_POST["alt_email"])) != "" )
        if( ! filter_var(trim(stripslashes($_POST["alt_email"])), FILTER_VALIDATE_EMAIL) )
            $errors[] = $current_module->language->errors->registration->invalid->alt_email;
    
    if( trim(stripslashes($_POST["alt_email"])) != "" )
        if( trim(stripslashes($_POST["email"])) == trim(stripslashes($_POST["alt_email"])) )
            $errors[] = $current_module->language->errors->registration->invalid->mails_must_be_different;
    
    if( trim(stripslashes($_POST["password"])) != trim(stripslashes($_POST["password2"])) )
        $errors[] = $current_module->language->errors->registration->invalid->passwords_mismatch;
    
    # Validations: captcha
    $res = recaptcha_check_answer($settings->get("engine.recaptcha_private_key"), get_remote_address(), $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
    if( ! $res->is_valid ) $errors[] = $current_module->language->errors->registration->invalid->captcha_invalid;
    
    # Check for duplicate account
    if( count($errors) == 0 )
    {
        $yaccount = new account(trim(stripslashes($_POST["user_name"])));
        if( $yaccount->_exists ) $errors[] = $current_module->language->errors->registration->invalid->user_name_taken;
    }
    
    # Check for existing main email
    if( count($errors) == 0 )
    {
        $query = "
            select * from account 
            where email = '".trim(stripslashes($_POST["email"]))."' 
            or alt_email = '".trim(stripslashes($_POST["email"]))."'
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) > 0 ) $errors[] = $current_module->language->errors->registration->invalid->main_email_exists;
    }
    
    # Check for existing alt email
    if( count($errors) == 0 && trim(stripslashes($_POST["alt_email"])) != "" )
    {
        $query = "
            select * from account 
            where email = '".trim(stripslashes($_POST["alt_email"]))."' 
            or alt_email = '".trim(stripslashes($_POST["alt_email"]))."'
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) > 0 ) $errors[] = $current_module->language->errors->registration->invalid->alt_email_exists;
    }
    
    # Proceed to insert the account and notify the user to confirm it
    if( count($errors) == 0 )
    {
        $xaccount->password = md5($xaccount->_raw_password);
        $xaccount->set_new_id();
        $xaccount->save();
        $xaccount->send_new_account_confirmation_email();
        
        if( ! empty($_REQUEST["redir_url"]) )
        {
            header("Location: " . $_REQUEST["redir_url"]);
            die("<a href='".$_REQUEST["redir_url"]."'>".$current_module->language->click_to_continue."</a>");
        }
    
        $template->set_page_title($current_module->language->page_titles->registration_form_submitted);
        $template->page_contents_include = "register_form_submitted.tpl.inc";
        include "{$template->abspath}/main.php";
        die();
    }
}

# Country list preload
$current_user_country = empty($xaccount->country) ? get_geoip_location_data(get_remote_address()) : $xaccount->country;
$countries            = array();
$query                = "select * from countries order by name asc";
$res                  = $database->query("select * from countries order by name asc");
while( $row = $database->fetch_object($res) ) $countries[$row->alpha_2] = $row->name;

$_errors               = $errors;
$_country_list         = $countries;
$_current_user_country = strtolower($current_user_country);

$template->set_page_title($current_module->language->page_titles->registration_form);
$template->page_contents_include = "register_form.tpl.inc";
include "{$template->abspath}/main.php";
