<?php
/**
 * @var array  $errors
 * @var array  $messages
 * @var string $_ROOT_URL
 */

# Grouping
$var_groups = array();
$query = "
    select * from settings
    where name not like 'modules:%.installed'
    and   name not like 'modules:%.enabled'
    and   name not like 'engine.admins'
    order by name asc
";
$res = $database->query($query);
while($row = $database->fetch_object($res))
{
    preg_match('/^(.*)\.(.*)$/', $row->name, $matches);
    $var_groups[($matches[1])][($matches[2])]
        = (object) array(
        "name"  => $row->name,
        "value" => $row->value,
    );
}
?>

<!-- Settings styles -->
<style type="text/css">
    @media all and (min-width: 700px)
    {
        .settings_group {
            -webkit-column-count: 2;
            -moz-column-count:    2;
            column-count:         2;
        }
        
        .settings_group section {
            display: inline-block; width: 100%;
        }
    }
</style>

<!-- Expandible Text Area -->
<script type="text/javascript" src="<?=$_ROOT_URL?>/lib/jquery.exptextarea.js"></script>
<script type="text/javascript"> $(document).ready(function() { $('.expandible_textarea').expandingTextArea(); }); </script>

<script type="text/javascript">
    var active_tab = parseInt('<?= empty($_REQUEST["tab"]) ? "0" : $_REQUEST["tab"] ?>');
    
    $(document).ready(function()
    {
        $('#tabs').tabs({
            active:   active_tab,
            activate: function(event, ui) { active_tab = ui.newTab.index(); console.log(active_tab); }
        });
    });
</script>

<h1 style="margin-top: 0;"><?= $current_module->language->admin->record_nav->page_title ?></h1>

<? if( count(array_merge($errors, $messages)) > 0 ): ?>
    <div class="framed_content state_ok">
        <? if( count($errors) > 0 ): ?>
            <? foreach($errors as $item): ?>
                <span class="fa fa-warning"></span>
                <?= $item ?><br>
            <? endforeach; ?>
        <? endif; ?>
        <? if( count($messages) > 0 ): ?>
            <? foreach($messages as $item): ?>
                <span class="fa fa-info-circle"></span>
                <?= $item ?><br>
            <? endforeach; ?>
        <? endif; ?>
    </div>
<? endif; ?>

<div id="tabs" class="framed_content">
    <ul>
        <? foreach($var_groups as $group_name => $group_data): ?>
            <li><a href="#g<?= md5($group_name) ?>"><?= ucwords($group_name) ?></a></li>
        <? endforeach; ?>
    </ul>
    
    <? foreach($var_groups as $group_name => $group_data): ?>
        
        <div id="g<?= md5($group_name) ?>" class="settings_group">
            
            <form name="" method="post" action="<?= $_SERVER["PHP_SELF"] ?>?wasuuup=<?=md5(mt_rand(1,65535))?>" onsubmit="$(this).find('input[name=tab]').val( active_tab )">
                
                <input type="hidden" name="mode" value="save_group">
                <input type="hidden" name="tab" value="<?= empty($_REQUEST["tab"]) ? "0" : $_REQUEST["tab"] ?>">
                
                <? foreach($group_data as $key => $data): ?>
                    
                    <section class="setting_item">
                        <h3><?= ucwords(str_replace("_", " ", $key)) ?></h3>
                        <div class="framed_content">
                            <textarea class="expandible_textarea" name="names[<?=$data->name?>]"><?= htmlspecialchars($data->value) ?></textarea>
                        </div>
                    </section>
                    
                <? endforeach; ?>
                
                <div align="center">
                    <button type="submit" style="padding-top: 5px; padding-bottom: 5px;">
                        <span class="fa fa-save"></span>
                        <?= $current_module->language->admin->record_nav->save_group ?>
                    </button>
                </div>
                
            </form>
            
        </div><!-- /#g<?= md5($group_name) ?> -->
        
    <? endforeach; ?>
    
</div><!-- /#tabs -->
