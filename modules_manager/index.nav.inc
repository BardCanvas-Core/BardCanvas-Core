<?
/**
 * @var array  $errors
 * @var array  $messages
 * @var module $this_module
 */

use hng2_base\module;

# Module re-scanning, including uninstalled ones
$_all_modules        = array();
$module_dirs_pattern = ABSPATH . "/*";
$available_modules   = glob($module_dirs_pattern, GLOB_ONLYDIR);
foreach($available_modules as $this_module_dir)
{
    $module_info_file = "$this_module_dir/module_info.xml";
    if( ! file_exists($module_info_file) ) continue;
    
    $module_keyname = basename($this_module_dir);
    $module = new module($module_info_file);
    $_all_modules[$module_keyname] = clone $module;
}

$module_groups = (object) array(
    "all"           => $_all_modules,
    "installed"     => array(),
    "not_installed" => array(),
    "enabled"       => array(),
    "disabled"      => array(),
);

foreach($_all_modules as $key => $this_module)
{
    if( $this_module->installed ) $module_groups->installed[$this_module->name]     = $this_module;
    else                          $module_groups->not_installed[$this_module->name] = $this_module;
    
    if( $this_module->enabled  )  $module_groups->enabled[$this_module->name]       = $this_module;
    else                          $module_groups->disabled[$this_module->name]      = $this_module;
    
    if( ! empty($this_module->group) ) $module_groups->{$this_module->group}[$this_module->name] = $this_module;
}
?>

<link rel="stylesheet" type="text/css" href="media/styles~v<?=$config->scripts_version?>.css">
<script type="text/javascript"          src="media/functions~v<?=$config->scripts_version?>.js"></script>
<script type="text/javascript">var active_tab = parseInt('<?= empty($_REQUEST["tab"]) ? "0" : $_REQUEST["tab"] ?>');</script>

<h1>
    <button class="pull-right" onclick="reload_self()">
        <span class="fa fa-refresh fa-fw"></span>
        <?=  $language->reload ?>
    </button>
    
    <?= $current_module->language->page_title ?>
    
    <? if( $settings->get("modules:modules_manager.disable_cache") == "true" ): ?>
        <button onclick="change_caching_status('enabled')">
            <span class="fa fa-toggle-on"></span>
            <?= $current_module->language->buttons->toggle_cache->enable ?>
        </button>
    <? else: ?>
        <button onclick="change_caching_status('disabled')">
            <span class="fa fa-toggle-off"></span>
            <?= $current_module->language->buttons->toggle_cache->disable ?>
        </button>
        <button onclick="purge_modules_cache()" title="<?= $current_module->language->buttons->purge_cache->title ?>">
            <span class="fa fa-refresh"></span>
            <?= $current_module->language->buttons->purge_cache->caption ?>
        </button>
    <? endif; ?>
