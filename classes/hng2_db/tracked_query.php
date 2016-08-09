<?php
/**
 * Tracked Query class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_db;

class tracked_query
{
    public $host_and_db;
    public $query;
    public $rows_in_result;
    public $execution_time;
    public $backtrace;
    
    public function __construct($db_settings, $query, $rows_in_result, $execution_time, $backtrace)
    {
        $this->host_and_db    = $db_settings;
        $this->query          = $query;
        $this->rows_in_result = $rows_in_result;
        $this->execution_time = $execution_time;
        $this->backtrace      = $backtrace;
    }
}
