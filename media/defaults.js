/**
 * Default variables - Overridable by the template
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var {string} $_FULL_ROOT_PATH
 */

var blockUI_default_params  = { css: { border: '0', backgroundColor: 'transparent' },
    message: '<img class="ajax_spinner" src="' + $_FULL_ROOT_PATH + '/media/spinner_128x128_v2.gif" width="128" height="128" border="0">' };

var blockUI_medium_params   = { css: { border: '0', backgroundColor: 'transparent' },
    message: '<img class="ajax_spinner" src="' + $_FULL_ROOT_PATH + '/media/spinner_64x64.gif" width="64" height="64" border="0">' };

var blockUI_smaller_params  = { css: { border: '0', backgroundColor: 'transparent' },
    message: '<img class="ajax_spinner" src="' + $_FULL_ROOT_PATH + '/media/spinner_32x32.gif" width="32" height="32" border="0">' };

var blockUI_smallest_params = { css: { border: '0', backgroundColor: 'transparent' },
    message: '<img class="ajax_spinner" src="' + $_FULL_ROOT_PATH + '/media/spinner_16x16.gif" width="16" height="16" border="0">' };

var blockUI_big_progress_params = { css: { border: '0', backgroundColor: 'transparent' },
    message: '<div id="blockui_progress_container" style="width: 240px;">' +
             '<div class="numbers" style="width: 240px; font-family: arial, helvetica, sans-serif; font-size: 12px; line-height: 12px; margin-bottom: 2px; text-align: center; color: white;">? / ? KB</div>' +
             '<div class="progress" style="position:relative; width:240px; border: 1px solid black; margin: 0; padding: 0; border-radius: 3px; background-color: silver; text-align: left;">' +
               '<div class="bar" style="background-color: deepskyblue; width:0; height: 20px; border-radius: 3px;"></div>' +
               '<div class="percent" style="position: absolute; top: 0; display: block; width: 240px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 18px; text-align: center;">0%</div>' +
             '</div>' +
             '</div>' };

var blockUI_small_progress_params = { css: { border: '0', backgroundColor: 'transparent' },
    message: '<div id="blockui_progress_container" style="width: 40px;">' +
             '<div class="progress" style="position:relative; width:40px; border: 1px solid black; margin: 0; padding: 0; border-radius: 3px; background-color: silver; text-align: left;">' +
               '<div class="bar" style="background-color: deepskyblue; width:0; height: 20px; border-radius: 3px;"></div>' +
               '<div class="percent" style="position: absolute; top: 0; display: block; width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 18px; text-align: center;">0%</div>' +
             '</div>' +
             '</div>' };
