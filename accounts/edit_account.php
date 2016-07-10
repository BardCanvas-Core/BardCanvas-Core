<?php
/**
 * User account editor
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

include "../config.php";
include "../includes/bootstrap.inc";

if( ! $account->_exists ) throw_fake_401();

$errors = $messages = array();
if( $_POST["mode"] == "save" )
{
    # This keeps the whole posted data prepped for output on the form
    $user_name = $account->user_name;
    $account->assign_from_posted_form();
    $account->user_name = $user_name;
    
    # Validations: missing fields
    foreach( array("display_name", "country", "email" ) as $field )
        if( trim(stripslashes($_POST[$field])) == "" ) $errors[] = $current_module->language->errors->registration->missing->{$field};
    
    # Validations: invalid entries
    if( ! filter_var(trim(stripslashes($_POST["email"])), FILTER_VALIDATE_EMAIL) )
        $errors[] = $current_module->language->errors->registration->invalid->email;
    
    if( trim(stripslashes($_POST["alt_email"])) != "" )
        if( ! filter_var(trim(stripslashes($_POST["alt_email"])), FILTER_VALIDATE_EMAIL) )
            $errors[] = $current_module->language->errors->registration->invalid->alt_email;
    
    if( trim(stripslashes($_POST["alt_email"])) != "" )
        if( trim(stripslashes($_POST["email"])) == trim(stripslashes($_POST["alt_email"])) )
            $errors[] = $current_module->language->errors->registration->invalid->same_emails;
    
    if( trim(stripslashes($_POST["password"])) != "" && trim(stripslashes($_POST["password2"])) == "" )
        $errors[] = $current_module->language->errors->registration->invalid->passwords_mismatch;
    
    if( trim(stripslashes($_POST["password"])) == "" && trim(stripslashes($_POST["password2"])) != "" )
        $errors[] = $current_module->language->errors->registration->invalid->passwords_mismatch;
    
    if( trim(stripslashes($_POST["password"])) != "" && trim(stripslashes($_POST["password2"])) != "" )
        if( trim(stripslashes($_POST["password"])) != trim(stripslashes($_POST["password2"])) )
            $errors[] = $current_module->language->errors->registration->invalid->passwords_mismatch;
    
    # Impersonation tries
    $res = $database->query("select * from account where id_account <> '$account->id_account' and (email = '".trim(stripslashes($_POST["email"]))."' or alt_email = '".trim(stripslashes($_POST["email"]))."')");
    
    if( $database->num_rows($res) > 0 ) $errors[] = $current_module->language->errors->registration->invalid->main_email_exists;
    
    if( trim(stripslashes($_POST["alt_email"])) != "" )
    {
        $res = $database->query("select * from account where id_account <> '$account->id_account' and (email = '".trim(stripslashes($_POST["alt_email"]))."' or alt_email = '".trim(stripslashes($_POST["alt_email"]))."')");
        if( $database->num_rows($res) > 0 ) $errors[] = $current_module->language->errors->registration->invalid->alt_email_exists;
    }
    
    # Actual save
    if( count($errors) == 0 )
    {
        if( trim(stripslashes($_POST["password"])) != "" ) $account->password = md5($account->_raw_password);
        $account->save();
        $messages[] = $current_module->language->edit_account_form->saved_ok;
    } # end if
    
}

$xaccount = $account;

# Country list preload
$current_user_country = $xaccount->country;
$countries            = array();
$res                  = $database->query("select * from countries order by name asc");
while( $row = $database->fetch_object($res) ) $countries[$row->alpha_2] = $row->name;

$_errors               = $errors;
$_messages             = $messages;
$_country_list         = $countries;
$_current_user_country = strtolower($current_user_country);

$template->set_page_title($current_module->language->page_titles->edit_account);
$template->page_contents_include = "edit_account_form.tpl.inc";
include "{$template->abspath}/main.php";
