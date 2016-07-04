<?
/**
 * Base template - Admin
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var string $_ROOT_URL
 */

use hng2_tools\internals;

if( ! isset($_ROOT_URL) ) $_ROOT_URL = ".";

foreach($modules as $this_module)
    if( ! empty($this_module->template_includes->pre_rendering) )
        include "{$this_module->abspath}/contents/{$this_module->template_includes->pre_rendering}";

header("Content-Type: text/html; charset=utf-8"); ?>
<!DOCTYPE html>
<html>
<head>
    <? include __DIR__ . "/segments/common_header.inc"; ?>
    
    <!-- Others -->
    <script type="text/javascript" src="<?= $_ROOT_URL ?>/lib/jquery.blockUI.js"></script>
    <script type="text/javascript" src="<?= $_ROOT_URL ?>/lib/jquery.form.min.js"></script>
    
    <!-- These must be loaded after setting $_ROOT_URL and other defaults -->
    <link rel="stylesheet" type="text/css" href="<?= $_ROOT_URL ?>/lib/jquery-lightbox/jquery.lightbox.css">
    <script type="text/javascript"          src="<?= $_ROOT_URL ?>/lib/jquery-lightbox/jquery.lightbox.js"></script>
    
    <!-- Noty -->
    <script type="text/javascript" src="<?= $_ROOT_URL ?>/lib/noty-2.3.7/js/noty/packaged/jquery.noty.packaged.min.js"></script>
    <script type="text/javascript" src="<?= $_ROOT_URL ?>/lib/noty-2.3.7/js/noty/themes/default.js"></script>
    <script type="text/javascript" src="<?= $_ROOT_URL ?>/media/noty_defaults~v<?=$config->scripts_version?>.js"></script>
    
    <!-- Core functions and styles -->
    <link rel="stylesheet" type="text/css" href="<?= $_ROOT_URL ?>/media/styles~v<?=$config->scripts_version?>.css">
    <? if($account->_is_admin): ?><link rel="stylesheet" type="text/css" href="<?= $_ROOT_URL ?>/media/admin~v<?=$config->scripts_version?>.css"><? endif; ?>
    <script type="text/javascript"          src="<?= $_ROOT_URL ?>/media/functions~v<?=$config->scripts_version?>.js"></script>
    <script type="text/javascript"          src="<?= $_ROOT_URL ?>/media/notification_functions~v<?=$config->scripts_version?>.js"></script>
    
    <!-- This template -->
    <link rel="stylesheet" type="text/css" href="<?= $template->url ?>/media/styles~v<?=$config->scripts_version?>.css">
    
    <!-- Per module loads -->
    <?
    foreach($modules as $this_module)
        if( ! empty($this_module->template_includes->html_head) )
            include "{$this_module->abspath}/contents/{$this_module->template_includes->html_head}";
    ?>
</head>
<body data-orientation="landscape" data-viewport-class="0" class="admin">

<div id="body_wrapper">
    
    <?
    foreach($modules as $this_module)
        if( ! empty($this_module->template_includes->pre_header) )
            include "{$this_module->abspath}/contents/{$this_module->template_includes->pre_header}";
    ?>
    
    <div id="header">
        
        <div class="header_top">
            <?
            if($account->_is_admin) include "{$template->abspath}/segments/admin_menu.inc";
            
            foreach($modules as $this_module)
                if( ! empty($this_module->template_includes->header_top) )
                    include "{$this_module->abspath}/contents/{$this_module->template_includes->header_top}";
            ?>
        </div>
        
        <div class="menu clearfix">
            
            <span id="main_menu_trigger" class="main_menu_item" onclick="toggle_main_menu_items()">
                <span class="fa fa-bars fa-fw"></span>
            </span>
            
            <a class="main_menu_item pull-left" href="<?= $_ROOT_URL ?>">
                <span class="fa fa-home fa-fw"></span>
            </a>
            
            <?
            foreach($modules as $this_module)
                if( ! empty($this_module->template_includes->header_menu) )
                    include "{$this_module->abspath}/contents/{$this_module->template_includes->header_menu}";
            
            echo $template->build_menu_items("priority");
            ?>
        
        </div>
        
        <div class="header_bottom">
            <?
            foreach($modules as $this_module)
                if( ! empty($this_module->template_includes->header_bottom) )
                    include "{$this_module->abspath}/contents/{$this_module->template_includes->header_bottom}";
            ?>
        </div>
    
    </div><!-- /#header -->
    
    <div id="content">
        <? include "{$current_module->abspath}/{$template->page_contents_include}"; ?>
    </div><!-- /#content -->
    
    <?
    foreach($modules as $this_module)
        if( ! empty($this_module->template_includes->post_footer) )
            include "{$this_module->abspath}/contents/{$this_module->template_includes->post_footer}";
    ?>
    
</div><!-- /#body_wrapper -->

<? internals::render(); ?>

</body>
</html>
