/**
 * Overridable noty defaults
 * IMPORTANT: These must be loaded AFTER noty!
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var {string} $_FULL_ROOT_PATH
 */

$.noty.defaults = {
    layout: 'bottomLeft',
    theme: 'defaultTheme',
    type: 'information',
    text: '', // can be html or string
    dismissQueue: true, // If you want to use queue feature set this true
    template: '<div class="noty_message">'
              + '<span class="fa fa-times fa-fw" style="cursor: pointer; position: absolute; top: 4px; right: 4px; font-size: 12px; width: 12px; height: 12px;"></span>'
              + '<span class="noty_text"></span>' 
              // + '<span class="noty_close"></span>'
              + '</div>',
    animation: {
        open:  { height: 'toggle' },
        close: { height: 'toggle' },
        easing: 'swing',
        speed: 100 // opening & closing animation speed
    },
    timeout: false, // delay for closing event. Set false for sticky notifications
    force: false, // adds notification to the beginning of queue when set to true
    modal: false,
    // maxVisible: 10, // you can set max visible notification for dismissQueue true option,
    killer: false, // for close all notifications before show
    closeWith: ['click'], // ['click', 'button', 'hover']
    callback: {
        onCloseClick: function() { notification_clicked(this); }
    },
    buttons: false // an array of buttons
};
