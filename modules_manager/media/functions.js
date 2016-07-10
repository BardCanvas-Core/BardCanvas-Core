
function do_module(action, module_name)
{
    location.href = $_PHP_SELF + '?install_action=' + action
        + '&do_module_name=' + module_name
        + '&tab=' + active_tab
        + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
}

function reload_self()
{
    location.href = $_PHP_SELF + '?tab=' + active_tab + '&wasuuup=' + (Math.random() * 1000000000000000);
}

function purge_modules_cache()
{
    var url = $_FULL_ROOT_PATH + '/modules_manager/purge_cache.php?wasuuup=' + parseInt(Math.random() * 1000000000000000);
    $.blockUI(blockUI_default_params);
    $.get(url, function(response)
    {
        if( response != 'OK' )
        {
            $.unblockUI();
            alert( response );
            
            return;
        }
        
        reload_self();
    });
}

function change_caching_status(new_status)
{
    var url = $_FULL_ROOT_PATH + '/modules_manager/change_caching_status.php';
    var params = {
        'new_status': new_status,
        'wasuuup':    parseInt(Math.random() * 1000000000000000)
    };
    $.blockUI(blockUI_default_params);
    $.get(url, params, function(response)
    {
        if( response != 'OK' )
        {
            $.unblockUI();
            alert( response );
            
            return;
        }
        
        reload_self();
    });
}

$(document).ready(function()
{
    $('#tabs').tabs({
        active:   active_tab,
        activate: function(event, ui) { active_tab = ui.newTab.index(); console.log(active_tab); }
    });
});
