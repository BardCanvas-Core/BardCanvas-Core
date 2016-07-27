<?php
namespace hng2_base\repository;

class accounts_repository extends abstract_repository
{
    protected $row_class                = "hng2_base\\repository\\account_record";
    protected $table_name               = "account";
    protected $key_column_name          = "id_account";
    
    /**
     * @param $id
     *
     * @return account_record|null
     */
    public function get($id)
    {
        return parent::get($id);
    }
    
    /**
     * @param array  $where
     * @param int    $limit
     * @param int    $offset
     * @param string $order
     *
     * @return account_record[]
     */
    public function find($where, $limit, $offset, $order)
    {
        return parent::find($where, $limit, $offset, $order);
    }
    
    /**
     * @param account_record $record
     *
     * @return int
     * 
     * @throws \Exception
     */
    public function save($record)
    {
        // TODO: Implement save() method.
        
        throw new \Exception("Method not implemented.");
    }
    
    /**
     * @param account_record $record
     *
     * @throws \Exception
     */
    public function validate_record($record)
    {
        if( ! $record instanceof account_record )
            throw new \Exception(
                "Invalid object class! Expected: {$this->row_class}, received: " . get_class($record)
            );
    }
    
    /**
     * @param array $ids
     *
     * @return account_record[] id_account as key
     */
    public function get_multiple(array $ids)
    {
        if( empty($ids) ) return array();
        
        $prepared_ids = array();
        foreach($ids as $id) $prepared_ids[] = "'$id'";
        $prepared_ids = implode(", ", $prepared_ids);
        
        $return = array();
        $rows   = $this->find(array("id_account in ($prepared_ids)"), 0, 0, "");
        
        foreach($rows as $row) $return[$row->id_account] = $row;
        
        return $return;
    }
}
