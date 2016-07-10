<?php
/**
* Modules manager admin index
* 
* @package    HNG2
* @subpackage modules_manager
* @author     Alejandro Caballero - lava.caballero@gmail.com
*/

use hng2_base\module;
use hng2_cache\disk_cache;

include "../config.php";
include "../includes/bootstrap.inc";

if( ! $account->_is_admin ) throw_fake_404();

$errors = $messages = array();

$do_module_name        = empty($_REQUEST["do_module_name"]) ? "" : trim(stripslashes($_REQUEST["do_module_name"]));
$module_install_action = empty($_REQUEST["install_action"]) ? "" : trim($_REQUEST["install_action"]);
if( empty($module_install_action) )
    if( ! empty($do_module_name) )
        if( ! is_file(ABSPATH . "/$do_module_name/module_info.xml"))
            $errors[] = $current_module->language->task_messages->module_not_found;

if( count($errors) > 0 )
{
    $template->page_contents_include = "index.nav.inc";
    $template->set_page_title($current_module->language->page_title);
    include "{$template->abspath}/admin.php";
    
    exit;
}

$update_cache = false;
switch( $module_install_action )
{
    case "install":
        
        # Precheck
        if( $settings->get("modules:$do_module_name.installed") == "true" )
        {
            $errors[] = $current_module->language->task_messages->already_installed;
            
            break;
        }
        
        # Install
        if( file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
        {
            include ABSPATH . "/$do_module_name/module_install.inc";
            if( count($errors) > 0 )
            {
                $errors[] = $current_module->language->task_messages->installed_ko;
        
                break;
            }
        }
        $settings->set("modules:$do_module_name.installed", "true");
        $update_cache = true;
        $messages[] = $current_module->language->task_messages->installed_ok;
        
        # Enable
        $module_install_action = "enable";
        if( file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
        {
            include ABSPATH . "/$do_module_name/module_install.inc";
            if( count($errors) > 0 )
            {
                $errors[] = $current_module->language->task_messages->enabled_ko;
                
                break;
            }
        }
        $settings->set("modules:$do_module_name.enabled", "true");
        $messages[] = $current_module->language->task_messages->enabled_ok;
        $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
        break;
        
    case "enable":
        
        # Prechecks
        if( $settings->get("modules:$do_module_name.installed") != "true" )
        {
            $errors[] = $current_module->language->task_messages->not_installed;
            
            break;
        }
        if( $settings->get("modules:$do_module_name.enabled") == "true" )
        {
            $errors[] = $current_module->language->task_messages->already_enabled;
            
            break;
        }
        
        # Enable
        if( file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
        {
            include ABSPATH . "/$do_module_name/module_install.inc";
            if( count($errors) > 0 )
            {
                $errors[] = $current_module->language->task_messages->enabled_ko;
                
                break;
            }
        }
        $settings->set("modules:$do_module_name.enabled", "true");
        $update_cache = true;
        $messages[] = $current_module->language->task_messages->enabled_ok;
        $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
        break;
    
    case "disable":
        
        # Prechecks
        if( $settings->get("modules:$do_module_name.installed") != "true" )
        {
            $errors[] = $current_module->language->task_messages->not_installed;
            
            break;
        }
        if( $settings->get("modules:$do_module_name.enabled") != "true" )
        {
            $errors[] = $current_module->language->task_messages->not_enabled;
            
            break;
        }
        
        # Disable
        if( file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
        {
            include ABSPATH . "/$do_module_name/module_install.inc";
            if( count($errors) > 0 )
            {
                $errors[] = $current_module->language->task_messages->disabled_ko;
                
                break;
            }
        }
        $settings->set("modules:$do_module_name.enabled", "false");
        $update_cache = true;
        $messages[] = $current_module->language->task_messages->disabled_ok;
        $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
        break;
    
    case "uninstall":
        
        # Prechecks
        if( $settings->get("modules:$do_module_name.installed") != "true" )
        {
            $errors[] = $current_module->language->task_messages->not_installed;
            
            break;
        }
        
        # Disable
        $module_install_action = "disable";
        if( file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
        {
            include ABSPATH . "/$do_module_name/module_install.inc";
            if( count($errors) > 0 )
            {
                $errors[] = $current_module->language->task_messages->disabled_ko;
                
                break;
            }
        }
        $settings->set("modules:$do_module_name.enabled", "false");
        $update_cache = true;
        $messages[] = $current_module->language->task_messages->disabled_ok;
        
        # Uninstall
        $module_install_action = "uninstall";
        if( file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
        {
            include ABSPATH . "/$do_module_name/module_install.inc";
            if( count($errors) > 0 )
            {
                $errors[] = $current_module->language->task_messages->uninstalled_ko;
                
                break;
            }
        }
        $settings->set("modules:$do_module_name.installed", "false");
        $messages[] = $current_module->language->task_messages->uninstalled_ok;
        $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
        break;
}

if( $update_cache )
{
    $modules_cache = new disk_cache("{$config->datafiles_location}/cache/modules.dat");
    $module = new module(ABSPATH . "/{$do_module_name}/module_info.xml");
    $modules_cache->set($do_module_name, $module->serialize());
}

$template->page_contents_include = "index.nav.inc";
$template->set_page_title($current_module->language->page_title);
include "{$template->abspath}/admin.php";
