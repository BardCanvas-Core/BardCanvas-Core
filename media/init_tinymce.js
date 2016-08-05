
var max_height = Math.round( $(window).height() * .50 );
if( max_height < 100 ) max_height = 100;

var tinymce_defaults = {
    menubar:                  false,
    statusbar:                false,
    relative_urls:            false,
    remove_script_host:       false,
    convert_urls:             false,
    selector:                 '.tinymce',
    plugins:                  'placeholder advlist contextmenu autolink lists link anchor searchreplace paste codemirror textcolor fullscreen autoresize',
    toolbar:                  'bold italic strikethrough forecolor fontsizeselect removeformat | alignleft aligncenter alignright | bullist numlist outdent indent | link',
    contextmenu:              'cut copy paste | link',
    fontsize_formats:         '10pt 12pt 14pt 18pt 24pt 36pt',
    content_css:              tinymce_default_css_files.join(','),
    autoresize_bottom_margin: 0,
    autoresize_min_height:    100,
    autoresize_max_height:    max_height,
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

if( tinymce_custom_plugins.length > 0 )
    tinymce_defaults.plugins = tinymce_defaults.plugins + ' ' + tinymce_custom_plugins.join(' ');

if( tinymce_custom_toolbar_buttons.length > 0 )
    tinymce_defaults.toolbar = tinymce_defaults.toolbar + ' ' + tinymce_custom_toolbar_buttons.join(' ');
tinymce_defaults.toolbar = tinymce_defaults.toolbar  + ' | fullscreen';

if( $_CURRENT_USER_IS_ADMIN )
    tinymce_defaults.toolbar = tinymce_defaults.toolbar + ' | code';

if( $_CURRENT_USER_LANGUAGE != "en" && $_CURRENT_USER_LANGUAGE != "en_US" )
    tinymce_defaults.language = $_CURRENT_USER_LANGUAGE;
