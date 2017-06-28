<?php
/**
 * Setup Script
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

#
# Inits
#

ini_set("register_globals",              "Off");
ini_set("display_errors",                "On");
ini_set("zlib.output_compression",       "On");
ini_set("zlib.output_compression_level", "5");
chdir(__DIR__);
header("Content-Type: text/html; charset=utf-8");

$bundle_name    = "BardCanvas";
$support_text   = "";
$preinit_bundle = true;
if( file_exists(__DIR__ . "/setup_bundle.inc") ) include __DIR__ . "/setup_bundle.inc";
$preinit_bundle = false;

#
# Already installed?
#

if( file_exists(__DIR__ . "/data/installed") ) die("<!DOCTYPE html>
    <html><head><title>{$bundle_name} Setup</title></head><body>
        <h1>Already installed</h1>
        <p>Your {$bundle_name} installation is already installed and configured. There's no need to run this script again.</p>
        <p><a href='index.php'>Click here to open the home page</a></p>
    </body></html>");

if( file_exists("config.php") ) include "config.php";

#
# FFMPEG preload
#

$ffmpeg_path    = "";
$ffmpeg_version = "";

if( function_exists("shell_exec") )
{
    $res = shell_exec("which ffmpeg");
    if( ! empty($res) )
    {
        foreach(explode("\n", $res) as $line)
        {
            $line = trim($line);
            $path = dirname($line);
            $file = basename($line);
            if( $file != "ffmpeg" ) continue;
            
            $res = shell_exec("$line -version");
            if( ! empty($res) )
            {
                $ffmpeg_version = $res;
                $ffmpeg_path    = $path;
                break;
            }
        }
    }
}

#
# Start
#

if($_GET["go"] == "true")
{
    $db = current($DATABASES);
    try
    {
        $db = new \PDO( "mysql:host={$db["host"]};mysql:port={$db["port"]};dbname={$db["db"]}", $db["user"], $db["pass"] );
    }
    catch(\Exception $e)
    {
        die("<!DOCTYPE html>
            <html><head><title>{$bundle_name} Setup</title></head><body>
                <h1>Error connecting to the database</h1>
                <p>There's been a problem connecting to the database:</p>
                <div class='framed_content state_ko'>
                    {$e->getMessage()}
                </div>
                <p>Please check your config.php file and make sure the database connection info is correct.</p>
                <p><a href='setup.php'>Click here to run setup checks again.</a></p>
            </body></html>");
    }
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
          
          name  varchar(128) not null default '',
          value mediumtext,
          
          primary key ( name )
          
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci'
    ");
    
    $rnd = mt_rand(100, 999);
    $db->exec("
        insert ignore into settings (name, value) values
        ( 'engine.website_name'                           , 'New BardCanvas Website' ),
        ( 'engine.enabled'                                , 'true' ),
        ( 'engine.default_language'                       , 'en_US' ),
        ( 'engine.template'                               , 'base' ),
        ( 'engine.user_session_cookie'                    , '_BCSC{$rnd}' ),
        ( 'engine.user_online_cookie'                     , '_BCOC{$rnd}' ),
        ( 'engine.mail_sender_name'                       , 'BardCanvas Notifier' ),
        ( 'engine.mail_sender_email'                      , 'nobody@localhost' ),
        ( 'engine.user_levels'                            , '0 - Unregistered\\n1 - Unconfirmed\\n10 - Newcomer\\n100 - Author\\n150 - VIP\\n200 - Editor\\n240 - Coadmin\\n255 - Admin' ),
        ( 'modules:accounts.installed'                    , 'true' ),
        ( 'modules:accounts.enabled'                      , 'true' ),
        ( 'modules:accounts.register_enabled'             , 'false' ),
        ( 'modules:accounts.enforce_device_registration'  , 'false' ),
        ( 'modules:accounts.disable_registrations_widget' , 'false' ),
        ( 'modules:settings.installed'                    , 'true' ),
        ( 'modules:settings.enabled'                      , 'true' ),
        ( 'modules:updates_client.installed'              , 'true' ),
        ( 'modules:updates_client.enabled'                , 'true' ),
        ( 'modules:modules_manager.installed'             , 'true' ),
        ( 'modules:modules_manager.enabled'               , 'true' ),
        ( 'modules:modules_manager.disable_cache'         , 'true' )
    ");
    
    $db->exec("
        CREATE TABLE `account` (
          `id_account`      bigint unsigned not null default 0,
          `user_name`       VARCHAR(64) NOT NULL DEFAULT '',
          `password`        VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'MD5 hash of password',
          `display_name`    VARCHAR(255) NOT NULL DEFAULT '',
          `email`           VARCHAR(255) NOT NULL DEFAULT '',
          `alt_email`       VARCHAR(255) NOT NULL DEFAULT '',
          `birthdate`       DATE,
          `avatar`          VARCHAR(255) NOT NULL DEFAULT '',
          `profile_banner`  VARCHAR(255) NOT NULL DEFAULT '',
          `signature`       TEXT,
          `bio`             TEXT,
          `homepage_url`    VARCHAR(255) NOT NULL DEFAULT '',
          `country`         VARCHAR(2) NOT NULL DEFAULT '' COMMENT 'ISO code',
          `level`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
          `state`           ENUM('new','enabled','disabled') NOT NULL DEFAULT 'new',
          `creation_host`   VARCHAR(255) NOT NULL DEFAULT '',
          `creation_date`   DATETIME NOT NULL,
          `last_update`     DATETIME NOT NULL,
          `changelog`       TEXT,
          PRIMARY KEY (`id_account`),
          INDEX   user_name ( user_name(5) ),
          INDEX   email     ( email(5)  ),
          INDEX   alt_email ( alt_email(5) )
        ) DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
    ");
    
    $pass = md5("admin");
    $db->exec("
        insert ignore into account set
          `id_account`      = concat('10', '0000000000', '000'),
          `user_name`       = 'admin',
          `password`        = '$pass',
          `display_name`    = 'Administrator',
          `email`           = 'nobody@localhost',
          `alt_email`       = '',
          `country`         = 'us',
          `level`           = 255,
          `state`           = 'enabled',
          `creation_host`   = '127.0.0.1; localhost',
          `creation_date`   = now(),
          `last_update`     = now(),
          `changelog`       = 'Created on installation\\n\\n'
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS `account_devices` (
          `id_device`      bigint unsigned not null default 0,
          `id_account`     bigint unsigned not null default 0,
          `device_label`   VARCHAR(255) NOT NULL DEFAULT '',
          `device_header`  VARCHAR(255) NOT NULL DEFAULT '',
          `creation_date`  DATETIME NOT NULL,
          `state`          ENUM('unregistered','enabled','disabled','deleted') NOT NULL DEFAULT 'unregistered',
          `last_activity`  DATETIME NOT NULL,
        
          PRIMARY KEY            (`id_device`),
          INDEX `id_account`     (`id_account` ASC),
          INDEX `account_device` (`id_account` ASC, `id_device` ASC),
          INDEX `account_agent`  (`id_account` ASC, `device_header`(8) ASC)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci'
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS `account_logins` (
          `id_account` bigint unsigned not null default 0,
          `id_device`  bigint unsigned not null default 0,
          `login_date` DATETIME NOT NULL,
          `ip`         VARCHAR(255) NOT NULL DEFAULT '',
          `hostname`   VARCHAR(255) NOT NULL DEFAULT '',
          `location`   VARCHAR(255) NOT NULL DEFAULT '',
        
          PRIMARY KEY (`id_account` ASC, `id_device` ASC, `login_date` ASC)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci'
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS `account_engine_prefs` (
          `id_account`     bigint unsigned not null default 0,
          `name`           VARCHAR(128) NOT NULL DEFAULT '',
          `value`          TEXT,
          PRIMARY KEY      (`id_account`, `name`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci'
    ");
    
    $db->exec("
        CREATE TABLE `countries` (
          `name`    varchar(50) NOT NULL default '',
          `alpha_2` varchar(2) NOT NULL default '',
          `alpha_3` varchar(3) NOT NULL default '',
          PRIMARY KEY  (`alpha_2`),
          INDEX   `alpha_3` (`alpha_3`),
          INDEX   `name` (`name`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE='utf8mb4_unicode_ci'
    ");
    
    $db->exec("
        INSERT INTO `countries` (`name`, `alpha_2`, `alpha_3`) VALUES
        ('Afghanistan', 'af', 'afg'),
        ('Aland Islands', 'ax', 'ala'),
        ('Albania', 'al', 'alb'),
        ('Algeria', 'dz', 'dza'),
        ('American Samoa', 'as', 'asm'),
        ('Andorra', 'ad', 'and'),
        ('Angola', 'ao', 'ago'),
        ('Anguilla', 'ai', 'aia'),
        ('Antarctica', 'aq', ''),
        ('Antigua and Barbuda', 'ag', 'atg'),
        ('Argentina', 'ar', 'arg'),
        ('Armenia', 'am', 'arm'),
        ('Aruba', 'aw', 'abw'),
        ('Australia', 'au', 'aus'),
        ('Austria', 'at', 'aut'),
        ('Azerbaijan', 'az', 'aze'),
        ('Bahamas', 'bs', 'bhs'),
        ('Bahrain', 'bh', 'bhr'),
        ('Bangladesh', 'bd', 'bgd'),
        ('Barbados', 'bb', 'brb'),
        ('Belarus', 'by', 'blr'),
        ('Belgium', 'be', 'bel'),
        ('Belize', 'bz', 'blz'),
        ('Benin', 'bj', 'ben'),
        ('Bermuda', 'bm', 'bmu'),
        ('Bhutan', 'bt', 'btn'),
        ('Bolivia, Plurinational State of', 'bo', 'bol'),
        ('Bonaire, Sint Eustatius and Saba', 'bq', 'bes'),
        ('Bosnia and Herzegovina', 'ba', 'bih'),
        ('Botswana', 'bw', 'bwa'),
        ('Bouvet Island', 'bv', ''),
        ('Brazil', 'br', 'bra'),
        ('British Indian Ocean Territory', 'io', ''),
        ('Brunei Darussalam', 'bn', 'brn'),
        ('Bulgaria', 'bg', 'bgr'),
        ('Burkina Faso', 'bf', 'bfa'),
        ('Burundi', 'bi', 'bdi'),
        ('Cambodia', 'kh', 'khm'),
        ('Cameroon', 'cm', 'cmr'),
        ('Canada', 'ca', 'can'),
        ('Cape Verde', 'cv', 'cpv'),
        ('Cayman Islands', 'ky', 'cym'),
        ('Central African Republic', 'cf', 'caf'),
        ('Chad', 'td', 'tcd'),
        ('Chile', 'cl', 'chl'),
        ('China', 'cn', 'chn'),
        ('Christmas Island', 'cx', ''),
        ('Cocos (Keeling) Islands', 'cc', ''),
        ('Colombia', 'co', 'col'),
        ('Comoros', 'km', 'com'),
        ('Congo', 'cg', 'cog'),
        ('Congo, The Democratic Republic of the', 'cd', 'cod'),
        ('Cook Islands', 'ck', 'cok'),
        ('Costa Rica', 'cr', 'cri'),
        (\"Cote d'Ivoire\", 'ci', 'civ'),
        ('Croatia', 'hr', 'hrv'),
        ('Cuba', 'cu', 'cub'),
        ('Curacao', 'cw', 'cuw'),
        ('Cyprus', 'cy', 'cyp'),
        ('Czech Republic', 'cz', 'cze'),
        ('Denmark', 'dk', 'dnk'),
        ('Djibouti', 'dj', 'dji'),
        ('Dominica', 'dm', 'dma'),
        ('Dominican Republic', 'do', 'dom'),
        ('Ecuador', 'ec', 'ecu'),
        ('Egypt', 'eg', 'egy'),
        ('El Salvador', 'sv', 'slv'),
        ('Equatorial Guinea', 'gq', 'gnq'),
        ('Eritrea', 'er', 'eri'),
        ('Estonia', 'ee', 'est'),
        ('Ethiopia', 'et', 'eth'),
        ('Falkland Islands (Malvinas)', 'fk', 'flk'),
        ('Faroe Islands', 'fo', 'fro'),
        ('Fiji', 'fj', 'fji'),
        ('Finland', 'fi', 'fin'),
        ('France', 'fr', 'fra'),
        ('French Guiana', 'gf', 'guf'),
        ('French Polynesia', 'pf', 'pyf'),
        ('French Southern Territories', 'tf', ''),
        ('Gabon', 'ga', 'gab'),
        ('Gambia', 'gm', 'gmb'),
        ('Georgia', 'ge', 'geo'),
        ('Germany', 'de', 'deu'),
        ('Ghana', 'gh', 'gha'),
        ('Gibraltar', 'gi', 'gib'),
        ('Greece', 'gr', 'grc'),
        ('Greenland', 'gl', 'grl'),
        ('Grenada', 'gd', 'grd'),
        ('Guadeloupe', 'gp', 'glp'),
        ('Guam', 'gu', 'gum'),
        ('Guatemala', 'gt', 'gtm'),
        ('Guernsey', 'gg', 'ggy'),
        ('Guinea', 'gn', 'gin'),
        ('Guinea-Bissau', 'gw', 'gnb'),
        ('Guyana', 'gy', 'guy'),
        ('Haiti', 'ht', 'hti'),
        ('Heard Island and McDonald Islands', 'hm', ''),
        ('Holy See (Vatican City State)', 'va', 'vat'),
        ('Honduras', 'hn', 'hnd'),
        ('Hong Kong', 'hk', 'hkg'),
        ('Hungary', 'hu', 'hun'),
        ('Iceland', 'is', 'isl'),
        ('India', 'in', 'ind'),
        ('Indonesia', 'id', 'idn'),
        ('Iran, Islamic Republic of', 'ir', 'irn'),
        ('Iraq', 'iq', 'irq'),
        ('Ireland', 'ie', 'irl'),
        ('Isle of Man', 'im', 'imn'),
        ('Israel', 'il', 'isr'),
        ('Italy', 'it', 'ita'),
        ('Jamaica', 'jm', 'jam'),
        ('Japan', 'jp', 'jpn'),
        ('Jersey', 'je', 'jey'),
        ('Jordan', 'jo', 'jor'),
        ('Kazakhstan', 'kz', 'kaz'),
        ('Kenya', 'ke', 'ken'),
        ('Kiribati', 'ki', 'kir'),
        (\"Korea, Democratic People's Republic of\", 'kp', 'prk'),
        ('Korea, Republic of', 'kr', 'kor'),
        ('Kuwait', 'kw', 'kwt'),
        ('Kyrgyzstan', 'kg', 'kgz'),
        (\"Lao People's Democratic Republic\", 'la', 'lao'),
        ('Latvia', 'lv', 'lva'),
        ('Lebanon', 'lb', 'lbn'),
        ('Lesotho', 'ls', 'lso'),
        ('Liberia', 'lr', 'lbr'),
        ('Libyan Arab Jamahiriya', 'ly', 'lby'),
        ('Liechtenstein', 'li', 'lie'),
        ('Lithuania', 'lt', 'ltu'),
        ('Luxembourg', 'lu', 'lux'),
        ('Macao', 'mo', 'mac'),
        ('Macedonia, The former Yugoslav Republic of', 'mk', 'mkd'),
        ('Madagascar', 'mg', 'mdg'),
        ('Malawi', 'mw', 'mwi'),
        ('Malaysia', 'my', 'mys'),
        ('Maldives', 'mv', 'mdv'),
        ('Mali', 'ml', 'mli'),
        ('Malta', 'mt', 'mlt'),
        ('Marshall Islands', 'mh', 'mhl'),
        ('Martinique', 'mq', 'mtq'),
        ('Mauritania', 'mr', 'mrt'),
        ('Mauritius', 'mu', 'mus'),
        ('Mayotte', 'yt', 'myt'),
        ('Mexico', 'mx', 'mex'),
        ('Micronesia, Federated States of', 'fm', 'fsm'),
        ('Moldova, Republic of', 'md', 'mda'),
        ('Monaco', 'mc', 'mco'),
        ('Mongolia', 'mn', 'mng'),
        ('Montenegro', 'me', 'mne'),
        ('Montserrat', 'ms', 'msr'),
        ('Morocco', 'ma', 'mar'),
        ('Mozambique', 'mz', 'moz'),
        ('Myanmar', 'mm', 'mmr'),
        ('Namibia', 'na', 'nam'),
        ('Nauru', 'nr', 'nru'),
        ('Nepal', 'np', 'npl'),
        ('Netherlands', 'nl', 'nld'),
        ('New Caledonia', 'nc', 'ncl'),
        ('New Zealand', 'nz', 'nzl'),
        ('Nicaragua', 'ni', 'nic'),
        ('Niger', 'ne', 'ner'),
        ('Nigeria', 'ng', 'nga'),
        ('Niue', 'nu', 'niu'),
        ('Norfolk Island', 'nf', 'nfk'),
        ('Northern Mariana Islands', 'mp', 'mnp'),
        ('Norway', 'no', 'nor'),
        ('Oman', 'om', 'omn'),
        ('Pakistan', 'pk', 'pak'),
        ('Palau', 'pw', 'plw'),
        ('Palestinian Territory, Occupied', 'ps', 'pse'),
        ('Panama', 'pa', 'pan'),
        ('Papua New Guinea', 'pg', 'png'),
        ('Paraguay', 'py', 'pry'),
        ('Peru', 'pe', 'per'),
        ('Philippines', 'ph', 'phl'),
        ('Pitcairn', 'pn', 'pcn'),
        ('Poland', 'pl', 'pol'),
        ('Portugal', 'pt', 'prt'),
        ('Puerto Rico', 'pr', 'pri'),
        ('Qatar', 'qa', 'qat'),
        ('Reunion', 're', 'reu'),
        ('Romania', 'ro', 'rou'),
        ('Russian Federation', 'ru', 'rus'),
        ('Rwanda', 'rw', 'rwa'),
        ('Saint Barthelemy', 'bl', 'blm'),
        ('Saint Helena, Ascension and Tristan Da Cunha', 'sh', 'shn'),
        ('Saint Kitts and Nevis', 'kn', 'kna'),
        ('Saint Lucia', 'lc', 'lca'),
        ('Saint Martin (French Part)', 'mf', 'maf'),
        ('Saint Pierre and Miquelon', 'pm', 'spm'),
        ('Saint Vincent and The Grenadines', 'vc', 'vct'),
        ('Samoa', 'ws', 'wsm'),
        ('San Marino', 'sm', 'smr'),
        ('Sao Tome and Principe', 'st', 'stp'),
        ('Saudi Arabia', 'sa', 'sau'),
        ('Senegal', 'sn', 'sen'),
        ('Serbia', 'rs', 'srb'),
        ('Seychelles', 'sc', 'syc'),
        ('Sierra Leone', 'sl', 'sle'),
        ('Singapore', 'sg', 'sgp'),
        ('Sint Maarten (Dutch Part)', 'sx', 'sxm'),
        ('Slovakia', 'sk', 'svk'),
        ('Slovenia', 'si', 'svn'),
        ('Solomon Islands', 'sb', 'slb'),
        ('Somalia', 'so', 'som'),
        ('South Africa', 'za', 'zaf'),
        ('South Georgia and The South Sandwich Islands', 'gs', ''),
        ('South Sudan', 'ss', 'ssd'),
        ('Spain', 'es', 'esp'),
        ('Sri Lanka', 'lk', 'lka'),
        ('Sudan', 'sd', 'sdn'),
        ('Suriname', 'sr', 'sur'),
        ('Svalbard and Jan Mayen', 'sj', 'sjm'),
        ('Swaziland', 'sz', 'swz'),
        ('Sweden', 'se', 'swe'),
        ('Switzerland', 'ch', 'che'),
        ('Syrian Arab Republic', 'sy', 'syr'),
        ('Taiwan, Province of China', 'tw', ''),
        ('Tajikistan', 'tj', 'tjk'),
        ('Tanzania, United Republic of', 'tz', 'tza'),
        ('Thailand', 'th', 'tha'),
        ('Timor-Leste', 'tl', 'tls'),
        ('Togo', 'tg', 'tgo'),
        ('Tokelau', 'tk', 'tkl'),
        ('Tonga', 'to', 'ton'),
        ('Trinidad and Tobago', 'tt', 'tto'),
        ('Tunisia', 'tn', 'tun'),
        ('Turkey', 'tr', 'tur'),
        ('Turkmenistan', 'tm', 'tkm'),
        ('Turks and Caicos Islands', 'tc', 'tca'),
        ('Tuvalu', 'tv', 'tuv'),
        ('Uganda', 'ug', 'uga'),
        ('Ukraine', 'ua', 'ukr'),
        ('United Arab Emirates', 'ae', 'are'),
        ('United Kingdom', 'gb', 'gbr'),
        ('United States', 'us', 'usa'),
        ('United States Minor Outlying Islands', 'um', ''),
        ('Uruguay', 'uy', 'ury'),
        ('Uzbekistan', 'uz', 'uzb'),
        ('Vanuatu', 'vu', 'vut'),
        ('Venezuela, Bolivarian Republic of', 've', 'ven'),
        ('Viet Nam', 'vn', 'vnm'),
        ('Virgin Islands, British', 'vg', 'vgb'),
        ('Virgin Islands, U.S.', 'vi', 'vir'),
        ('Wallis and Futuna', 'wf', 'wlf'),
        ('Western Sahara', 'eh', 'esh'),
        ('Yemen', 'ye', 'yem'),
        ('Zambia', 'zm', 'zmb'),
        ('Zimbabwe', 'zw', 'zwe')
    ");
        
    if( ! empty($ffmpeg_path) )
        $db->exec("insert ignore into settings set value = '$ffmpeg_path' , name = 'engine.ffmpeg_path'");
    
    $messages = array();
    if( file_exists(__DIR__ . "/setup_bundle.inc") ) include __DIR__ . "/setup_bundle.inc";
    ?><!DOCTYPE html>
    <html>
    <head>
        <title><?php echo $bundle_name ?> Setup completed!</title>
        <script type="text/javascript"          src="lib/jquery-1.11.0.min.js"></script>
        <script type="text/javascript"          src="lib/jquery-migrate-1.2.1.js"></script>
        <script type="text/javascript"          src="lib/jquery-ui-1.10.4.custom.min.js"></script>
        <link rel="stylesheet" type="text/css" href="lib/jquery-ui-themes-1.10.4/blitzer/jquery-ui.css">
        <link rel="stylesheet" type="text/css" href="lib/font-awesome-4.6.3/css/font-awesome.css">
        
        <link rel="stylesheet" type="text/css" href="media/styles.css">
        <style type="text/css">
            body { padding: 20px; }
            section .framed_content .row            { padding-bottom: 10px; margin-bottom: 10px; border-bottom: 2px dotted silver; margin-top: 10px; }
            section .framed_content .row:last-child { padding-bottom:    0; margin-bottom:    0; border-bottom: none; }
            section .framed_content .row div.framed_content { margin-bottom: 0; }
        </style>
    </head>
    <body>
    
    <h1><?php echo $bundle_name ?> Setup completed!</h1>
    
    <p><i class="fa fa-warning"></i> <b>Important, don't dismiss:</b> in order to get automatic maintenance,
    you're going to need the next cron jobs:</p>
    
    <pre># m h d m w command
  0 * * * * php -q <?php echo __DIR__; ?>/accounts/scripts/cli_autopurge.php > /dev/null
  0 4 * * * php -q <?php echo __DIR__; ?>/updates_client/cli_check.php       > <?php echo __DIR__; ?>/logs/updates_checker-$(date +\%Y\%m\%d).log 2>&1
</pre>
    
    <p>Please go into your <code>crontab</code> and add the lines above.</p>
    
    <p><i>Note: you may change the updates checked and cache cleaner to run at a time other than 4:00 AM if you want.</i></p>
    
    <?php if( ! empty($messages) )
        foreach($messages as $message)
            echo $message; ?>
    
    <hr>
    
    <p>Now you can login as administrator using <span class="framed_content">admin</span> as username and password.</p>
    
    <?php
    if( ! empty($support_text) ) echo $support_text;
    else echo "<p>If you experience any problem, take a look at the forums on the
               <a href=\"https://bardcanvas.com\" target=\"_blank\">BardCanvas Website</a>.</p>";
    ?>
    
    <p><a href="index.php">Click here to open your new <?php echo $bundle_name ?> powered website</a></p>
    
    </body>
    </html>
    <?
    file_put_contents("data/installed", date("Y-m-d H:i:s"));
    exit;
}

$errors_found = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $bundle_name ?> Setup</title>
    <script type="text/javascript"          src="lib/jquery-1.11.0.min.js"></script>
    <script type="text/javascript"          src="lib/jquery-migrate-1.2.1.js"></script>
    <script type="text/javascript"          src="lib/jquery-ui-1.10.4.custom.min.js"></script>
    <link rel="stylesheet" type="text/css" href="lib/jquery-ui-themes-1.10.4/blitzer/jquery-ui.css">
    <link rel="stylesheet" type="text/css" href="lib/font-awesome-4.6.3/css/font-awesome.css">
    
    <link rel="stylesheet" type="text/css" href="media/styles.css">
    <style type="text/css">
        body { padding: 20px; }
        section .framed_content .row            { padding-bottom: 10px; margin-bottom: 10px; border-bottom: 2px dotted silver; margin-top: 10px; }
        section .framed_content .row:last-child { padding-bottom:    0; margin-bottom:    0; border-bottom: none; }
        section .framed_content .row div.framed_content { margin-bottom: 0; }
    </style>
</head>
<body>

<h1><?php echo $bundle_name ?> Setup</h1>

<p>This page runs a set of tests to ensure that <?php echo $bundle_name ?> can run in your system. Please scroll down to the bottom while
reviewing any warnings or errors before attempting to run the setup operations.</p>

<section>
    <h2>Bare neccessities</h2>
    <div class="framed_content">
        
        <?php if( ! is_dir("lib") ): $errors_found++; ?>
            
            <div class="row clearfix">
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Missing</span>
                
                Library directory
                
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    The core required libraries aren't found. You may need to re-download the bundle or fetch the
                    <code>core/bardcanvas_lib</code> package
                </div>
            </div>
            
        <?php else: ?>
            
            <div class="row clearfix">
                <span class="framed_content inlined pull-right">Present</span>
                
                Library directory
                
            </div>
            
        <?php endif; ?>
        
        <?php if( ! file_exists("config.php") ): $errors_found++; ?>
            
            <div class="row clearfix">
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Missing</span>
                
                Configuration file
                
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Please copy the <code>config-sample.php</code> file as <code>config.php</code>,
                    then add the required values and upload it.
                </div>
            </div>
            
        <?php else: ?>
            
            <div class="row clearfix">
                <span class="framed_content inlined pull-right">Present</span>
                
                Configuration file
                
            </div>
            
        <?php endif; ?>
        
        <div class="row clearfix">
            <?php if( is_writable("data") ): ?>
                <span class="framed_content inlined pull-right">Present</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Read-only</span>
            <?php endif; ?>
            
            Data directory
            
            <?php if( ! is_writable("data") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    The <code>data</code> directory seems to be read-only.<br>
                    Please change its attributes to <code>777</code> (<code>rwxrwxrwx</code>) through FTP
                    or from the command line with <code>chmod 0777 data</code>
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( is_writable("logs") ): ?>
                <span class="framed_content inlined pull-right">Present</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Read-only</span>
            <?php endif; ?>
            
            Logs directory
            
            <?php if( ! is_writable("logs") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    The <code>logs</code> directory seems to be read-only.<br>
                    Please change its attributes to <code>777</code> (<code>rwxrwxrwx</code>) through FTP
                    or from the command line with <code>chmod 0777 logs</code>
                </div>
            <?php endif ?>
        </div>
        
    </div>
</section>

<section>
    <h2>PHP settings</h2>
    <div class="framed_content">
        
        <div class="row clearfix">
            <?php if( ini_get("short_open_tag") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Disabled</span>
            <?php endif; ?>
            
            Short open tag
            
            <?php if( ! ini_get("short_open_tag") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Please edit your <code>php.ini</code> configuration file and set <code>short_open_tag = On</code>
                </div>
            <?php endif ?>
        </div>
        
    </div>
</section>

<section>
    <h2>System binaries</h2>
    <div class="framed_content">
        
        <?php if( ! function_exists("shell_exec")): ?>
            
            <div class="row clearfix">
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Can't check for binaries since <code>shell_exec</code> is disabled!<br>
                    <br><br>
                    You'll manually have to make sure that your system account is able to run all of the next programs:
                    
                    <ul>
                        <li>
                            <code>ffmpeg</code> - for converting uploaded videos into mp4<br>
                            <b>Note:</b> this can be avoided by downloading and installing an static version of ffmpeg from
                            <a href="https://www.johnvansickle.com/ffmpeg/" target="_blank">https://www.johnvansickle.com/ffmpeg/</a>
                            and then specifying it in the configuration.
                        </li>
                    </ul>
                    
                </div>
            </div>
            
        <?php else: ?>
            
            <div class="row clearfix">
                <?php if( ! empty($ffmpeg_version) ): ?>
                    <span class="framed_content inlined pull-right">Pass</span>
                <?php else: ?>
                    <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Unknown</span>
                <?php endif; ?>
                
                FFMPEG for converting video files
                
                <?php if( ! empty($ffmpeg_version)): ?>
                    
                    <blockquote>
                        <pre style="white-space: pre-wrap">Found at <?php echo $ffmpeg_path ?><br><br><?php echo $ffmpeg_version ?></pre>
                    </blockquote>
                    
                <?php else: ?>
                    
                    <div class="framed_content state_highlight">
                        <i class="fa fa-warning"></i>
                        You wont be able to use automatic conversion of videos from the media gallery unless you ask
                        your hostmaster to install ffmpeg on the system or manually download a static version from
                        <a href="https://www.johnvansickle.com/ffmpeg/" target="_blank">https://www.johnvansickle.com/ffmpeg/</a>
                        into your system account and specify the path on the system configuration.
                    </div>
                    
                <?php endif ?>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<section>
    <h2>PHP extensions</h2>
    <div class="framed_content">
        
        <div class="row clearfix">
            <?php if( class_exists("PDO") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Disabled</span>
            <?php endif; ?>
            
            PDO support
            
            <?php if( ! class_exists("PDO") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Please install or enable <code>php-pdo</code> extension.
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( function_exists("zip_open") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Disabled</span>
            <?php endif; ?>
            
            ZIP support
            
            <?php if( ! function_exists("zip_open") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Please install or enable <code>php-zip</code> extension.
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( function_exists("ftp_connect") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Disabled</span>
            <?php endif; ?>
            
            FTP support
            
            <?php if( ! function_exists("ftp_connect") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Please install or enable <code>php-ftp</code> extension.
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( function_exists("mcrypt_encrypt") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Disabled</span>
            <?php endif; ?>
            
            MCrypt
            
            <?php if( ! function_exists("mcrypt_encrypt") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Please install or enable <code>php-mcrypt</code> extension.
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( function_exists("curl_exec") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: $errors_found++; ?>
                <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Disabled</span>
            <?php endif; ?>
            
            CURL
            
            <?php if( ! function_exists("curl_exec") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-warning"></i>
                    Please install or enable <code>php-curl</code> extension.
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( class_exists("Memcache") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: ?>
                <span class="framed_content inlined state_highlight pull-right"><i class="fa fa-info-circle"></i> Disabled</span>
            <?php endif; ?>
            
            Memcache
            
            <?php if( ! class_exists("Memcache") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-info-circle"></i>
                    Memcached wasn't found. If you want to get a  fast website and you have control of your host,
                    you should install <code>memcached</code> and PHP <code>memcache</code> extension.
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( function_exists("imap_open") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: ?>
                <span class="framed_content inlined state_highlight pull-right"><i class="fa fa-info-circle"></i> Disabled</span>
            <?php endif; ?>
            
            (optional) IMAP
            
            <?php if( ! function_exists("imap_open") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-info-circle"></i>
                    Some modules may need <code>php-imap</code>. In such case, you'll be notified when needed.
                </div>
            <?php endif ?>
        </div>
        
        <div class="row clearfix">
            <?php if( function_exists("tidy_repair_string") ): ?>
                <span class="framed_content inlined pull-right">Enabled</span>
            <?php else: ?>
                <span class="framed_content inlined state_highlight pull-right"><i class="fa fa-info-circle"></i> Disabled</span>
            <?php endif; ?>
            
            (optional) Tidy
            
            <?php if( ! function_exists("tidy_repair_string") ): ?>
                <div class="framed_content state_highlight">
                    <i class="fa fa-info-circle"></i>
                    Some modules may need <code>php-tidy</code>. In such case, you'll be notified when needed.
                </div>
            <?php endif ?>
        </div>
        
    </div>
</section>

<?php if( $errors_found > 0): ?>
    
    <div class="framed_content state_ko" align="center" style="padding-top: 20px;">
        <h1>Errors found</h1>
        <p>Please fix the errors above and reload this page to try again.</p>
    </div>

<?php else:
    
    $db_error = "";
    try
    {
        $db = current($DATABASES);
        new \PDO(
            "mysql:host={$db["host"]};mysql:port={$db["port"]};dbname={$db["db"]}", $db["user"], $db["pass"]
        );
    }
    catch(\Exception $e)
    {
        $db_error = $e->getMessage();
    }
    ?>
    
    <section>
        <h2>Configuration checks</h2>
        <div class="framed_content">

            <div class="row clearfix">
                <?php if( empty($db_error) ): ?>
                    <span class="framed_content inlined pull-right">OK</span>
                <?php else: $errors_found++; ?>
                    <span class="framed_content inlined state_ko pull-right"><i class="fa fa-warning"></i> Error</span>
                <?php endif; ?>
                
                Database
                
                <?php if( ! empty($db_error) ): ?>
                    <div class="framed_content state_highlight">
                        <i class="fa fa-warning"></i>
                        There has been an error while accessing the database:<br>
                        <div class="framed_content state_ko"><?php echo $db_error ?></div>
                        <p>Please check your config.php file and make sure the database connection info is correct.</p>
                    </div>
                <?php endif ?>
            </div>

        </div>
    </section>
    
    <div align="center">
        <h1><i class="fa fa-check"></i> Requirement checks passed</h1>
        <p><a href="setup.php?go=true">Please click here to finish setup</a></p>
    </div>

<?php endif; ?>

</body>
</html>
