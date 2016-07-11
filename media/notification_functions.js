/**
 * Notification functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var {string} $_FULL_ROOT_PATH
 */

var notification_getter_heartbeat = 3000;

var notifications_sent = [];
var getting_notifications = false;
var notification_getter_hooks = [];
var notification_params_hooks = [];

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
    if( $_CURRENT_USER_ID_ACCOUNT == '' ) return;
    if( getting_notifications ) return;
    
    getting_notifications = true;
    
    var url = $_FULL_ROOT_PATH + '/scripts/get_notifications.php';
    var params = {
        'wasuuup': parseInt(Math.random() * 1000000000000000)
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
            + '&wasuuup='    + parseInt(Math.random() * 1000000000000000)
        ;
    
    $.get(url, function(response)
    {
        if( response != 'OK' ) alert(response);
    });
}

var notification_getter_interval = null;
function start_notifications_getter()
{
    if( $_CURRENT_USER_ID_ACCOUNT == '' ) { stop_notifications_getter(); return; }
    if( notification_getter_interval ) stop_notifications_getter();
    notification_getter_interval = setInterval('get_notifications()', notification_getter_heartbeat);
}

function stop_notifications_getter()
{
    clearInterval(notification_getter_interval);
}

/**
 * Throw a notification
 *
 * @param {string} message
 * @param {string} message_type alert, success, error, warning, information, confirm
 */
function throw_notification(message, message_type)
{
    if( typeof message_type == 'undefined' ) message_type = $.noty.defaults.type;
    noty({text: message, type: message_type});
}

$(document).ready(function()
{
    prepare_notifications_hooks();
    get_notifications();
    start_notifications_getter();
});
