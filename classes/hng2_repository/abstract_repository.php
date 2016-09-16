<?php
/**
 * Abstract repository
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_repository;

abstract class abstract_repository
{
    protected $row_class                = "";      // OVERRIDE THIS
    protected $table_name               = "";      // OVERRIDE THIS
    protected $key_column_name          = "";      // OVERRIDE THIS
    protected $additional_select_fields = array(); // OVERRIDE IF NEEDED
    
    protected $last_query;
    
    public function __construct()
    {
    }
    
    /**
     * @param $id
     *
     * @return abstract_record|null
     * @throws \Exception
     */
    public function get($id)
    {
        global $object_cache;
        
        if( $object_cache->exists($this->table_name, $id) )
            return $object_cache->get($this->table_name, $id);
        
        $where = array($this->key_column_name => $id);
        
        $res = $this->find($where, 1, 0, "");
        
        if( count($res) == 0 ) return null;
        
        /** @var abstract_record $item */
        $item = current($res);
        $object_cache->set($this->table_name, $id, $item);
        
        return current($res);
    }
    
    /**
     * @param array  $where
     * @param int    $limit
     * @param int    $offset
     * @param string $order
     *
     * @return abstract_record[]
     *
     * @throws \Exception
     */
    public function find($where, $limit, $offset, $order)
    {
        global $database;
        
        $query_where = "";
        if( ! empty($where) ) $query_where = "where " . $this->convert_where($where);
        
        $order_by = "";
        if( ! empty($order) ) $order_by = "order by {$order}";
        
        $limit_by = "";
        if($limit > 0 || $offset > 0 ) $limit_by = "limit $limit offset $offset";
        
        if( empty($this->additional_select_fields) )
        {
            $query = "
                select * from `{$this->table_name}`
                $query_where
                $order_by
                $limit_by
            ";
        }
        else
        {
            $all_fields = array_merge(
                array("`{$this->table_name}`.*"),
                $this->additional_select_fields
            );
        
            $all_fields_string = implode(",\n                  ", $all_fields);
            $query = "
                select
                  $all_fields_string
                from `{$this->table_name}`
                $query_where
                $order_by
                $limit_by
            ";
        }
        
        # echo "<pre>$query</pre>";
        $this->last_query = $query;
        $res = $database->query($query);
        
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        while($row = $database->fetch_object($res))
        {
            $class = $this->row_class;
            $return[] = new $class($row);
        }
        
        return $return;
    }
    
    protected function convert_where(array $where)
    {
        if( empty($where) ) return "true";
        
        $sanitized_where = array();
        foreach($where as $column => $value)
        {
            if( is_numeric($column) )
                $sanitized_where[] = $value;
            else
                $sanitized_where[] = "`{$column}` = '{$value}'";
        }
        
        return implode("\n            and ", $sanitized_where);
    }
    
    /**
     * @param array $where
     *
     * @return int
     */
    public function get_record_count(array $where)
    {
        global $database;
        
        $where = $this->convert_where($where);
        $query = "
            select count(*) as total_rows
            from {$this->table_name}
            where {$where}
        ";
        $this->last_query = $query;
        $res = $database->query($query);
        $row = $database->fetch_object($res);
        
        return $row->total_rows;
    }
    
    /**
     * @param abstract_record $record
     *
     * @return int
     */
    abstract public function save($record);
    
    /**
     * @param abstract_record $record
     *
     * @throws \Exception
     */
    abstract public function validate_record($record);
    
    /**
     * @param $key
     *
     * @return int
     */
    public function delete($key)
    {
        global $database, $object_cache;
        
        $query = "
            delete from {$this->table_name}
            where {$this->key_column_name} = '{$key}'
        ";
        $this->last_query = $query;
        $object_cache->delete($this->table_name, $key);
        
        return $database->exec($query);
    }
    
    public function get_last_query()
    {
        return $this->last_query;
    }
    
    public function get_counts_by_level()
    {
        global $database;
        
        $return = array();
        $res = $database->query("
            select level, count(id_account) as `count`
            from account group by level
        ");
        
        while($row = $database->fetch_object($res))
            $return[$row->level] = $row->count;
        
        return $return;
    }
}
