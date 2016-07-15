
function toggle_left_sidebar_items()
{
    var visible        = $('#left_sidebar').is(':visible');
    var bar_visibility = visible ? 'false' : 'true';
    
    $('body').attr('data-left-sidebar-visible', bar_visibility);
    $('#left_sidebar_trigger').toggle();
}
