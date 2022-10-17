<?php
/**
 * Database controlling class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_db;

class db_controller
{
    /**
     * @var db_settings[]
     */
    private $read_dbs = array();
    
    /**
     * @var db_settings[]
     */
    private $write_dbs = array();
    
    /**
     * @var db_settings
     */
    private $current_read_db = null;
    
    /**
     * @var tracked_query[]
     */
    private $tracked_queries = array();
    
    private $last_query = "";
    
    public function __construct()
    {
        global $DATABASES;
        
        if( count($DATABASES) == 1 )
        {
            $this->read_dbs  =
            $this->write_dbs = array(new db_settings(
                $DATABASES[0]["host"],
                $DATABASES[0]["user"],
                $DATABASES[0]["pass"],
                $DATABASES[0]["db"],
                $DATABASES[0]["port"]
            ));
            
            return;
        }
        
        foreach($DATABASES as $database)
        {
            if( $database["usage"] == "read" )
                $this->read_dbs[] = new db_settings(
                    $database["host"],
                    $database["user"],
                    $database["pass"],
                    $database["db"],
                    $database["port"]
                );
            elseif( $database["usage"] == "write" )
                $this->write_dbs[] = new db_settings(
                    $database["host"],
                    $database["user"],
                    $database["pass"],
                    $database["db"],
                    $database["port"]
                );
        }
        
        if( empty($this->read_dbs)  ) throw new \RuntimeException("No READING databases have been found in the config file.");
        if( empty($this->write_dbs) ) throw new \RuntimeException("No WRITING databases have been found in the config file.");
    }
    
    /**
     * Run a WRITE-ONLY query and return amount of affected rows
     * from the last writing case
     *
     * @param $query
     *
     * @return int
     * 
     * @throws \Exception
     */
    public function exec($query)
    {
        global $config, $modules;
        
        $return = 0;
        $this->last_query = $query;
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        
        foreach($this->write_dbs as $db)
        {
            if( is_null($db->handler) ) $db->connect();
            
            $query_start = $config->query_tracking_enabled ? microtime(true) : 0;
            $error_info = array();
            
            $return     = $db->exec($query);
            $error_info = $db->handler->errorInfo();
            
            if( ! empty($error_info[2]) )
            {
                if( $config->globals["@db_controller:no_error_processing"] !== true )
                {
                    foreach($modules as $this_module)
                        if( ! empty($this_module->php_includes->before_logging_db_error) )
                            include "{$this_module->abspath}/{$this_module->php_includes->before_logging_db_error}";
                    
                    $logfd  = date("Ymd");
                    $logfl  = "{$config->logfiles_location}/db_errors-{$logfd}.log";
                    $logdt  = date("Y-m-d H:i:s");
                    $logmsg = "[$logdt] Error while executing query:\n\n"
                            . "{$error_info[2]}\n\n"
                            . "Query:\n" . htmlspecialchars($query) . "\n\n"
                            . "Stack trace:\n"
                    ;
                    $backtrace2 = debug_backtrace();
                    foreach($backtrace2 as $backtrace_item2)
                        $logmsg .= " • " . $backtrace_item2["file"] . ":" . $backtrace_item2["line"] . "\n";
                    $logmsg .= "\n";
                    
                    $ip   = get_remote_address();
                    $host = @gethostbyaddr($ip); if(empty($host)) $host = $ip;
                    $loc  = get_geoip_location($ip);
                    $isp  = get_geoip_isp($ip);
                    $logmsg .= "Connection data:\n"
                            .  " • IP:       $ip\n"
                            .  " • Host:     $host\n"
                            .  " • Location: $loc\n"
                            .  " • IPS:      $isp\n"
                            .  " • QueryStr: {$_SERVER["QUERY_STRING"]}\n"
                            .  " • Referer:  {$_SERVER["HTTP_REFERER"]}\n"
                            .  "\n";
                    
                    @file_put_contents($logfl, $logmsg, FILE_APPEND);
                    
                    foreach($modules as $this_module)
                        if( ! empty($this_module->php_includes->after_logging_db_error) )
                            include "{$this_module->abspath}/{$this_module->php_includes->after_logging_db_error}";
                }
                
                throw new \Exception(
                    "Error while executing query:\n" .
                    "{$error_info[2]}"
                );
            }
            
            if( $config->query_tracking_enabled )
                $this->tracked_queries[] = new tracked_query(
                    "{$db->host}.{$db->database}",
                    $query,
                    $return,
                    microtime(true) - $query_start,
                    $backtrace
                );
        }
        
        return $return;
    }
    
    /**
     * run a READ-ONLY query and return the resource object for further iteration
     *
     * @param string $query
     *
     * @return \PDOStatement
     * 
     * @throws \Exception
     */
    public function query($query)
    {
        global $config, $modules;
        
        $this->last_query = $query;
        $this->set_current_read_db();
        
        $backtrace = "N/A";
        if( $config->query_backtrace_enabled )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        
        $query_start = $config->query_tracking_enabled ? microtime(true) : 0;
        
        $res = $this->current_read_db->query($query);
        $error_info = $this->current_read_db->handler->errorInfo();
        
        if( ! empty($error_info[2]) )
        {
            foreach($modules as $this_module)
                if( ! empty($this_module->php_includes->before_logging_db_error) )
                    include "{$this_module->abspath}/{$this_module->php_includes->before_logging_db_error}";
            
            $logfd   = date("Ymd");
            $logfl   = "{$config->logfiles_location}/db_errors-{$logfd}.log";
            $logdt  = date("Y-m-d H:i:s");
            $logmsg = "[$logdt] Error while executing query:\n\n"
                . "{$error_info[2]}\n\n"
                . "Query:\n" . $query . "\n\n"
                . "Stack trace:\n"
            ;
            $backtrace2 = debug_backtrace();
            foreach($backtrace2 as $backtrace_item2)
                $logmsg .= " • " . $backtrace_item2["file"] . ":" . $backtrace_item2["line"] . "\n";
            $logmsg .= "\n";
            
            $ip   = get_remote_address();
            $host = @gethostbyaddr($ip); if(empty($host)) $host = $ip;
            $loc  = get_geoip_location($ip);
            $isp  = get_geoip_isp($ip);
            $logmsg .= "Connection data:\n"
                    .  " • IP:       $ip\n"
                    .  " • Host:     $host\n"
                    .  " • Location: $loc\n"
                    .  " • IPS:      $isp\n"
                    .  " • QueryStr: {$_SERVER["QUERY_STRING"]}\n"
                    .  " • Referer:  {$_SERVER["HTTP_REFERER"]}\n"
                    .  "\n";
            
            @file_put_contents($logfl, $logmsg, FILE_APPEND);
            
            foreach($modules as $this_module)
                if( ! empty($this_module->php_includes->after_logging_db_error) )
                    include "{$this_module->abspath}/{$this_module->php_includes->after_logging_db_error}";
            
            throw new \Exception(
                "Error while executing query:\n" .
                "{$error_info[2]}"
            );
        }
        
        if( $config->query_tracking_enabled )
            $this->tracked_queries[] = new tracked_query(
                "{$this->current_read_db->host}.{$this->current_read_db->database}",
                $query,
                $res->rowCount(),
                microtime(true) - $query_start,
                $backtrace
            );
        
        return $res;
    }
    
    /**
     * @param \PDOStatement $res
     *
     * @return int
     */
    public function num_rows(\PDOStatement $res)
    {
        return $res->rowCount();
    }
    
    /**
     * Fetch a row from a result set
     * 
     * @param \PDOStatement $res
     *
     * @return object
     */
    public function fetch_object(\PDOStatement $res)
    {
        global $config;
        
        $return = @$res->fetchObject();
        
        $memory_limit = ini_get('memory_limit');
        if( preg_match('/^(\d+)(.)$/', $memory_limit, $matches) )
        {
            if(      $matches[2] == 'M' ) $memory_limit = $matches[1] * 1024 * 1024;
            else if( $matches[2] == 'K' ) $memory_limit = $matches[1] * 1024;
        }
        
        $used = memory_get_usage(true);
        if( $used >= ($memory_limit - 1024) )
        {
            $rows   = $this->num_rows($res);
            $logfd  = date("Ymd");
            $logfl  = "{$config->logfiles_location}/db_errors-{$logfd}.log";
            $logdt  = date("Y-m-d H:i:s");
            $logmsg = "[$logdt] Memory of $memory_limit bytes exhausted when attempting to fetch a row from a {$rows} rows result set.\n\n"
                    . $this->last_query . "\n\n";
            $backtrace2 = debug_backtrace();
            foreach($backtrace2 as $backtrace_item2)
                $logmsg .= " • " . $backtrace_item2["file"] . ":" . $backtrace_item2["line"] . "\n";
            $logmsg .= "\n";
            
            $ip   = get_remote_address();
            $host = @gethostbyaddr($ip); if(empty($host)) $host = $ip;
            $loc  = get_geoip_location($ip);
            $isp  = get_geoip_isp($ip);
            $logmsg .= "Connection data:\n"
                    .  " • IP:       $ip\n"
                    .  " • Host:     $host\n"
                    .  " • Location: $loc\n"
                    .  " • IPS:      $isp\n"
                    .  " • QueryStr: {$_SERVER["QUERY_STRING"]}\n"
                    .  " • Referer:  {$_SERVER["HTTP_REFERER"]}\n"
                    .  "\n";
           
            @file_put_contents($logfl, $logmsg, FILE_APPEND);
            
            throw new \Exception("Error when fetching result set: memory exhausted.");
        }
        
        return $return;
    }
    
    private function set_current_read_db()
    {
        if( ! is_null($this->current_read_db) ) return;
        
        foreach($this->read_dbs as $db)
        {
            if( ! is_null($db->handler) )
            {
                $this->current_read_db = $db;
                
                return;
            }
            
            $db->connect();
            $this->current_read_db = $db;
            
            return;
        }
        
        throw new \RuntimeException("Can't connect to any READING database.");
    }
    
    /**
     * @return tracked_query[]
     */
    public function get_tracked_queries()
    {
        return $this->tracked_queries;
    }
    
    public function get_tracked_queries_count()
    {
        return count($this->tracked_queries);
    }
    
    public function get_last_query()
    {
        return $this->last_query;
    }
}
