<?php
/**
 * Configuration file.
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

define("ROOTPATH", __DIR__);

# The next aren't neccesary unless specifically needed:
# define("FULL_ROOT_URL",  "https://www.domain.com:8080/dir/subdir");
# define("FULL_ROOT_PATH", "/dir/subdir");

define("ENCRYPTION_KEY",       "BIG_RANDOM_SRING_HERE");
define("WEBSITE_ID",          "hng2site");
define("LANGUAGE_COOKIE_VAR", "ULANG");

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

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

# true:  shows a menu entry for internals.
# false: saves internals to files.
define("EMBED_INTERNALS", true);

ini_set("register_globals",              "Off");
ini_set("display_errors",                "On");
ini_set("zlib.output_compression",       "On");
ini_set("zlib.output_compression_level", "5");

$global_start_time = microtime(true);
