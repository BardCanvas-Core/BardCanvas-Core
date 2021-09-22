<?php
namespace hng2_base;

use hng2_repository\abstract_repository;

class accounts_repository extends abstract_repository
{
    protected $row_class                = "hng2_base\\account_record";
    protected $table_name               = "account";
    protected $key_column_name          = "id_account";
    
    /**
     * @param $id_or_slug
     *
     * @return account_record|null
     */
    public function get($id_or_slug)
    {
        global $object_cache;
        
        if( empty($id_or_slug) ) return null;
        
        if( $object_cache->exists($this->table_name, $id_or_slug) )
            return $object_cache->get($this->table_name, $id_or_slug);
        
        if( is_numeric($id_or_slug) ) $where = array("id_account" => $id_or_slug);
        else                          $where = array("user_name" => $id_or_slug);
        
        $res = $this->find($where, 1, 0, "");
        
        if( count($res) == 0 ) return null;
        
        /** @var account_record $record */
        $record = current($res);
    
        $object_cache->set($this->table_name, $record->id_account, $record);
        
        return $record;
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
        global $mem_cache;
        global $object_cache;
        
        if( empty($ids) ) return array();
        
        $return = array();
        foreach($ids as $index => $id)
        {
            $record = $object_cache->get($this->table_name, $id);
            if( ! is_null($record) )
            {
                $return[$id] = $record;
                unset($ids[$index]);
            }
        }
        
        if( empty($ids) ) return $return;
        
        $prepared_ids = array();
        foreach($ids as $id) $prepared_ids[] = "'$id'";
        $prepared_ids = implode(", ", $prepared_ids);
        
        $mem_key = "accounts_repository/get_multiple/hash:" . md5(implode(",", $ids));
        $res     = $mem_cache->get($mem_key);
        if( is_array($res) ) return $res;
        if( $res == "none" ) return array();
        
        $rows = $this->find(array("id_account in ($prepared_ids)"), 0, 0, "");
        foreach($rows as $row)
        {
            $object_cache->set($this->table_name, $row->id_account, $row);
            $return[$row->id_account] = $row;
        }
        
        if( empty($return) ) $mem_cache->set($mem_key, "none",  0, 60*5);
        else                 $mem_cache->set($mem_key, $return, 0, 60*5);
        
        return $return;
    }
    
    /**
     * @param int  $level
     * @param int  $end_level
     * @param bool $enabled_only
     *
     * @return array [id_account => {id_account, user_name, display_name, email}, ...]
     * @throws \Exception
     */
    public function get_basics_above_level($level, $end_level = 255, $enabled_only = true)
    {
        global $database;
        
        $query = "
            select id_account, user_name, display_name, email from account
            where level >= '$level' and level <= '$end_level'
        ";
        if( $enabled_only ) $query .= " and state = 'enabled'";
        
        $res = $database->query($query);
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        while($row = $database->fetch_object($res)) $return[$row->id_account] = (object) array(
            "id_account"   => $row->id_account,
            "user_name"    => $row->user_name,
            "display_name" => $row->display_name,
            "email"        => $row->email,
        );
        
        return $return;
    }
    
    /**
     * @param int $id_account
     *
     * @return int
     */
    public function delete($id_account)
    {
        global $modules, $database, $config;
        
        $database->exec("delete from account_devices      where id_account = '$id_account'");
        $database->exec("delete from account_engine_prefs where id_account = '$id_account'");
        $database->exec("delete from account_logins       where id_account = '$id_account'");
        $this->delete_user_files($id_account);
        
        $config->globals["deleting_id_account"] = $id_account;
        $modules["accounts"]->load_extensions("accounts_repository", "delete");
        unset( $config->globals["deleting_id_account"] );
        
        return parent::delete($id_account);
    }
    
