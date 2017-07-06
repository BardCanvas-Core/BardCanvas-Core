<?php
/**
 * Configuration file.
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

define("ROOTPATH", __DIR__);

# The next two aren't neccesary unless specifically needed:
# define("FULL_ROOT_URL",  "https://www.domain.com:8080/dir/subdir");
# define("FULL_ROOT_PATH", "/dir/subdir");

############################################
# Modify these settings to suit your needs #
############################################ 

# Set a big random string to encrypt cookies and other stuff
define("ENCRYPTION_KEY", "BIG_RANDOM_SRING_HERE");

# Set a short lowecased string to identify your website, like, your website name
define("WEBSITE_ID",     "mybcweb");

# You can leave this one as it is, but if you're using multiple
# instances on the same domain, you should set a unique
# UPPERCASED, alpha-only string to differentiate
# the language set on the cookie variable.
define("LANGUAGE_COOKIE_VAR", "ULANG");

# Specify database user, password and database name below
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

#######################################################################################
# You may leave the ones below as they are unless you want to fine tune configuration #
#######################################################################################

# If memcached is not installed, ignore this. But if you have it installed
# and it is located on a different server or port, modify accordingly.
$MEMCACHE_SERVERS = array(
    array(
        "host"   => "localhost",
        "port"   => "11211",
    ),
);

#--------------------------------------------
# Cluster node identification - use if needed
# The next variable has to be set STATICALLY for every server in the cluster.
#----------------------------------------------------------------------------
# Uncomment and adapt this one if the node ID is set in the environment:
#    $NUMERIC_SERVER_ID = getenv("BARDCANVAS_SERVER_ID");
# Use this one if you will place the ID into a static file_
#    $NUMERIC_SERVER_ID = trim(file_get_contents("/etc/bardcanvas_server_id"));

# Set this to "On" if you want to show PHP errors.
ini_set("display_errors", "On");

# If you set display_errors to "On", you might want to limit error scopes below. 
# Otherwise, just comment the line below.
# Note: if the php.ini file has a different value, that one will be used.
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

# If you turn on the debugging settings,
# you can modify the way they're shown below.
# true  - shows a menu entry for internals.
# false - saves internals to files. They'll be available at http://yoursite.com/logs/internals/
define("EMBED_INTERNALS", true);

# If you don't want pages to be compressed and save bandwidth, comment these two lines.
ini_set("zlib.output_compression",       "On");
ini_set("zlib.output_compression_level", "5");

# Register Globals is never used, but if you write a module that needs it, set this to "On"
ini_set("register_globals", "Off");

$global_start_time = microtime(true);
