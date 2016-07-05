<?
/**
 * Base template - Embeddable stand-alone multi purpose
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var string $_ROOT_URL
 */

use hng2_tools\internals;

if( ! isset($_ROOT_URL) ) $_ROOT_URL = ".";

header("Content-Type: text/html; charset=utf-8"); ?>

<div id="embedded_content">
    <? include "{$current_module->abspath}/{$template->page_contents_include}"; ?>
</div>

<? internals::render(__FILE__); ?>
