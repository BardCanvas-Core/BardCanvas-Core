/**
 * Misc functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var {string} $_ROOT_URL
 */

//noinspection JSUnusedGlobalSymbols
/**
 * Get a document and show a dialog from it
 * 
 * @param {string} title
 * @param {string} url
 * @param {bool}   full_sized
 */
function show_ajax_dialog(title, url, full_sized)
{
    if( typeof full_sized == 'undefined' ) full_sized = false;
    
    $.get(url, function(response)
    {
        var html = '<div id="ajax_temporary_dialog" style="display: none;">' + response + '</div>';
        $('body').append(html);
        
        $('#ajax_temporary_dialog').dialog({
            modal:     true,
            title:     title,
            width:     full_sized ? $(window).width()  - 20 : 440,
            height:    full_sized ? $(window).height() - 20 : 'auto',
            maxHeight: $(window).height() - 20,
            close:     function() { $('#ajax_temporary_dialog').dialog('destroy').remove(); }
        });
    });
}

/**
 * Throw an alert dialog
 * 
 * @param {string} selector
 * @param {bool}   full_sized
 */
function show_discardable_dialog(selector, full_sized)
{
    if( typeof full_sized == 'undefined' ) full_sized = false;
    
    var title = $(selector).attr('title');
    if( typeof title == 'undefined' ) title = '';
    
    $(selector).dialog({
        modal:     true,
        title:     $(selector).attr('title'),
        width:     full_sized ? $(window).width()  - 20 : 440,
        height:    full_sized ? $(window).height() - 20 : 'auto',
        maxHeight: $(window).height() - 20,
        close:     function() { $(this).dialog('destroy'); }
    });
}

function set_body_metas()
{
    var width  = $(window).width();
    var height = $(window).height();
    
    if( height >= width ) $('body').attr('data-orientation', 'portrait');
    else                  $('body').attr('data-orientation', 'landscape');
    
         if(width >= 1900) $('body').attr('data-viewport-class', '1920');
    else if(width >= 1340) $('body').attr('data-viewport-class', '1360');
    else if(width >= 1260) $('body').attr('data-viewport-class', '1280');
    else if(width >= 1000) $('body').attr('data-viewport-class', '1024');
    else if(width >=  700) $('body').attr('data-viewport-class',  '720');
    else                   $('body').attr('data-viewport-class',  '480');
}

function toggle_main_menu_items()
{
    var $header = $('#header');
    if( navigator.userAgent.indexOf('MSIE') > 0 || navigator.userAgent.indexOf('Safari') > 0 )
    {
        $header.find('.main_menu_item').toggleClass('visible');
        $header.find('.special_menu_item').toggleClass('visible');
    }
    else
    {
        $header.find('.main_menu_item:not(.current)').toggleClass('visible');
        $header.find('.special_menu_item').toggleClass('visible');
    }
    
    $('#main_menu_trigger').toggleClass('open');
}

function prepare_submenus()
{
    $('.is_submenu_trigger').each(function()
    {
        var $this = $(this);
        
        if( $this.attr('data-already-processed') ) return;
        
        $this.click(function(event)
        {
            var $self = $(this);
            event.stopPropagation();
            toggle_dropdown_menu( $self );
        });
    });
}

function toggle_dropdown_menu($trigger)
{
    if( $trigger.length == 0 ) return;
    
    if( ! $trigger.hasClass('submenu_visible') ) hide_dropdown_menus();
    
    var menu_selector   = $trigger.attr('data-submenu');
    var $menu           = $(menu_selector);
    var offset          = $trigger.offset();
    var top             = offset.top + $trigger.height() + 10;
    var width           = $menu.width();
    var window_boundary = $(window).width();
    var left            = offset.left;
    if( (offset.left + width + 12) > window_boundary ) left = offset.left + $trigger.width() - width + 12;
    
    $trigger.toggleClass('submenu_visible');
    $menu.toggle().css('left', left + 'px').css('top',  top + 'px');
    $trigger.find('.menu_toggle > span').toggle();
}

function hide_dropdown_menus()
{
    $('.is_submenu_trigger.submenu_visible').each(function()
    {
        var $trigger = $(this);
        var menu     = $trigger.attr('data-submenu');
        $trigger.toggleClass('submenu_visible', false);
        $(menu).hide();
    });
}

function check_wrapped_tables()
{
    $('.table_wrapper').each(function()
    {
        if( $(this).find('.nav_table').width() > $(this).width() )
            $(this).addClass('scrolling');
        else
            $(this).removeClass('scrolling');
    });
}

function refresh_record_browser($target)
{
    var target_id = '#' + $target.attr('id');
    $target.find('form').ajaxForm({
        target:       target_id,
        beforeSubmit: function()
                      {
                          $target.block(blockUI_medium_params);
                      },
        success:      function()
                      {
                          $target.unblock();
                          refresh_record_browser($target);
                      }
    });
    
    check_wrapped_tables();
}

$(document).ready(function()
{
    set_body_metas();
    
    $(window).resize(function()
    {
        if( $('#main_menu_trigger').hasClass('open') ) toggle_main_menu_items();
        check_wrapped_tables();
        set_body_metas();        
    });
    
    check_wrapped_tables();
    prepare_submenus();
    
    $(window).click(function()
    {
        hide_dropdown_menus();
    });
    
    var $ajax_record_browsers = $('.ajax_record_browser');
    if( $ajax_record_browsers.length > 0 )
    {
        $ajax_record_browsers.each(function()
        {
            if( $(this).attr('data-no-autoload') ) return;
            
            var url = $(this).attr('data-src');
            $(this).load(url, function()
            {
                refresh_record_browser( $(this) );
            });
        });
    }
});
