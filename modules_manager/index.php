<?php
/**
* Modules manager admin index
* 
* @package    BadCanvas
* @subpackage Modules::included::modules_manager
* @author     Alejandro Caballero<lava.caballero@gmail.com>
*/

use wcms_base\module;

$_ROOT_URL = "..";
include "{$_ROOT_URL}/config.php";
include "{$_ROOT_URL}/includes/bootstrap.inc";

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

switch( $module_install_action )
{
    #==============
    case "install":
    #==============
    {
        $this_module = new module(ABSPATH . "/$do_module_name/module_info.xml");
        if( $this_module->installed )
        {
            $errors[] = $current_module->language->task_messages->already_installed;
        }
        else
        {
            if( ! file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
            {
                $messages[] = $current_module->language->task_messages->installed_ok;
                $settings->set("modules:$do_module_name.installed", "true");
                $settings->set("modules:$do_module_name.enabled", "true");
                $messages[] = $current_module->language->task_messages->enabled_ok;
                $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
            }
            else
            {
                include ABSPATH . "/$do_module_name/module_install.inc";
                if( count($errors) > 0 )
                {
                    $errors[] = $current_module->language->task_messages->installed_ko;
                }
                else
                {
                    $settings->set("modules:$do_module_name.installed", "true");
                    $messages[] = $current_module->language->task_messages->installed_ok;
                    
                    $module_install_action = "enable";
                    include ABSPATH . "/$do_module_name/module_install.inc";
                    if( count($errors) > 0 )
                    {
                        $errors[] = $current_module->language->task_messages->enabled_ko;
                    }
                    else
                    {
                        $settings->set("modules:$do_module_name.enabled", "true");
                        $messages[] = $current_module->language->task_messages->enabled_ok;
                        $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
                    }
                }
            }
        }
        break;
    }
    #=============
    case "enable":
    #=============
    {
        $this_module = new module(ABSPATH . "/$do_module_name/module_info.xml");
        if( ! $this_module->installed )
        {
            $errors[] = $current_module->language->task_messages->not_installed;
        }
        elseif( $this_module->enabled )
        {
            $errors[] = $current_module->language->task_messages->already_enabled;
        }
        else
        {
            if( ! file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
            {
                $settings->set("modules:$do_module_name.enabled", "true");
                $messages[] = $current_module->language->task_messages->enabled_ok;
                $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
            }
            else
            {
                include ABSPATH . "/$do_module_name/module_install.inc";
                if( count($errors) > 0 )
                {
                    $errors[] = $current_module->language->task_messages->enabled_ko;
                }
                else
                {
                    $settings->set("modules:$do_module_name.enabled", "true");
                    $messages[] = $current_module->language->task_messages->enabled_ok;
                    $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
                }
            }
        }
        break;
    }
    #==============
    case "disable":
    #==============
    {
        $this_module = new module(ABSPATH . "/$do_module_name/module_info.xml");
        if( ! $this_module->installed )
        {
            $errors[] = $current_module->language->task_messages->not_installed;
        }
        elseif( ! $this_module->enabled )
        {
            $errors[] = $current_module->language->task_messages->not_enabled;
        }
        else
        {
            if( ! file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
            {
                $settings->set("modules:$do_module_name.enabled", "false");
                $messages[] = $current_module->language->task_messages->disabled_ok;
                $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
            }
            else
            {
                include ABSPATH . "/$do_module_name/module_install.inc";
                if( count($errors) > 0 )
                {
                    $errors[] = $current_module->language->task_messages->disabled_ko;
                }
                else
                {
                    $settings->set("modules:$do_module_name.enabled", "false");
                    $messages[] = $current_module->language->task_messages->disabled_ok;
                    $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
                }
            }
        }
        break;
    }
    #================
    case "uninstall":
    #================
    {
        $this_module = new module(ABSPATH . "/$do_module_name/module_info.xml");
        if( ! $this_module->installed )
        {
            $errors[] = $current_module->language->task_messages->not_installed;
        }
        else
        {
            if( ! file_exists(ABSPATH . "/$do_module_name/module_install.inc") )
            {
                $settings->set("modules:$do_module_name.enabled", "false");
                $messages[] = $current_module->language->task_messages->disabled_ok;
                $settings->set("modules:$do_module_name.installed", "false");
                $messages[] = $current_module->language->task_messages->uninstalled_ok;
                $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
            }
            else
            {
                $module_install_action = "disable";
                include ABSPATH . "/$do_module_name/module_install.inc";
                if( count($errors) > 0 )
                {
                    $errors[] = $current_module->language->task_messages->disabled_ko;
                }
                else
                {
                    $settings->set("modules:$do_module_name.enabled", "false");
                    $messages[] = $current_module->language->task_messages->disabled_ok;
                    
                    $module_install_action = "uninstall";
                    include ABSPATH . "/$do_module_name/module_install.inc";
                    if( count($errors) > 0 )
                    {
                        $errors[] = $current_module->language->task_messages->uninstalled_ko;
                    }
                    else
                    {
                        $settings->set("modules:$do_module_name.installed", "false");
                        $messages[] = $current_module->language->task_messages->uninstalled_ok;
                        $messages[] = replace_escaped_vars($current_module->language->task_messages->all_ops_ok, '{$self_link}', "javascript:reload_self()");
                    }
                }
            }
        }
        break;
    }
}

$template->page_contents_include = "index.nav.inc";
$template->set_page_title($current_module->language->page_title);
include "{$template->abspath}/admin.php";
