<?php
/**
 * Base template - Admin menu
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var string $_ROOT_URL
 */
?>

<script type="text/javascript">
    function toggle_admin_menu_items()
    {
        var $menu = $('#admin_menu');
        
        if( navigator.userAgent.indexOf('MSIE') > 0 || navigator.userAgent.indexOf('Safari') > 0 )
            $menu.find('.item').toggleClass('visible');
        else
            $menu.find('.item').toggleClass('visible');
        
        $('#admin_menu_trigger').toggleClass('open');
    }
    
    $(document).ready(function()
    {
        $(window).resize(function()
        {
            if( $('#admin_menu_trigger').hasClass('open') )
                toggle_admin_menu_items();
        });
    });
</script>

<div id="admin_menu">
    
    <span id="admin_menu_trigger" onclick="toggle_admin_menu_items()">
        <span class="fa fa-bars"></span>
    </span>
    
    <span class="admin_bar_title <? if($current_module) echo "current"; ?>">
        <span class="fa fa-info-circle"></span>
        <? if($current_module): ?>
            <?= ucwords(str_replace("_", " ", $current_module->name)) ?> v<?= $current_module->version ?>
        <? else: ?>
            <?= $language->admin->bar_title ?> <?= $config->engine_version ?>
        <? endif; ?>
    </span>
    
    <?
    $menu = "";
    foreach($modules as $module_name => $this_module)
    {
        if(is_array($this_module->menu_items) || is_object($this_module->menu_items))
        {
            foreach($this_module->menu_items->item as $this_menu_item)
            {
                $item_url    = trim($this_menu_item->url);
                $added_class = ($module_name == $current_module->name && stristr($_SERVER["PHP_SELF"], "/$module_name/$item_url") !== false) ? "current" : "";
                $url         = "$_ROOT_URL/$module_name/$item_url";
                $caption     = $this_module->language->admin->menu_items->{$this_menu_item->caption_language_var};
                $menu       .= "<a class='item $added_class' href='$url'>$caption</a>";
            }
        }
    }
    
    echo $menu;
    ?>

</div>
