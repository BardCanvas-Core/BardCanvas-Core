/**
 * Notification functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var {string} $_FULL_ROOT_PATH
 */

var notification_getter_heartbeat = 5000;

var notifications_sent = [];
var getting_notifications = false;
var notification_getter_hooks = [];
var notification_params_hooks = [];
var notification_instance_active = false;

function prepare_notifications_hooks()
{
    var $hooking_scripts = $('script[data-hooks-to="notifications-getter"]');
    
    notification_getter_hooks   = [];
    if( $hooking_scripts.length > 0 )
    {
        $hooking_scripts.each(function()
        {
            var function_name = $(this).attr('data-notifications-getter-hooking-function');
            eval('notification_getter_hooks[notification_getter_hooks.length] = ' + function_name + '()');
            //console.log(notification_hooks);
        });
    }
    
    notification_params_hooks = [];
    $hooking_scripts = $('script[data-hooks-to="notifications-params-preparer"]');
    if( $hooking_scripts.length > 0 )
    {
        $hooking_scripts.each(function()
        {
            var function_name = $(this).attr('data-notifications-params-preparer-hooking-function');
            eval('notification_params_hooks[notification_params_hooks.length] = ' + function_name + '()');
            //console.log(notification_hooks);
        });
    }
}

function get_notifications()
{
    if( typeof $_NOTIFICATIONS_SYSTEM_DISABLED !== 'undefined' )
        if( $_NOTIFICATIONS_SYSTEM_DISABLED ) return;
    
    if( $_CURRENT_USER_ID_ACCOUNT == '' ) return;
    if( getting_notifications ) return;
    
    getting_notifications = true;
    
    var url = $_FULL_ROOT_PATH + '/scripts/get_notifications.php';
    var params = {
        'wasuuup': wasuuup()
    };
    
    if( notification_params_hooks.length > 0 )
        for(var x in notification_params_hooks)
            params = notification_params_hooks[x]( params );
    
    $.getJSON(url, params, function(data)
    {
        if( typeof data == 'undefined' )               return;
        if( data.length == 0 )                         return;
        if( typeof data.notifications == 'undefined' ) return;
        
        var notifications = data.notifications;
        for(var i in notifications)
        {
            if( typeof notifications_sent[i] == 'undefined' )
            {
                throw_notification(notifications[i].message, notifications[i].message_type);
                notifications_sent[i] = true;
            }
        }
        // if( typeof i != 'undefined' ) $.getJSON(url + '&last_read=' + i);
        getting_notifications = false;
        
        if( notification_getter_hooks.length == 0 ) return;
        
        for(var y in notification_getter_hooks)
            notification_getter_hooks[y]( data );
    });
}

function notification_clicked( $noty_object )
{
    var message_archive = $noty_object.$message.find('span[data-message-archive]').attr('data-message-archive');
    
    var url = $_FULL_ROOT_PATH
            + '/scripts/delete_notification.php'
            + '?identifier=' + encodeURI(message_archive)
            + '&wasuuup='    + wasuuup()
        ;
    
    $.get(url, function(response)
    {
        if( response != 'OK' ) console.log(response);
        
        check_notifications_killer();
    });
}

var notification_getter_interval = null;
function start_notifications_getter()
{
    if( notification_instance_active ) return;
    
    notification_instance_active = true;
    if( $_CURRENT_USER_ID_ACCOUNT == '' ) { stop_notifications_getter(); return; }
    if( notification_getter_interval ) stop_notifications_getter();
    notification_getter_interval = setInterval('get_notifications()', notification_getter_heartbeat);
}

function stop_notifications_getter()
{
    clearInterval(notification_getter_interval);
    notification_instance_active = false;
}

/**
 * Throw a notification
 *
 * @param {string} message
 * @param {string} message_type alert, success, error, warning, information, confirm
 * @param {int} autoclose_timeout milliseconds
 */
function throw_notification(message, message_type, autoclose_timeout)
{
    if( typeof message_type == 'undefined' ) message_type = $.noty.defaults.type;
    if( typeof autoclose_timeout === 'undefined' ) autoclose_timeout = false;
    show_notifications_killer();
    noty({text: message, type: message_type, timeout: autoclose_timeout});
    play_notification_sound();
}

function show_notifications_killer()
{
    //noinspection JSJQueryEfficiency
    var $killer = $('#notifications_killer');
    
    if( $killer.length == 0 )
        $('#noty_bottomLeft_layout_container').prepend(sprintf(
            '<li id="notifications_killer" onclick="kill_all_notifications()">%s</li>',
            notifications_killer_caption
        ));
}

function hide_notifications_killer()
{
    $('#notifications_killer').remove();
}

function check_notifications_killer()
{
    if( $('.noty_message').length == 0 ) hide_notifications_killer();
}

function kill_all_notifications()
{
    var $elements = $('.noty_message');
    if( $elements.length == 0 ) return;
    
    var ids = [];
    $elements.each(function()
    {
        var $span = $(this).find('span[data-message-archive]');
        if( $span.length == 0 ) return;
        
        ids[ids.length] = $span.attr('data-message-archive');
    });
    
    if( ids.length > 0 )
    {
        for(var i in ids)
        {
            var url = $_FULL_ROOT_PATH
                + '/scripts/delete_notification.php'
                + '?identifier=' + encodeURI(ids[i])
                + '&wasuuup='    + wasuuup()
            ;
            
            $.get(url, function(response) { if( response != 'OK' ) console.log(response); });
        }
    }
    
    $.noty.closeAll();
    hide_notifications_killer();
}

ion.sound({
    sounds: [
        { name: "glass"               }, // message
        { name: "button_click"        }, // message2
        { name: "water_droplet_3"     }, // message3
        { name: "bell_ring"           }, // incoming
        { name: "water_droplet"       }, // warning
        { name: "computer_error"      }, // error
        { name: "borealis_notify"     }, // notify
        { name: "borealis_question1"  }, // question1
        { name: "borealis_question2"  }, // question2
        { name: "borealis_send"       }, // send
        { name: "button_tiny"         }  // default
    ],
    volume:  1,
    path:    $_FULL_ROOT_PATH + "/lib/ion.sound-3.0.7/sounds/",
    preload: true
});

/**
 * Plays a notification sound
 * 
 * @param name default, message, message2, message3, incoming, notify, question1, question2, send, warning, error
 */
function play_notification_sound(name)
{
    if( typeof name == 'undefined' ) name = 'default';
    if( $_SILENT_NOTIFICATIONS ) return;
    
    switch(name)
    {
        case 'message':   ion.sound.play("glass");              break;
        case 'message2':  ion.sound.play("button_click");       break;
        case 'message3':  ion.sound.play("water_droplet_3");    break;
        case 'incoming':  ion.sound.play("bell_ring");          break;
        case 'warning':   ion.sound.play("water_droplet");      break;
        case 'error':     ion.sound.play("computer_error");     break;
        case 'notify':    ion.sound.play("borealis_notify");    break;
        case 'question1': ion.sound.play("borealis_question1"); break;
        case 'question2': ion.sound.play("borealis_question2"); break;
        case 'send':      ion.sound.play("borealis_send");      break;
        default:          ion.sound.play("button_tiny");        break;
    }
}

$(document).ready(function()
{
    prepare_notifications_hooks();
    get_notifications();
    start_notifications_getter();
});
