
var max_height = Math.round( $(window).height() * .50 );
if( max_height < 100 ) max_height = 100;

var tinymce_defaults = {
    browser_spellcheck:       true,
    menubar:                  false,
    statusbar:                false,
    relative_urls:            false,
    remove_script_host:       false,
    convert_urls:             false,
    selector:                 '.tinymce',
    plugins:                  'placeholder advlist autolink lists link anchor searchreplace paste codemirror '
                              + 'textcolor fullscreen autoresize image imagetools hr table',
    toolbar:                  tinymce_standard_toolbar,
    imagetools_toolbar:       'imageoptions',
    fontsize_formats:         '10pt 12pt 14pt 18pt 24pt 36pt',
    content_css:              tinymce_default_css_files.join(','),
    content_style:            'body {overflow-y: hidden !important;}',
    autoresize_bottom_margin: 0,
    autoresize_min_height:    100,
    autoresize_max_height:    max_height,
    entity_encoding:          'raw',
    formats : {
        alignleft:   {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'alignleft'},
        aligncenter: {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'aligncenter'},
        alignright:  {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'alignright'}
    },
    codemirror: {
        indentOnInit: true,
        config: {
            mode: 'htmlmixed',
            lineNumbers: true,
            lineWrapping: true,
            indentUnit: 1,
            tabSize: 1,
            matchBrackets: true,
            styleActiveLine: true
        },
        jsFiles: [
            'lib/codemirror.js',
            'addon/edit/matchbrackets.js',
            'mode/xml/xml.js',
            'mode/javascript/javascript.js',
            'mode/css/css.js',
            'mode/htmlmixed/htmlmixed.js',
            'addon/dialog/dialog.js',
            'addon/search/searchcursor.js',
            'addon/search/search.js',
            'addon/selection/active-line.js'
        ],
        cssFiles: [
            'lib/codemirror.css',
            'addon/dialog/dialog.css'
        ]
    }
};

if( $_CURRENT_USER_IS_MOD || $_CURRENT_USER_IS_ADMIN )
    tinymce_defaults.extended_valid_elements = 'script[type|src|async],iframe[src|style|width|height|scrolling|marginwidth|marginheight|frameborder]';

if( tinymce_custom_plugins.length > 0 )
    tinymce_defaults.plugins = tinymce_defaults.plugins + ' ' + tinymce_custom_plugins.join(' ');

var tinymce_full_defaults = $.extend({}, tinymce_defaults);
tinymce_full_defaults.selector = '.tinymce_full';

var tinymce_minimal_defaults = $.extend({}, tinymce_defaults);
tinymce_minimal_defaults.selector = '.tinymce_minimal';

if( tinymce_custom_toolbar_buttons.length > 0 )
{
    tinymce_defaults.toolbar         = tinymce_standard_toolbar + ' | ' + tinymce_custom_toolbar_buttons.join(' ');
    tinymce_full_defaults.toolbar    = tinymce_full_toolbar     + ' | ' + tinymce_custom_toolbar_buttons.join(' ');
}

if( $_CURRENT_USER_LEVEL >= 200 )
{
    tinymce_defaults.toolbar      = tinymce_defaults.toolbar          + ' | code fullscreen';
    tinymce_full_defaults.toolbar = tinymce_full_defaults.toolbar     + ' | code fullscreen';
}
else
{
    tinymce_defaults.toolbar      = tinymce_defaults.toolbar          + ' | fullscreen';
    tinymce_full_defaults.toolbar = tinymce_full_defaults.toolbar     + ' | fullscreen';
}

if( $_CURRENT_USER_LANGUAGE != "en" && $_CURRENT_USER_LANGUAGE != "en_US" )
{
    tinymce_defaults.language     = $_CURRENT_USER_LANGUAGE;
    tinymce_defaults.language_url = $_FULL_ROOT_PATH + '/lib/tinymce-4.6.3/langs/' + $_CURRENT_USER_LANGUAGE + '.js?v=5';
    
    tinymce_full_defaults.language     = $_CURRENT_USER_LANGUAGE;
    tinymce_full_defaults.language_url = $_FULL_ROOT_PATH + '/lib/tinymce-4.6.3/langs/' + $_CURRENT_USER_LANGUAGE + '.js?v=5';
    
    tinymce_minimal_defaults.language     = $_CURRENT_USER_LANGUAGE;
    tinymce_minimal_defaults.language_url = $_FULL_ROOT_PATH + '/lib/tinymce-4.6.3/langs/' + $_CURRENT_USER_LANGUAGE + '.js?v=5';
}
