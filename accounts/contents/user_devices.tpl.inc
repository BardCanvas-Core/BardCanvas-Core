<?php
/**
 * @var int $order
 */

use hng2_base\device;
?>

<style type="text/css">
    .nav_table th { text-align: left; }
</style>

<h1><?= $current_module->language->devices_nav->page_title ?></h1>

<?
    $query = "
        select * from account_devices where
        id_account = '$account->id_account' and
        state <> 'deleted'
        order by device_label asc
    ";
    $res = $database->query($query);
?>

<?
    # [+] Header presets
    #===================
    {
        $table_header = array(
            (object) array(
                "content"   => $current_module->language->devices_nav->column_titles->login_date,
                "xalign"    => "center",
                "xnowrap"   => true,
                "xclass"    => "",
                "sort_asc"  => (object) array( "enabled" => false, "order" =>  0, "alt" => "" ),
                "sort_desc" => (object) array( "enabled" => false, "order" =>  0, "alt" => "" )
            ),
            (object) array(
                "content"   => $current_module->language->devices_nav->column_titles->ip,
                "xalign"    => "center",
                "xnowrap"   => true,
                "xclass"    => "",
                "sort_asc"  => (object) array( "enabled" => false, "order" =>  0, "alt" => "" ),
                "sort_desc" => (object) array( "enabled" => false, "order" =>  0, "alt" => "" )
            ),
            (object) array(
                "content"   => $current_module->language->devices_nav->column_titles->hostname,
                "xalign"    => "center",
                "xnowrap"   => true,
                "xclass"    => "",
                "sort_asc"  => (object) array( "enabled" => false, "order" =>  0, "alt" => "" ),
                "sort_desc" => (object) array( "enabled" => false, "order" =>  0, "alt" => "" )
            ),
            (object) array(
                "content"   => $current_module->language->devices_nav->column_titles->location,
                "xalign"    => "center",
                "xwidth"    => "100%",
                "xclass"    => "",
                "sort_asc"  => (object) array( "enabled" => false, "order" =>  0, "alt" => "" ),
                "sort_desc" => (object) array( "enabled" => false, "order" =>  0, "alt" => "" )
            ),
        );
    }
    #===================
    # [-] Header presets
?>

<? $current_device = new device($account->id_account); ?>
<? while( $this_device = $database->fetch_object($res) ): ?>
    
    <?
        /** @var device $this_device */
        switch($this_device->state)
        {
            case "enabled":      $class = "";              break;
            case "disabled":     $class = "state_warning"; break;
            case "unregistered": $class = "state_ko";      break;
        } // end switch
        if( $this_device->id_device == $current_device->id_device ) $class = "state_active";
    ?>
    
    <div class="framed_content <?=$class?>">
        
        <h2 style="<? if($this_device->state != "enabled") echo "margin-bottom: 0;"; ?>">
            <span style="float: right;">
                <button onclick="change_device_label('<?=$this_device->id_device?>', '<?= addslashes($this_device->device_label)?>');">
                    <span class="fa fa-pencil"></span>
                    <?= $current_module->language->devices_nav->buttons->change_label ?>
                </button>
                
                <? if($this_device->state == "enabled"): ?>
                    
                    <button <?= $current_device->id_device == $this_device->id_device ? "disabled" : "" ?>
                            onclick="change_device_state('disabled', '<?=$this_device->id_device?>')">
                        <span class="fa fa-lock"></span>
                        <?= $current_module->language->devices_nav->buttons->disable ?>
                    </button>
                    
                <? elseif($this_device->state == "disabled"): ?>
                    
                    <button <?= $current_device->id_device == $this_device->id_device ? "disabled" : "" ?>
                            onclick="change_device_state('enabled', '<?=$this_device->id_device?>')">
                        <span class="fa fa-unlock"></span>
                        <?= $current_module->language->devices_nav->buttons->enable ?>
                    </button>
                    
                <? endif; ?>
                
                <button <?= $current_device->id_device == $this_device->id_device ? "disabled" : "" ?>
                        onclick="change_device_state('deleted', '<?=$this_device->id_device?>')">
                    <span class="fa fa-trash-o"></span>
                    <?= $current_module->language->devices_nav->buttons->delete ?>
                </button>
            </span>
            <span class="framed_content" style="font-weight: normal; padding: 0 5px; "><?= $current_module->language->devices_nav->state_captions->{$this_device->state} ?></span>
            <?= empty($this_device->device_label) ? $current_module->language->devices_nav->no_label : $this_device->device_label ?>
            <span style="font-size: 12pt; font-weight: normal; font-style: italic;">
                <?= $this_device->device_header ?>
            </span>
        </h2>
        
        <? if( $this_device->state == "enabled" ): ?>
            <?
                $query2 = "select * from account_logins where id_account = '$account->id_account' and id_device = '$this_device->id_device' order by login_date desc limit 10";
                $res2   = $database->query($query2);
            ?>
            <? if( $database->num_rows($res) == 0 ): ?>
                
                <div class="framed_content state_ko">
                    <span class="fa fa-info-circle"></span>
                    <?= $language->record_nav->no_records_found ?>
                </div>
                
            <? else: ?>
                
                <div class="table_wrapper">
                    <table class="nav_table evened">
                        <thead>
                            <tr>
                                <? foreach($table_header as $this_cell): ?>
                                    <th align="<?=$this_cell->xalign?>" <? if($this_cell->xnowrap == true) echo "nowrap "; ?> 
                                        <? if(!empty($this_cell->xwidth)) echo "width='$this_cell->xwidth' "; ?> 
                                        class="<?=$this_cell->xclass?>"><?
                                        if($this_cell->sort_asc->enabled == true) echo "<img 
                                            src='{$config->full_root_path}/media/icons/order_asc".($order==$this_cell->sort_asc->order? "_this" : "").".gif' 
                                            align='absmiddle' width='7' height='7'  style='cursor: pointer;'
                                            alt='".$this_cell->sort_asc->alt."' title='".$this_cell->sort_asc->alt."'
                                            onclick=\""."document.filter_form.order.value='".$this_cell->sort_asc->order."'; "."document.filter_form.submit();\">&nbsp;";
                                    ?><?=$this_cell->content?><?
                                        if($this_cell->sort_desc->enabled == true) echo "&nbsp;<img 
                                            src='{$config->full_root_path}/media/icons/order_desc".($order==$this_cell->sort_desc->order? "_this" : "").".gif' 
                                            align='absmiddle' width='7' height='7'  style='cursor: pointer;'
                                            alt='".$this_cell->sort_desc->alt."' title='".$this_cell->sort_desc->alt."'
                                            onclick=\""."document.filter_form.order.value='".$this_cell->sort_desc->order."'; "."document.filter_form.submit();\">";
                                    ?></th>
                                <? endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <? while ( $row = $database->fetch_object($res2) ) { ?>
                                
                                <tr>
                                    
                                    <td nowrap><?= $row->login_date ?></td>
                                    
                                    <td nowrap><?= $row->ip ?></td>
                                    
                                    <td nowrap><?= $row->hostname ?></td>
                                    
                                    <td width="100%"><?= $row->location ?></td>
                                    
                                </tr>
                                
                            <? } # end while ?>
                        </tbody>
                    </table>
                </div>
                
            <? endif; ?>
        <? endif; ?>
    </div>
    
<? endwhile; ?>

<div id="devices_nav_messages" style="display: none;">
    <span class="change_label"><?= $current_module->language->devices_nav->change_label ?></span>
    <span class="confirm_delete"><?= $current_module->language->devices_nav->confirm_delete ?></span>
</div>
