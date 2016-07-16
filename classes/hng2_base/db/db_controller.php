<?php
/**
 * Database controlling class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base\db;

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
    
    private $tracking_enabled = false;
    
    /**
     * @var tracked_query[]
     */
    private $tracked_queries = array();
    
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
        
        if( defined("ENABLE_QUERY_TRACKING") )
            if( ENABLE_QUERY_TRACKING == true )
                $this->tracking_enabled = true;
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
        $return = 0;
        
        $backtrace = "N/A";
        if( defined("ENABLE_QUERY_BACKTRACE") && ENABLE_QUERY_BACKTRACE )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        
        foreach($this->write_dbs as $db)
        {
            if( is_null($db->handler) ) $db->connect();
            
            $query_start = $this->tracking_enabled ? microtime(true) : 0;
            
            $error_info = array();
            $return = $db->exec($query);
            $error_info = $db->handler->errorInfo();
            
            if( ! empty($error_info[2]) ) throw new \Exception(
                "Error while executing query:\n" .
                "{$error_info[2]}\n\n" .
                "Query:\n" . $query
            );
            
            if( $this->tracking_enabled )
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
        $this->set_current_read_db();
    
        $backtrace = "N/A";
        if( defined("ENABLE_QUERY_BACKTRACE") && ENABLE_QUERY_BACKTRACE )
        {
            $backtrace = debug_backtrace();
            foreach($backtrace as &$backtrace_item) $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        }
        
        $query_start = $this->tracking_enabled ? microtime(true) : 0;
        
        $res = $this->current_read_db->query($query);
        $error_info = $this->current_read_db->handler->errorInfo();
        
        if( ! empty($error_info[2]) ) throw new \Exception(
            "Error while executing query:\n" .
             "{$error_info[2]}\n\n" .
            "Query:\n" . $query
        );
        
        if( $this->tracking_enabled )
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
        return $res->fetchObject();
    }
    
    private function set_current_read_db()
    {
        if( ! is_null($this->current_read_db) ) return;
        
        foreach($this->read_dbs as $db)
        {
            if( ! is_null($db->handler) )
            {
                $this->read_dbs = $db;
                
                return;
            }
            
            $db->connect();
            $this->current_read_db = $db;
            
            return;
        }
        
        throw new \RuntimeException("Can't connect to any READING database.");
    }
    
    /**
     * @param boolean $tracking_enabled
     */
    public function enable_tracking($tracking_enabled)
    {
        $this->tracking_enabled = $tracking_enabled;
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
}
