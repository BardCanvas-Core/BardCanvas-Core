<?php
namespace hng2_media;

use hng2_base\accounts_repository;
use hng2_base\config;
use hng2_repository\abstract_repository;
use hng2_modules\categories\category_record;
use hng2_modules\gallery\items_data;
use hng2_tools\record_browser;

class media_repository extends abstract_repository
{
    protected $row_class       = "hng2_media\\media_record";
    protected $table_name      = "media";
    protected $key_column_name = "id_media";
    protected $additional_select_fields = array(
        # Author data
        "( select concat(user_name, '\\t', display_name, '\\t', email, '\\t', level)
           from account where account.id_account = media.id_author )
           as _author_data",
        # Main category data
        "( select concat(slug, '\\t', title, '\\t', visibility, '\\t', min_level)
           from categories where categories.id_category = media.main_category )
           as _main_category_data",
        # Tags list
        "( select group_concat(tag order by date_attached asc, order_attached asc separator ',')
           from media_tags where media_tags.id_media = media.id_media )
           as tags_list",
        # Categories list
        "( select group_concat(id_category order by date_attached asc, order_attached asc separator ',')
           from media_categories where media_categories.id_media = media.id_media )
           as categories_list",
        # Mentions list
        "( select group_concat(id_account order by date_attached asc, order_attached asc separator ',')
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
        
        $date  = date("Y-m-d H:i:s");
        return $database->exec("
            insert into media_categories set
            id_media       = '$id_media',
            id_category    = '$id_category',
            date_attached  = '$date',
            order_attached = '0'
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
                media_categories.date_attached asc, media_categories.order_attached asc
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
        global $settings, $account;
        
        $today = date("Y-m-d H:i:s");
        $where[] = "status = 'published'";
        $where[] = "visibility <> 'private'";
        
        if( ! $skip_date_check ) $where[] = "publishing_date <= '$today'";
        
        if( ! $account->_exists )
            $where[] = "visibility = 'public'";
        else
            $where[] = "(
                            visibility = 'public' or visibility = 'users' or 
                            (
                                visibility = 'level_based' and
                                '{$account->level}' >= (select level from account where account.id_account = media.id_author)
                            ) 
                        )";
        
        $where[] = "(
            media.main_category in (
                select id_category from categories where
                visibility = 'public' or visibility = 'users' or
                (visibility = 'level_based' and '{$account->level}' >= min_level)
            )
        )";
        
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
    
    /**
     * @param $tag
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    protected function build_find_params_for_tag($tag)
    {
        $return = $this->build_find_params();
        
        $return->where[]
            = "( id_media in (select id_media from media_tags where media_tags.tag = '{$tag}') )";
        
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
    
    public function get_for_tag($tag)
    {
        $find_params = $this->build_find_params_for_tag($tag);
        
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
        global $database, $settings;
        
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
        
        if( $settings->get("modules:posts.show_featured_posts_tag_everywhere") == "true" ) return $return;
        
        $excluded = $settings->get("modules:posts.featured_posts_tag");
        if( empty($excluded) ) return $return;
        
        unset($return[$excluded]);
        return $return;
    }
    
    /**
     * Receives an uploaded media item and process it for saving
     *
     * @param array $data                   actual or forged $_POST array
     * @param array $file                   an item from $_FILES
     * @param bool  $return_item_on_success If true, the item object will be returned instead of "OK".
     *
     * @return string|media_record "OK" or error message or object
     */
    public function receive_and_save($data, $file, $return_item_on_success = false)
    {
        global $modules, $config, $account, $settings;
        
        $current_module = $modules["gallery"];
        
        $item = new media_record();
        # We don't use set_from_post because actual data may not come from $_POST but from $data
        # $item->set_from_post();
        foreach( $data as $key => $val ) $item->{$key} = stripslashes($val);
        
        if( empty($item->visibility) ) $item->visibility = "public";
        
        if( empty($item->id_media) )
        {
            if( empty($item->main_category) )
                $item->main_category = "0000000000000";
            
            if( empty($item->title) )
            {
                $item->title = stripslashes($account->display_name . " - " . $file["name"]);
                
                if( $this->get_record_count(array("title" => $item->title)) )
                    return $current_module->language->messages->item_exists;
            }
        }
        
        /** @var abstract_item_manager $media_manager */
        $media_manager = null;
        $media_path    = "";
        $thumbnail     = "";
        $media_type    = "";
        $mime_type     = "";
        $dimensions    = "";
        $size          = 0;
        
        if( empty($data["id_media"]) && ! empty($file) )
        {
            if( ! is_uploaded_file($file["tmp_name"]) )
                return $current_module->language->messages->invalid->upload;
            
            $extension = strtolower(end(explode(".", $file["name"])));
            if( empty($extension) ) return $current_module->language->messages->invalid->file;
            
            if( ! isset($config->upload_file_types[$extension]) )
                return $current_module->language->messages->invalid->file;
            
            $media_manager_class = $config->upload_file_types[$extension];
            if( $media_manager_class == "system" )
                $media_manager_class = "hng2_media\\item_manager_{$extension}";
            
            try
            {
                $media_manager = new $media_manager_class(
                    $file["name"],
                    $file["type"],
                    $file["tmp_name"]
                );
                
                $parts         = explode(".", $file["name"]); array_pop($parts);
                $file_name     = sanitize_file_name(implode(".", $parts));
                $date          = date("dHis");
                $new_file_name = "{$account->user_name}-{$date}-{$file_name}.{$extension}";
                
                $media_manager->move_to_repository($new_file_name);
                $media_path = $media_manager->get_relative_path();
                $thumbnail  = $media_manager->get_thumbnail();
                $media_type = $media_manager->get_type();
                $mime_type  = $media_manager->get_final_mime_type();
                $dimensions = $media_manager->get_dimensions();
                $size       = $media_manager->get_size();
            }
            catch(\Exception $e)
            {
                return unindent(replace_escaped_vars(
                    $current_module->language->messages->media_manager_exception,
                    array('{$class}', '{$exception}'),
                    array($media_manager_class, $e->getMessage())
                ));
            }
        }
        
        $old_item = empty($data["id_media"]) ? null : $this->get($data["id_media"]);
        
        if( empty($item->id_media) )
        {
            $item->set_new_id();
            $item->type              = $media_type;
            $item->mime_type         = $mime_type;
            $item->dimensions        = $dimensions;
            $item->size              = $size;
            $item->path              = $media_path;
            $item->thumbnail         = $thumbnail;
            $item->id_author         = $account->id_account;
            $item->creation_date     = date("Y-m-d H:i:s");
            $item->creation_ip       = get_remote_address();
            $item->creation_host     = gethostbyaddr($item->creation_ip);
            $item->creation_location = forge_geoip_location($item->creation_ip);
            $item->last_update       = date("Y-m-d H:i:s");
        }
        
        # if( $item->main_category != $old_item->main_category )
        #     $this->unset_category($old_item->main_category, $item->id_media);
        # $this->set_category($item->main_category, $item->id_media);
        
        if( $item->status == "published" && $old_item->status != $item->status )
            $item->publishing_date = date("Y-m-d H:i:s");
        
        $tags = extract_hash_tags($item->title . " " . $item->description);
        $featured_posts_tag = $settings->get("modules:posts.featured_posts_tag");
        if(
            $account->level < config::MODERATOR_USER_LEVEL
            && $settings->get("modules:posts.show_featured_posts_tag_everywhere") != "true"
            && ! empty($featured_posts_tag)
            && in_array($featured_posts_tag, $tags)
        ) {
            unset($tags[array_search($featured_posts_tag, $tags)]);
            $item->title       = str_replace("#$featured_posts_tag", $featured_posts_tag, $item->title);
            $item->description = str_replace("#$featured_posts_tag", $featured_posts_tag, $item->description);
        }
        
        if( ! empty($tags) ) $this->set_tags($tags, $item->id_media);
        
        $this->save($item);
        
        if( $return_item_on_success )
            return $item;
        else
            return "OK";
    }
    
    /**
     * @param $id_media
     *
     * @return media_tag[]
     *
     * @throws \Exception
     */
    public function get_tags($id_media)
    {
        global $database;
        
        $res = $database->query("select * from media_tags where id_media = '$id_media'");
        $this->last_query = $database->get_last_query();
        
        if( $database->num_rows($res) == 0 ) return array();
        
        $rows = array();
        while($row = $database->fetch_object($res))
            $rows[$row->tag] = new media_tag($row);
        
        return $rows;
    }
    
    public function set_tags(array $list, $id_media)
    {
        global $database;
        
        $actual_tags = $this->get_tags($id_media);
        
        if( empty($actual_tags) && empty($list) ) return;
        
        $date = date("Y-m-d H:i:s");
        $inserts = array();
        $index   = 1;
        foreach($list as $tag)
        {
            if( ! isset($actual_tags[$tag]) ) $inserts[] = "('$id_media', '$tag', '$date', '$index')";
            unset($actual_tags[$tag]);
            $index++;
        }
        
        if( ! empty($inserts) )
        {
            $database->exec(
                "insert into media_tags (id_media, tag, date_attached, order_attached) values "
                . implode(", ", $inserts)
            );
            $this->last_query = $database->get_last_query();
        }
        
        if( ! empty($actual_tags) )
        {
            $deletes = array();
            foreach($actual_tags as $tag => $object) $deletes[] = "'$tag'";
            $database->exec(
                "delete from media_tags where id_media = '$id_media' and tag in (" . implode(", ", $deletes) . ")"
            );
            $this->last_query = $database->get_last_query();
        }
    }
    
    public function change_status($id_media, $new_status)
    {
        global $database;
        
        $res = $database->exec("update {$this->table_name} set status = '$new_status' where  id_media = '$id_media'");
        $this->last_query = $database->get_last_query();
        
        return $res;
    }
    
    public function add_tag($id_media, $tag, $date, $order)
    {
        global $database;
        
        $res = $database->exec("
            insert ignore into media_tags set
            id_media      = '$id_media',
            tag            = '$tag',
            date_attached  = '$date',
            order_attached = '$order'
        ");
        $this->last_query = $database->get_last_query();
        
        return $res;
    }
    
    public function delete_tag($id_media, $tag)
    {
        global $database;
        
        $res = $database->exec("
            delete from media_tags where
            id_media = '$id_media' and
            tag      = '$tag'
        ");
        $this->last_query = $database->get_last_query();
        
        return $res;
    }
    
    public function delete_tag_by_post($id_post, $tag)
    {
        global $database;
        
        $res = $database->exec("
            delete from media_tags where
            id_post = '$id_post' and
            tag     = '$tag'
        ");
        $this->last_query = $database->get_last_query();
        
        return $res;
    }
    
    public function increment_views($id_media)
    {
        global $database, $config;
        
        $cookie_key = "{$config->website_key}_lvm_{$id_media}";
        if( ! empty($_COOKIE[$cookie_key]) ) return 0;
        setcookie($cookie_key, $id_media, time() + 300, "/", $config->cookies_domain);
        
        $now = date("Y-m-d H:i:s");
        return $database->exec("
            update {$this->table_name} set
                views       = views + 1,
                last_viewed = '$now'
            where
                id_media = '$id_media'
        ");
    }
}
