<?php
/**
 * Configuration file.
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

define("ABSPATH",        dirname(__FILE__));
define("ENCRYPTION_KEY", "BIG_RANDOM_SRING_HERE");
define("WEBSITE_ID",     "hng2site");

$DATABASES = array(
    array(
        "usage"  => "write",
        "host"   => "localhost",
        "port"   => "3306",
        "user"   => "MYSQL_USER",
        "pass"   => "MYSQL_PASSWORD",
        "db"     => "MYSQL_DATABASE",
    ),
);

$MEMCACHE_SERVERS = array(
    array(
        "host"   => "localhost",
        "port"   => "11211",
    ),
);

define("ENABLE_QUERY_BACKTRACE",      true);
define("ENABLE_QUERY_TRACKING",       true);
define("DISPLAY_PERFORMANCE_DETAILS", true);

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

ini_set("register_globals",              "Off");
ini_set("display_errors",                "On");
ini_set("zlib.output_compression",       "On");
ini_set("zlib.output_compression_level", "5");

$global_start_time = microtime(true);