</h1>

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
        <? foreach($module_groups as $key => $grouped_modules):
            if( empty($grouped_modules) ) continue; ?>
            <li><a href="#m_<?= $key ?>"><?= ucwords(str_replace("_", " ", $key))." (".count($grouped_modules).")" ?></a></li>
        <? endforeach; ?>
    </ul>
    
    <? foreach($module_groups as $key => $grouped_modules):
        if( empty($grouped_modules) ) continue; ?>
        
        <div id="m_<?= $key ?>">
            
            <? ksort($grouped_modules); ?>
            <? foreach($grouped_modules as $module_name => $this_module): ?>
                
                <div class="module_data_container framed_content <?= $this_module->enabled ? "state_ok" : "" ?>">
                    
                    <div class="action_buttons framed_content">
                        <? if( ! $this_module->installed ): ?>
                            <button <? if($this_module->working_flags->install != "true") echo "disabled"; ?> onclick="do_module('install', '<?=$this_module->name?>'); return false;">
                                <span class="fa fa-plus-circle"></span>
                                <?= $current_module->language->per_module_fields->action_buttons->install ?>
                            </button><br>
                            <button disabled>
                                <span class="fa fa-chevron-circle-up"></span>
                                <?= $current_module->language->per_module_fields->action_buttons->enable ?>
                            </button><br>
                        <? else: ?>
                            <button <? if($this_module->working_flags->uninstall != "true") echo "disabled"; ?> onclick="do_module('uninstall', '<?=$this_module->name?>')">
                                <span class="fa fa-minus-circle"></span>
                                <?= $current_module->language->per_module_fields->action_buttons->uninstall ?>
                            </button><br>
                            <? if( ! $this_module->enabled ): ?>
                                <button <? if($this_module->working_flags->enable != "true") echo "disabled"; ?> onclick="do_module('enable', '<?=$this_module->name?>')">
                                    <span class="fa fa-chevron-circle-up"></span>
                                    <?= $current_module->language->per_module_fields->action_buttons->enable ?>
                                </button><br>
                            <? else: ?>
                                <button <? if($this_module->working_flags->disable != "true") echo "disabled"; ?> onclick="do_module('disable', '<?=$this_module->name?>')">
                                    <span class="fa fa-chevron-circle-down"></span>
                                    <?= $current_module->language->per_module_fields->action_buttons->disable ?>
                                </button><br>
                            <? endif; ?>
                        <? endif; ?>
                    </div>
                    
                    <h3 style="margin-top: 0;"><?= ucwords(str_replace("_", " ", $module_name)) ?></h3>
                    
                    <p><i><?= $this_module->language->description ?></i></p>
                    
                    <table>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->version ?></th>
                            <td width="100%">
                                <?
                                echo $this_module->version . " ";
                                $info_xml = ABSPATH . "/{$this_module->name}/module_info.xml";
                                $last_update = date("Y-m-d H:i:s", filemtime($info_xml));
                                echo replace_escaped_vars( $current_module->language->per_module_fields->uploaded, '{$time}',
                                                           time_elapsed_string($last_update) )
                                ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->group ?></th>
                            <td width="100%"><?= $this_module->group ?></td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->name ?></th>
                            <td width="100%"><?= $module_name ?></td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->admin_only->caption ?></th>
                            <td width="100%"><?= $this_module->admin_only == "true"
                                               ? $current_module->language->per_module_fields->admin_only->generic
                                               : $current_module->language->per_module_fields->admin_only->only_admins ?></td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->working_flags->caption ?></th>
                            <td width="100%">
                                <div>
                                    <span class="fa fa-<?= $this_module->working_flags->install == "true" ? "check" : "warning" ?>"></span>
                                    <?= $current_module->language->per_module_fields->working_flags->install->{$this_module->working_flags->install}?>
                                </div>
                                <div>
                                    <span class="fa fa-<?= $this_module->working_flags->enable == "true" ? "check" : "times" ?>"></span>
                                    <?= $current_module->language->per_module_fields->working_flags->enable->{$this_module->working_flags->enable}?>
                                </div>
                                <div>
                                    <span class="fa fa-<?= $this_module->working_flags->disable == "true" ? "check" : "times" ?>"></span>
                                    <?= $current_module->language->per_module_fields->working_flags->disable->{$this_module->working_flags->disable}?>
                                </div>
                                <div>
                                    <span class="fa fa-<?= $this_module->working_flags->uninstall == "true" ? "check" : "times" ?>"></span>
                                    <?= $current_module->language->per_module_fields->working_flags->uninstall->{$this_module->working_flags->uninstall}?>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->menu_items->caption ?></th>
                            <td width="100%">
                                <? if( empty($this_module->menu_items->item) ): ?>
                                    <?= $current_module->language->per_module_fields->menu_items->no_items ?>
                                <? else: ?>
                                    <ul>
                                        <? foreach($this_module->menu_items->item as $this_menu_item): ?>
                                            <li>
                                                «<?= $this_module->language->admin->menu_items->{$this_menu_item->caption_language_var}  ?>»
                                            </li>
                                        <? endforeach; ?>
                                    </ul>
                                <? endif; ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->php_includes->caption ?></th>
                            <td width="100%">
                                <? if( ! is_object($this_module->php_includes) ): ?>
                                    <?= $current_module->language->per_module_fields->php_includes->no_items ?>
                                <? else: ?>
                                    <ul>
                                        <? foreach($this_module->php_includes as $key => $val): ?>
                                            <li>
                                                [<?= $key ?>] <?= $current_module->language->per_module_fields->php_includes->{$key}  ?>
                                            </li>
                                        <? endforeach; ?>
                                    </ul>
                                <? endif; ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->template_includes->caption ?></th>
                            <td width="100%">
                                <? if( ! is_object($this_module->template_includes) ): ?>
                                    <?= $current_module->language->per_module_fields->template_includes->no_items ?>
                                <? else: ?>
                                    <ul>
                                        <? foreach($this_module->template_includes as $key => $val): ?>
                                            <li>
                                                [<?= $key ?>] <?= $current_module->language->per_module_fields->template_includes->{$key}  ?>
                                            </li>
                                        <? endforeach; ?>
                                    </ul>
                                <? endif; ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->extends_to->caption ?></th>
                            <td width="100%">
                                <? if( !(is_array($this_module->extends_to) || is_object($this_module->extends_to)) ): ?>
                                    <?= $current_module->language->per_module_fields->extends_to->no_data ?>
                                <? else: ?>
                                    <ul>
                                        <? foreach($this_module->extends_to as $extended_module_key => $extended_module_case_data): ?>
                                            <li>
                                                <?= ucwords(str_replace("_", " ", $extended_module_key)) ?>:
                                                <ul>
                                                    <? foreach($extended_module_case_data as $extended_module_case => $extended_module_include): ?>
                                                        <li><?= ucwords(str_replace("_", " ", $extended_module_case)) ?>:
                                                            <?= ucwords(str_replace("_", " ", implode(", ", array_keys( (array) $extended_module_include )))) ?>.</li>
                                                    <? endforeach; ?>
                                                </ul>
                                            </li>
                                        <? endforeach; ?>
                                    </ul>
                                <? endif; ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th nowrap><?= $current_module->language->per_module_fields->extension_areas_info->caption ?></th>
                            <td width="100%">
                                <? if( empty($this_module->extension_areas_info) ): ?>
                                    <?= $current_module->language->per_module_fields->extension_areas_info->no_data ?>
                                <? else: ?>
                                    <? $info = str_replace("\n", "</li><li>", trim($this_module->extension_areas_info) ); ?>
                                    <ul>
                                        <li><?= $info ?></li>
                                    </ul>
                                <? endif; ?>
                            </td>
                        </tr>
                        
                    </table>
                    
                </div>
                
            <? endforeach; ?>
            
        </div><!-- /m_<?= $key ?> -->
        
    <? endforeach; ?>
    
</div><!-- /#tabs -->
