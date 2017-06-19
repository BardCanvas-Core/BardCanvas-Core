/**
 * Misc functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var {string} $_FULL_ROOT_PATH
 */

$(document).ajaxError(function( event, jqxhr, settings, thrownError )
{
    console.log('>>> AJAX ERROR DETECTED');
    console.log('>>> URL:      ', settings.url);
    console.log('>>> jqXHR:    ', jqxhr);
    console.log('>>> Response: ', jqxhr.status + ' ' + jqxhr.statusText);
    if( parseInt(jqxhr.status) == 0 ) return;
    if( parseInt(jqxhr.status) == 200 ) return;
    
    var title    = $_AJAX_ERROR_DIALOG_TITLE;
    var contents = $_AJAX_ERROR_CONTENTS;
    
    contents = contents.replace('{$url}',      settings.url);
    contents = contents.replace('{$response}', jqxhr.status + ' ' + jqxhr.statusText);
    
    alert(contents);
    $('body').find('.blockUI').remove();
});


function wasuuup()
{
    return parseInt(Math.random() * 1000000000000000);
}

//noinspection JSUnusedGlobalSymbols
/**
 * Get a document and show a dialog from it
 *
 * @param {string}   title
 * @param {string}   url
 * @param {boolean}  full_sized
 * @param {function} callback
 */
function show_ajax_dialog(title, url, full_sized, callback)
{
    if( typeof full_sized == 'undefined' ) full_sized = false;
    
    var width = $(window).width() - 20;
    if( ! full_sized && width > 480 ) width = 480;
    
    var height = 'auto';
    if( full_sized ) height = $(window).height() - 20;
    
    $.get(url, function(response)
    {
        var html = '<div id="ajax_temporary_dialog" style="display: none;">' + response + '</div>';
        $('body').append(html);
        
        $('#ajax_temporary_dialog').dialog({
            modal:     true,
            title:     title,
            width:     width,
            height:    height,
            maxHeight: $(window).height() - 20,
            open:      function() { $('body').css('overflow', 'hidden'); },
            close:     function() { $('#ajax_temporary_dialog').dialog('destroy').remove(); $('body').css('overflow', 'auto'); }
        });
        
        if( callback ) callback();
    });
}

//noinspection JSUnusedGlobalSymbols
function close_ajax_dialog()
{
    $('#ajax_temporary_dialog').dialog('close');
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
    
    var width = $(window).width() - 20;
    if( ! full_sized && width > 480 ) width = 480;
    
    var height = 'auto';
    if( full_sized ) height = $(window).height() - 20;
    
    $(selector).dialog({
        modal:     true,
        title:     $(selector).attr('title'),
        width:     width,
        height:    height,
        maxHeight: $(window).height() - 20,
        open:      function() { $('body').css('overflow', 'hidden'); },
        close:     function() { $(this).dialog('destroy'); $('body').css('overflow', 'auto'); }
    });
}

function set_body_metas()
{
    var width  = $(window).width();
    var height = $(window).height();
    var $body  = $('body');
    
    $body.attr('data-start-width',   width);
    $body.attr('data-window-width',  width);
    $body.attr('data-start-height',  height);
    $body.attr('data-window-height', height);
    
    $body.attr('data-header-can-be-fixed', height >= 300 ? "true" : "false");
    
    if( height >= width ) $body.attr('data-orientation', 'portrait');
    else                  $body.attr('data-orientation', 'landscape');
    
         if(width >= 1900) $body.attr('data-viewport-class', '1920');
    else if(width >= 1340) $body.attr('data-viewport-class', '1360');
    else if(width >= 1260) $body.attr('data-viewport-class', '1280');
    else if(width >= 1000) $body.attr('data-viewport-class', '1024');
    else if(width >=  700) $body.attr('data-viewport-class',  '768');
    else                   $body.attr('data-viewport-class',  '480');
}

