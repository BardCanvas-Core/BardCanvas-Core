/**
 * Account front-end variables and functions
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

function open_register_dialog()
{
    $('#register_dialog').dialog('open');
}

function show_login_form()
{
    $('#login_dialog').dialog('open');
    return false;
}

function validate_login_form()
{
    var $login_form = $('#login_form');
    if( $login_form.find('input[name="user_name"]').val().trim() == "" ||
        $login_form.find('input[name="user_name"]').val().trim() == "" )
    {
        alert( $('#login_errors').find('.invalid_login_info').text().replace(/\n\s+/g, ' ') );
        return false;
    }
    
    return true;
}

function validate_register_form()
{
    var fields = ['display_name', 'user_name', 'email', 'password', 'password2', 'recaptcha_response_field'];
    var errors = [];
    
    var $register_form = $('#register_form');
    
    for( var i in fields )
        if( $register_form.find('input[name="' + fields[i] + '"]').val().trim() == '' )
            errors[errors.length] = $('#register_form_errors .' + fields[i]).text().trim().replace(/\s+/g, ' ');
    
    if( $register_form.find('select[name="country"] option:selected').val() == '' )
        errors[errors.length] = $('#register_form_errors').find('.country').text().trim().replace(/\s+/g, ' ');
    
    if( $register_form.find('input[name="email"]').val().trim() == $register_form.find('input[name="alt_email"]').val().trim() )
        errors[errors.length] = $('#register_form_errors').find('.mails_must_be_different').text().trim().replace(/\s+/g, ' ');
    
    if( $register_form.find('input[name="password"]').val().trim() != $register_form.find('input[name="password2"]').val().trim() )
        errors[errors.length] = $('#register_form_errors').find('.passwords_mismatch').text().trim().replace(/\s+/g, ' ');
    
    if( errors.length > 0 )
    {
        var msg = '• ' + errors.join('\n• ');
        alert( msg );
        return false;
    }
    
    return true;
}

function process_login_result()
{
    var result = $('#login_targetarea').text();
    
    if( result.indexOf('ERROR') < 0 )
    {
        // result > username > device_message > redirect
        parts = result.split('\t');
        
        // Let's show the info and logout button
        $('.login').hide();
        $('#loggedin_username').text(parts[1]);
        $('.logout').show();
        
        // Let's check if we need to alert about the new device
        if( parts[2] != "OK" )
        {
            $('#loggedin_icon').hide();
            $('#loggedin_icon_locked').show();
            alert( $('#device_messages .' + parts[2]).text().replace(/\n\s+/g, ' ') );
            return;
        }
        
        if( parts[3] != '' )
            location.href = $_FULL_ROOT_PATH + parts[3];
        return;
    }
    
    alert( $('#login_errors .' + result).text().replace(/\n\s+/g, ' ') );
}

function change_device_label(id_device, current_label)
{
    var message = $('#devices_nav_messages').find('.change_label').text();
    var label = prompt(message, current_label);
    if( label == null || label == current_label ) return;
    
    var url = $_FULL_ROOT_PATH + '/accounts/devices.php';
    var params = {
        'mode':         'set_label',
        'id_device':    id_device,
        'device_label': label
    };
    $.post(url, params, function(response)
    {
        if( response != 'OK' )
        {
            alert(response);
            return;
        } // end if
        
        top.location.href = url;
    });
}

function change_device_state(state, id_device)
{
    if( state == 'deleted' )
    {
        var message = $('#devices_nav_messages').find('.confirm_delete').text();
        if( ! confirm(message) ) return;
    } // end if
    
    var url = $_FULL_ROOT_PATH + '/accounts/devices.php';
    var params = {
        'mode':         'set_state',
        'id_device':    id_device,
        'state':        state
    };
    $.post(url, params, function(response)
    {
        if( response != 'OK' )
        {
            alert(response);
            return;
        } // end if
        
        top.location.href = url;
    });
}

function reset_password()
{
    $('#password_reset').dialog('open');
}

function prepare_reset_submission()
{
    $('#reset_form').block(blockUI_medium_params);
    $('#password_reset').closest('.ui-dialog').find('.ui-dialog-buttonpane button:first-child').button('disable');
}

function process_reset_result(responseText, statusText, xhr, $form)
{
    var $reset_form     = $('#reset_form');
    var $password_reset = $('#password_reset');

    if( responseText != 'OK' )
    {
        alert(responseText);
        $reset_form.unblock();
        $password_reset.closest('.ui-dialog').find('.ui-dialog-buttonpane button:first-child').button('enable');
        return;
    } // end if
    
    alert( $('#reset_messages').find('.OK').text() );
    
    $reset_form.unblock();
    $password_reset.closest('.ui-dialog').find('.ui-dialog-buttonpane button:first-child').button('enable');
    $reset_form[0].reset();
    $password_reset.dialog('close');
}

function set_engine_pref(key, value)
{
    var url = $_FULL_ROOT_PATH + '/accounts/scripts/set_engine_pref.php';
    var params = {'key': key, 'value': value};
    $.get(url, params, function(response)
    {
        if(response != 'OK') alert(response);
    });
}

$(document).ready(function()
{
    $('#register_dialog').dialog({
        autoOpen: false,
        modal:    true
    });
    
    var $reset_form = $('#reset_form');
    if( typeof $reset_form != 'undefined' )
    {
        var $password_reset = $('#password_reset');
        $password_reset.dialog({
            title:    $password_reset.attr('title'),
            autoOpen: false,
            modal:    true,
            close:    function() { $('#reset_form')[0].reset(); }
        });
        
        $reset_form.ajaxForm({
            target:        '#reset_targetarea',
            beforeSubmit:  prepare_reset_submission,
            success:       process_reset_result
        });
    }
    
    if( typeof $('#post_login') != 'undefined' )
    {
        var $login_dialog = $('#login_dialog');
        var $login_form = $('#login_form');
        $login_dialog.dialog({
            title:    $login_dialog.attr('title'),
            autoOpen: false,
            modal:    true,
            width:    320,
            close:    function() { $('#login_form')[0].reset(); }
        });
        
        $login_form.ajaxForm({
            target:        '#login_targetarea',
            beforeSubmit:  validate_login_form,
            success:       process_login_result
        });
    }
});
