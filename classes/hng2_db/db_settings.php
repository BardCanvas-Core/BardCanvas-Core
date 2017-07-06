<?php
/**
 * Database instance settings
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_db;

class db_settings
{
    public $host;
    public $user;
    public $password;
    public $database;
    public $port;
    
    /**
     * @var \PDO
     */
    public $handler = null;
    
    public function __construct($host, $user, $password, $database, $port)
    {
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port     = $port;
    }
    
    public function connect()
    {
        $this->handler = new \PDO(
            "mysql:host={$this->host};mysql:port={$this->port};dbname={$this->database}",
            $this->user,
            $this->password
        );
        
        if( $this->handler ) $this->exec("SET SESSION sql_mode = ''");
    }
    
    /**
     * @param $query
     *
     * @return int
     */
    public function exec($query)
    {
        return $this->handler->exec($query);
    }
    
    /**
     * @param $query
     *
     * @return \PDOStatement
     */
    public function query($query)
    {
        return $this->handler->query($query);
    }
}
