<?
/**
 * Base template - Home
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
<body data-orientation="landscape" data-viewport-class="0" class="home">

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
                <span class="fa fa-bars"></span>
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
        <?
        foreach($modules as $this_module)
            if( ! empty($this_module->template_includes->content_top) )
                include "{$this_module->abspath}/contents/{$this_module->template_includes->content_top}";
    
        foreach($modules as $this_module)
            if( ! empty($this_module->template_includes->home_content) )
                include "{$this_module->abspath}/contents/{$this_module->template_includes->home_content}";
        
        foreach($modules as $this_module)
            if( ! empty($this_module->template_includes->content_bottom) )
                include "{$this_module->abspath}/contents/{$this_module->template_includes->content_bottom}";
        ?>
        
        <p>
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla sed odio sodales, luctus ex et, vulputate neque. Cras cursus consequat sem, a ultrices justo iaculis et. Ut elementum lacus augue, nec laoreet mi scelerisque vel. Sed in eros velit. Quisque feugiat enim sit amet vestibulum laoreet. Praesent semper pretium erat. Fusce non varius libero, at laoreet urna. Fusce neque neque, vulputate scelerisque nisi eu, laoreet bibendum massa. Praesent neque quam, aliquet eu massa ut, tristique faucibus justo.
        </p>
        <p>
            Suspendisse vulputate congue tellus, eu tempor diam. Quisque id lorem egestas orci volutpat dapibus non id ipsum. Duis eget justo posuere, elementum urna nec, consectetur lacus. Donec non quam at lacus venenatis dictum. Aliquam vel magna scelerisque erat suscipit pulvinar. Sed tempor nisl quis tellus porta consectetur. Suspendisse potenti. Praesent dui arcu, blandit nec placerat a, fringilla in orci. Etiam sed dui id sapien mollis laoreet at eu dui. Curabitur in orci eu nisi iaculis dictum. Pellentesque non dignissim erat, et hendrerit orci.
        </p>
        <p>
            In eleifend eros quis ultrices ullamcorper. Suspendisse eu elementum risus. Praesent quis mollis quam. Aenean mattis laoreet erat finibus congue. Nulla tristique sollicitudin tincidunt. Quisque fermentum pellentesque viverra. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Integer sodales rhoncus justo, in egestas ligula maximus at. Sed facilisis tortor vel dui ultrices facilisis. Proin cursus nulla vel risus feugiat dictum. Donec pellentesque mauris urna. Integer quis laoreet nunc. Duis ultricies lorem non ipsum lobortis, et ultrices eros vestibulum. Vivamus dictum sem sit amet tristique feugiat.
        </p>
        <p>
            Fusce lacus lorem, ornare hendrerit feugiat ac, cursus a odio. Vivamus faucibus ante at sollicitudin maximus. Nulla eget nisi convallis, laoreet tellus ut, aliquet ante. Donec eleifend arcu magna, non gravida magna congue sed. Sed facilisis et leo in luctus. Nullam interdum urna non orci egestas lobortis. Ut diam lacus, molestie varius libero in, posuere blandit dolor. Vestibulum leo erat, condimentum non lobortis sit amet, semper a justo. Integer sit amet imperdiet erat, eu tristique ante. Proin vestibulum purus id ex scelerisque lobortis. Sed egestas, lorem vitae imperdiet feugiat, risus ante condimentum sem, ut dictum ante odio varius eros.
        </p>
        <p>
            Suspendisse potenti. Sed dapibus ut enim sit amet imperdiet. Donec tempus molestie ligula ac tincidunt. Ut erat magna, efficitur eu arcu in, faucibus euismod est. Etiam accumsan dictum leo quis tristique. Suspendisse quis porttitor diam. Morbi quam neque, ornare quis tortor vitae, suscipit placerat nulla. Curabitur sit amet tempus ipsum. In non nibh eu quam tempor rutrum. Morbi venenatis metus orci. Integer vel mi a purus fringilla pharetra vitae tempus mauris. Pellentesque ut urna scelerisque, ullamcorper nunc eu, sagittis neque.
        </p>
    </div><!-- /#content -->
    
    <?
    foreach($modules as $this_module)
        if( ! empty($this_module->template_includes->pre_footer) )
            include "{$this_module->abspath}/contents/{$this_module->template_includes->pre_footer}";
    ?>
    
    <div id="footer">
        <?
        foreach($modules as $this_module)
            if( ! empty($this_module->template_includes->footer_top) )
                include "{$this_module->abspath}/contents/{$this_module->template_includes->footer_top}";
        ?>
        
        <div class="footer_contents">
            
            <div align="center">
                <?= $settings->get("engine.website_name") ?> v<?= $config->engine_version ?>
            </div>
            
        </div>
        
        <?
        foreach($modules as $this_module)
            if( ! empty($this_module->template_includes->footer_bottom) )
                include "{$this_module->abspath}/contents/{$this_module->template_includes->footer_bottom}";
        ?>
        
    </div><!-- /#footer -->
    
    <?
    foreach($modules as $this_module)
        if( ! empty($this_module->template_includes->post_footer) )
            include "{$this_module->abspath}/contents/{$this_module->template_includes->post_footer}";
    ?>
    
</div><!-- /#body_wrapper -->

<?
foreach($modules as $this_module)
    if( ! empty($this_module->template_includes->pre_eof) )
        include "{$this_module->abspath}/contents/{$this_module->template_includes->pre_eof}";

internals::render();
?>

</body>
</html>
<?
foreach($modules as $this_module)
    if( ! empty($this_module->template_includes->post_rendering) )
        include "{$this_module->abspath}/contents/{$this_module->template_includes->post_rendering}";
?>