//noinspection JSUnusedGlobalSymbols
/**
 * This function should be called AFTER set_body_metas
 */
function adjust_top_dimensions()
{
    var height = $(window).height();
    
    var $header     = $('#header');
    var $admin_menu = $('#admin_menu');
    if( height < 300 )
    {
        $header.toggleClass('fixed', false);
        $('body').css('padding-top', 0);
    }
    else
    {
        var header_height = $header.height();
        $header.toggleClass('fixed', true);
        $('body').css('padding-top', header_height);
    }
}

/**
 * If forced, this function should run AFTER set_body_metas.
 * 
 * @param {boolean} forced
 */
function toggle_main_menu_items(forced)
{
    if( forced )
        if( $(window).width() == $('body').attr('data-start-width') ) return;
    
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
    
    if( left < 0 ) left = 0;
    
    $trigger.toggleClass('submenu_visible');
    $menu.toggle().css('left', left + 'px').css('top',  top + 'px');
    // TODO: Fix this fucking toggle!
    // $trigger.find('.menu_toggle > span').toggle();
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
    $target.find('form[data-avoid-ajax-browser-hooks!="true"]').ajaxForm({
        target:       target_id,
        beforeSubmit: function()
                      {
                          if( ! $target.hasClass('no_refresh_blocking') ) $target.block(blockUI_medium_params);
                      },
        success:      function()
                      {
                          if( ! $target.hasClass('no_refresh_blocking') ) $target.unblock();
                          refresh_record_browser($target);
                      }
    });
    
    $target.find('.prettyPhoto').prettyPhoto({social_tools: false});
    check_wrapped_tables();
}

//noinspection JSUnusedGlobalSymbols
function reload_record_browser($browser)
{
    var url = $browser.attr('data-src');
    $browser.load(url, function()
    {
        refresh_record_browser( $(this) );
    });
}

function toggle_info_section(handler, handler_is_prefix)
{
    var $targets;
    if( handler_is_prefix ) $targets = $('*[id^="' + handler + '"]');
    else                    $targets = $('#' + handler);
    
    var visible = null;
    $targets.each(function()
    {
        var $target = $(this);
        
        if( visible == null ) visible = $target.is(':visible');
        
        if( visible )
            $target.toggle('fast');
        else
            $target.toggle('fast')
                .addClass(    'highlighted',  50 ).delay(10)
                .removeClass( 'highlighted',  50 ).delay(10)
                .addClass(    'highlighted',  50 ).delay(10)
                .removeClass( 'highlighted',  50 ).delay(10)
                .addClass(    'highlighted',  50 ).delay(10)
                .removeClass( 'highlighted', 200 )
            ;
    });
    
    visible = ! visible;
    if( visible ) set_engine_pref(handler, '');
    else          set_engine_pref(handler, 'hidden');
}

function prepare_buttonized_radios()
{
    $('.buttonized_radios label')
        .hover(
            function() { $(this).toggleClass('state_hover', true)  },
            function() { $(this).toggleClass('state_hover', false) }
        )
        .click(function()
        {
            $(this).closest('.buttonized_radios').find('label').toggleClass('state_active', false);
            $(this).toggleClass('state_active', true)
        })
    ;
}

//noinspection JSUnusedGlobalSymbols
function toggle_fa_pseudo_switch(src, toggle)
{
    var is_on;
    if( typeof toggle != 'undefined' )
        is_on = ! toggle;
    else
        is_on = $(src).find('.toggle-on:visible').length > 0;
    
    var value_on  = $(src).attr('data-value-on');
    var value_off = $(src).attr('data-value-off');
    
    if( is_on )
    {
        $(src).attr('data-current-value', value_off);
        $(src).find('input').val( value_off );
        $(src).find('.toggle-on').hide();
        $(src).find('.toggle-off').show();
    }
    else
    {
        $(src).attr('data-current-value', value_on);
        $(src).find('input').val( value_on );
        $(src).find('.toggle-on').show();
        $(src).find('.toggle-off').hide();
    }
}

