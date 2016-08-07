<?php
namespace hng2_base\repository;

use hng2_modules\categories\category_record;
use hng2_modules\gallery\items_data;
use hng2_tools\record_browser;

class media_repository extends abstract_repository
{
    protected $row_class       = "hng2_base\\repository\\media_record";
    protected $table_name      = "media";
    protected $key_column_name = "id_media";
    protected $additional_select_fields = array(
        "( select concat(user_name, '\\t', display_name, '\\t', email, '\\t', level)
           from account where account.id_account = media.id_author )
           as _author_data",
        "( select concat(slug, '\\t', title)
           from categories where categories.id_category = media.main_category )
           as _main_category_data",
        
        "( select group_concat(tag order by order_attached asc separator ',')
           from media_tags where media_tags.id_media = media.id_media )
           as tags_list",
        "( select group_concat(id_category order by order_attached asc separator ',')
           from media_categories where media_categories.id_media = media.id_media )
           as categories_list",
        "( select group_concat(id_account order by order_attached asc separator ',')
           from media_mentions where media_mentions.id_media = media.id_media )
           as mentions_list",
    );
    
    /**
     * @param $id
     *
     * @return media_record|null
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
     * @return media_record[]
     */
    public function find($where, $limit, $offset, $order)
    {
        return parent::find($where, $limit, $offset, $order);
    }
    
    /**
     * @param media_record $record
     *
     * @return int
     */
    public function save($record)
    {
        global $database;
        
        $this->validate_record($record);
        $obj = $record->get_for_database_insertion();
        
        $obj->last_update = date("Y-m-d H:i:s");
        
        return $database->exec("
            insert into {$this->table_name}
            (
                id_media         ,
                id_author        ,
                
                path             ,
                type             ,
                mime_type        ,
                dimensions       ,
                size             ,
                title            ,
                thumbnail        ,
                
                description      ,
                main_category    ,
                
                visibility       ,
                status           ,
                password         ,
                allow_comments   ,
                
                creation_date    ,
                creation_ip      ,
                creation_host    ,
                creation_location,
                
                publishing_date  ,
                views            ,
                comments_count   ,
                
                last_update      ,
                last_viewed      ,
                last_commented
            ) values (
                '{$obj->id_media         }',
                '{$obj->id_author        }',
                
                '{$obj->path             }',
                '{$obj->type             }',
                '{$obj->mime_type        }',
                '{$obj->dimensions       }',
                '{$obj->size             }',
                '{$obj->title            }',
                '{$obj->thumbnail        }',
                
                '{$obj->description      }',
                '{$obj->main_category    }',
                
                '{$obj->visibility       }',
                '{$obj->status           }',
                '{$obj->password         }',
                '{$obj->allow_comments   }',
                
                '{$obj->creation_date    }',
                '{$obj->creation_ip      }',
                '{$obj->creation_host    }',
                '{$obj->creation_location}',
                
                '{$obj->publishing_date  }',
                '{$obj->views            }',
                '{$obj->comments_count   }',
                
                '{$obj->last_update      }',
                '{$obj->last_viewed      }',
                '{$obj->last_commented   }'
            ) on duplicate key update
                title             = '{$obj->title            }',
                description       = '{$obj->description      }',
                main_category     = '{$obj->main_category    }',
                
                visibility        = '{$obj->visibility       }',
                status            = '{$obj->status           }',
                password          = '{$obj->password         }',
                allow_comments    = '{$obj->allow_comments   }',
                
                last_update       = '{$obj->last_update      }'
        ");
    }
    
    /**
     * @param media_record $record
     *
     * @throws \Exception
     */
    public function validate_record($record)
    {
        if( ! $record instanceof media_record )
            throw new \Exception(
                "Invalid object class! Expected: {$this->row_class}, received: " . get_class($record)
            );
    }
    
    public function set_category($id_category, $id_media)
    {
        global $database;
        
        $attached = $this->get_attached_categories($id_media);
        if( isset($attached[$id_category]) ) return 0;
        
        $order = microtime(true);
        $date  = date("Y-m-d H:i:s");
        return $database->exec("
            insert into media_categories set
            id_media       = '$id_media',
            id_category    = '$id_category',
            date_attached  = '$date',
            order_attached = '$order'
        ");
    }
    
    public function unset_category($id_category, $id_media)
    {
        global $database;
        
        return $database->exec("
            delete from media_categories where
            id_media    = '$id_media' and
            id_category = '$id_category'
        ");
    }
    
    /**
     * @param $id_media
     *
     * @return category_record[]
     *
     * @throws \Exception
     */
    public function get_attached_categories($id_media)
    {
        global $database;
        
        $res = $database->query("
            select
                media_categories.order_attached,
                categories.*
            from
                media_categories, categories
            where
                media_categories.id_media = '$id_media' and
                categories.id_category    = media_categories.id_category
            order by
                order_attached
        ");
        
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        while($row = $database->fetch_object($res))
            $return[$row->id_category] = new category_record($row);
        
        return $return;
    }
    
    public function trash($id_media)
    {
        global $database;
        
        # TODO: Hide all attached media?
        
        $date = date("Y-m-d H:i:s");
        return $database->exec("
            update media set
                status      = 'trashed',
                last_update = '$date'
            where
                id_media = '$id_media'
        ");
    }
    
    /**
     * Media index builder
     * Used to build indexes by user/category/tag/date
     *
     * @param array $where Initial params
     *
     * @param bool  $skip_date_check
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    protected function build_find_params($where = array(), $skip_date_check = false)
    {
        global $settings;
        
        $today = date("Y-m-d H:i:s");
        $where[] = "status = 'published'";
        $where[] = "visibility <> 'private'";
        
        if( ! $skip_date_check )
            $where[] = "publishing_date <= '$today'";
        
        // TODO: Complement where[] with additional filters (per user level, etc.)
        
        $order  = "publishing_date desc";
        $limit  = $settings->get("modules:gallery.items_per_page", 30);
        $offset = (int) $_GET["offset"];
        
        if( empty($limit) ) $limit = 30;
        
        return (object) array(
            "where"  => $where,
            "limit"  => $limit,
            "offset" => $offset,
            "order"  => $order
        );
    }
    
    /**
     * @param $id_category
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    protected function build_find_params_for_category($id_category)
    {
        $return = $this->build_find_params();
        
        # Added to EXCLUDE featured posts
        $return->where[]
            = "( main_category = '{$id_category}' or id_media in
                 ( select id_media from media_categories
                   where media_categories.id_category = '{$id_category}' )
               )";
        
        return $return;
    }
    
    protected function build_find_params_for_date_archive($start_date, $end_date)
    {
        $return = $this->build_find_params(array(), true);
        
        $return->where[] = "publishing_date >= '$start_date'";
        $return->where[] = "publishing_date <= '$end_date'";
        
        return $return;
    }
    
    /**
     * @param        $id_account
     *
     * @param string $type
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    protected function build_find_params_for_author($id_account, $type = "")
    {
        $return = $this->build_find_params();
        
        $return->where[] = "id_author = '$id_account'";
        if( ! empty($type) )
            $return->where[] = "type = '" . trim(stripslashes($type)) . "'";
        
        return $return;
    }
    
    public function get_for_author($id_account, $type = "")
    {
        $find_params = $this->build_find_params_for_author($id_account, $type);
        
        return $this->get_items_data($find_params);
    }
    
    public function get_for_category($id_category)
    {
        $find_params = $this->build_find_params_for_category($id_category);
        
        return $this->get_items_data($find_params);
    }
    
    public function get_for_date_archive($start_date, $end_date)
    {
        $find_params = $this->build_find_params_for_date_archive($start_date, $end_date);
        
        return $this->get_items_data($find_params);
    }
    
    /**
     * Standard way to build the posts collection
     *
     * @param array  $where
     * @param int    $limit
     * @param int    $offset
     * @param string $order
     *
     * @return media_record[]
     */
    public function lookup($where, $limit = 0, $offset = 0, $order = "")
    {
        $params = $this->build_find_params();
        
        if( empty($where)  ) $where  = array();
        if( empty($limit)  ) $limit  = $params->limit;
        if( empty($offset) ) $offset = $params->offset;
        if( empty($order)  ) $order  = $params->order;
        
        $where = array_merge($where, $params->where);
        
        return parent::find($where, $limit, $offset, $order);
    }
    
    /**
     * @param $find_params
     *
     * @return items_data
     */
    private function get_items_data($find_params)
    {
        $items_data = new items_data();
        
        $items_data->browser    = new record_browser("");
        $items_data->count      = $this->get_record_count($find_params->where);
        $items_data->pagination = $items_data->browser->build_pagination($items_data->count, $find_params->limit, $find_params->offset);
        $items_data->items      = $this->find($find_params->where, $find_params->limit, $find_params->offset, $find_params->order);
        
        $this->preload_authors($items_data);
        
        return $items_data;
    }
    
    private function preload_authors(items_data &$items_data)
    {
        $author_ids = array();
        foreach( $items_data->items as $item ) $author_ids[] = $item->id_author;
        
        if( count($author_ids) > 0 )
        {
            $author_ids         = array_unique($author_ids);
            $authors_repository = new accounts_repository();
            $authors            = $authors_repository->get_multiple($author_ids);
            
            foreach( $items_data->items as $index => &$item )
                $item->set_author($authors[$item->id_author]);
        }
    }
    
    
    public function get_grouped_tag_counts($since = "", $min_hits = 10)
    {
        global $database;
        
        $min_hits = empty($min_hits) ? 10 : $min_hits;
        $having   = $min_hits == 1   ? "" : "having `count` >= '$min_hits'";
        
        if( empty($since) )
            $query = "
                select tag, count(tag) as `count` from media_tags
                group by tag
                $having
                order by `count` desc
            ";
        else
            $query = "
                select tag, count(tag) as `count` from media_tags
                where date_attached >= '{$since}'
                group by tag
                $having
                order by `count` desc
            ";
        
        $res = $database->query($query);
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        while( $row = $database->fetch_object($res) )
            $return[$row->tag] = $row->count;
        
        return $return;
    }
}
