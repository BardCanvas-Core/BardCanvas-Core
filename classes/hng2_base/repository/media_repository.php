<?php
namespace hng2_base\repository;

use hng2_modules\categories\category_record;

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
            $where[] = "(publishing_date <> '0000-00-00 00:00:00' and publishing_date <= '$today')";
        
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
    public function build_find_params_for_category($id_category)
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
    
    public function build_find_params_for_date_archive($start_date, $end_date)
    {
        $return = $this->build_find_params(array(), true);
        
        $return->where[] = "publishing_date >= '$start_date'";
        $return->where[] = "publishing_date <= '$end_date'";
        
        return $return;
    }
    
    /**
     * @param $id_account
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    public function build_find_params_for_author($id_account)
    {
        $return = $this->build_find_params();
        
        $return->where[] = "id_author = '$id_account'";
        
        return $return;
    }
}
