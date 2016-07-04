<?
/**
 * Base template - Embedded layout
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
    
    <!-- Core functions and styles -->
    <link rel="stylesheet" type="text/css" href="<?= $_ROOT_URL ?>/media/styles~v<?=$config->scripts_version?>.css">
    <? if($account->_is_admin): ?><link rel="stylesheet" type="text/css" href="<?= $_ROOT_URL ?>/media/admin~v<?=$config->scripts_version?>.css"><? endif; ?>
    
    <!-- This template -->
    <link rel="stylesheet" type="text/css" href="<?= $template->url ?>/media/styles~v<?=$config->scripts_version?>.css">
</head>
<body data-orientation="landscape" data-viewport-class="0" class="popup">

<div id="body_wrapper">
    
    <div id="content">
        <? include "{$current_module->abspath}/contents/{$template->page_contents_include}"; ?>
    </div><!-- /#content -->
    
</div><!-- /#body_wrapper -->

<? internals::render(); ?>

</body>
</html>