    private function delete_user_files($id_account)
    {
        global $config;
        
        if( empty($config->globals["deletions_log"]) ) $config->globals["deletions_log"] = array();
        
        $account = $this->get($id_account);
        
        $dirs = array(
            "{$config->datafiles_location}/user_avatars/{$account->user_name}",
            "{$config->datafiles_location}/user_profile_banners/{$account->user_name}",
        );
        
        foreach($dirs as $dir)
        {
            $files = glob("$dir/*");
            if( empty($files) ) continue;
            
            foreach($files as $file)
                if( @unlink($file) )
                    $config->globals["deletions_log"][] = "User file <b>$file</b> deleted.";
            
            if( @unlink($dir) ) $config->globals["deletions_log"][] = "User directory <b>$dir</b> deleted.";
        }
    }
    
    /**
     * Grabs engine preferences for multiple accounts
     * 
     * @param array  $account_ids
     * @param string $pref_name
     *
     * @return array [id_account => value, id_account => value, ...]
     * @throws \Exception
     */
    public function get_multiple_engine_prefs(array $account_ids, $pref_name)
    {
        global $database, $mem_cache;
        
        $key_hash      = md5($pref_name . ":" . implode(",", $account_ids));
        $mem_cache_key = "accounts_repository/get_multiple_engine_prefs/hash:$key_hash";
        $mem_cache_ttl = 300;
        $cached_value  = $mem_cache->get($mem_cache_key);
        if( is_array($cached_value) ) return $cached_value;
        if( $cached_value == "none" ) return array();
        
        foreach($account_ids as &$id) $id = "'$id'";
        $account_ids = implode(", ", $account_ids);
        
        $res = $database->query("
            select id_account, value from account_engine_prefs where name = '$pref_name'
            and id_account in ($account_ids)
        ");
        
        if( $database->num_rows($res) == 0 )
        {
            $mem_cache->set($mem_cache_key, "none", 0, $mem_cache_ttl);
            
            return array();
        }
        
        $return = array();
        while($row = $database->fetch_object($res))
            $return[$row->id_account] = json_decode($row->value);
        
        $mem_cache->set($mem_cache_key, $return, 0, $mem_cache_ttl);
        
        return $return;
    }
    
    public function find_user_prefs($id_account, $filter)
    {
        global $database;
    
        $res = $database->query("
            select name, value from account_engine_prefs
            where id_account = '$id_account'
            and name like '$filter'
        ");
        
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        while($row = $database->fetch_object($res))
            $return[$row->name] = json_decode($row->value);
        
        return $return;
    }
    
    public function get_online_users_list($boundary_minutes = 10, $exclude_self = true, $extra_filter = array())
    {
        global $database, $account, $config;
        
        $self_id = $exclude_self ? $account->id_account : 0;
        $date    = date("Y-m-d H:i:s", strtotime("now - $boundary_minutes minutes"));
        $where   = array(
            "account.id_account            <> '$self_id'",
            "account_devices.last_activity >= '$date'",
            "account_devices.id_account     = account.id_account",
            "account.state                  = 'enabled'",
        );
        if( ! empty($extra_filter) ) $where = array_merge($where, $extra_filter);
        $where = implode(" and\n                ", $where);
        
        $query = "
            select
                account.id_account,
                account.user_name,
                account.level,
                account.display_name,
                account.avatar,
                account.email,
                account_devices.last_activity
            from
                account,
                account_devices
            where
                $where
            order by
                display_name
        ";
        
        $res = $database->query($query);
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        $added  = array();
        while($row = $database->fetch_object($res))
        {
            if( empty($added[$row->id_account]) )
            {
                if( $row->avatar == "@gravatar" )
                {
                    $avatar = "https://www.gravatar.com/avatar/" . md5(trim(strtolower($row->email)));
                }
                else
                {
                    $file   = empty($row->avatar) ? "media/default_avatar.jpg" : "user/{$row->user_name}/avatar";
                    $avatar = "{$config->full_root_path}/{$file}";
                }
                
                $return[] = (object) array(
                    "id_account"    => $row->id_account,
                    "user_name"     => $row->user_name,
                    "level"         => $row->level,
                    "display_name"  => $row->display_name,
                    "avatar"        => $avatar,
                    "last_activity" => $row->last_activity,
                );
                
                $added[$row->id_account] = true;
            }
        }
        
        return $return;
    }
}