//noinspection JSUnusedGlobalSymbols
/**
 * Deprecated and kept until a better solution is found
 */
function check_main_menu_auto_collapse()
{
    if( typeof $_MAIN_MENU_AUTO_COLLAPSE_WIDTH == 'undefined' )
        $_MAIN_MENU_AUTO_COLLAPSE_WIDTH = 0;
    
    if( $_MAIN_MENU_AUTO_COLLAPSE_WIDTH == 0 )
        return;
    
    if( $(window).width() <= $_MAIN_MENU_AUTO_COLLAPSE_WIDTH )
        $('body').attr('data-main-menu-collapsed', 'true');
    else
        $('body').attr('data-main-menu-collapsed', null);
}

/**
 * Initializes blockUI prgress updater
 */
function blockUI_progress_init()
{
    var $contanier = $('#blockui_progress_container');
    if( $contanier.length == 0 ) return;
    
    var $bar       = $contanier.find('.bar');
    var $percent   = $contanier.find('.percent');
    var $numbers   = $contanier.find('.numbers');
    
    var percentVal = '0%';
    $bar.css('width', percentVal);
    $percent.html(percentVal);
    if( $numbers.length > 0 ) $numbers.text('? / ? KB');
}

/**
 * Updates upload progress on blockUI element
 * 
 * @param event
 * @param {int} position
 * @param {int} total
 * @param {int} percentComplete
 */
function blockUI_progress_update(event, position, total, percentComplete)
{
    var $contanier = $('#blockui_progress_container');
    var $bar       = $contanier.find('.bar');
    var $percent   = $contanier.find('.percent');
    var $numbers   = $contanier.find('.numbers');
    
    var percentVal = percentComplete + '%';
    $bar.css('width', percentVal);
    $percent.html(percentVal);
    
    if( $numbers.length > 0 )
    {
        if( typeof $numbers.attr('data-max') == 'undefined' )
            $numbers.attr('data-max', total);
        
        var kbmb = "KB";
        if( total >= 1000000 )
        {
            kbmb     = "MB";
            total    = total    / 1000000;
            position = position / 1000000;
        }
        else
        {
            total    = total    / 1000;
            position = position / 1000;
        }
        
        total    = total.toFixed(1);
        position = position.toFixed(1);
        $numbers.text(sprintf('%s / %s %s', position, total, kbmb));
    }
}

/**
 * Finalizes upload progress on blockUI element
 */
function blockUI_progress_complete()
{
    var $contanier = $('#blockui_progress_container');
    var $bar       = $contanier.find('.bar');
    var $percent   = $contanier.find('.percent');
    var $numbers   = $contanier.find('.numbers');
    
    var percentVal = '100%';
    $bar.css('width', percentVal);
    $percent.html(percentVal);
    
    if( $numbers.length > 0 )
    {
        var total = parseInt($numbers.attr('data-max'));
        var kbmb  = "KB";
        if( total >= 1000000 )
        {
            kbmb  = "MB";
            total = total / 1000000;
        }
        else
        {
            total = total / 1000;
        }
        
        total = total.toFixed(1);
        $numbers.text(sprintf('%s / %1$s %s', total, kbmb));
    }
}

$(document).ready(function()
{
    // adjust_top_dimensions();
    set_body_metas();
    check_wrapped_tables();
    // check_main_menu_auto_collapse();
    
    $(window).resize(function()
    {
        if( $('#main_menu_trigger').hasClass('open') ) toggle_main_menu_items(true);
        // adjust_top_dimensions();
        set_body_metas();
        check_wrapped_tables();
        // check_main_menu_auto_collapse();
    });
    
    prepare_submenus();
    
    $(window).click(function()
    {
        hide_dropdown_menus();
    });
    
    prepare_buttonized_radios();
    
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
