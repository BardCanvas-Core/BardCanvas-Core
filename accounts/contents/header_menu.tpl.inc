<?php
/**
 * Accounts header buttons
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */
?>

<!-- accounts :: basic :: header login -->

<? if( ! $account->_exists): ?>
    
    <? if( $settings->get("modules:accounts.register_enabled") != "false" ): ?>
        <a class="main_menu_item always_visible pull-right"
           href="<?=$config->full_root_path?>/accounts/register.php">
           <span class="fa fa-pencil"></span>
           <?= $this_module->language->register ?>
        </a>
    <? endif; ?>
    
    <a class="main_menu_item always_visible pull-right"
       href="<?=$config->full_root_path?>/accounts/scripts/login.php" onclick="return show_login_form()">
       <span class="fa fa-sign-in"></span>
       <?= $this_module->language->login ?>
    </a>
    
<? else: ?>
    
    <? $added_class = preg_match('#/accounts/edit_account.php|/accounts/devices.php#i', $_SERVER["PHP_SELF"]) ? "current" : ""; ?>
    <span class="main_menu_item <?= $added_class?> always_visible pull-right is_submenu_trigger" id="loggedin_username" 
          style="cursor: pointer;" data-submenu="#user_menu_items">
        <span id="loggedin_icon" class="fa fa-border <? if(! $account->_is_locked) echo "fa-user"; else echo "fa-lock"; ?>"></span>
        <span id="loggedin_icon_locked" class="fa fa-border fa-lock" style="display: none;"></span>
        <?= $account->get_processed_display_name() ?>
        <span class="menu_toggle">
            <span class="fa fa-caret-down"></span>
            <span class="fa fa-caret-up" style="display: none;"></span>
        </span>
    </span>
    
<? endif; ?>
