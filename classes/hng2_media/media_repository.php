<?php
namespace hng2_media;

use hng2_base\account;
use hng2_base\account_record;
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
        # Views
        "( select views
           from media_views where media_views.id_media = media.id_media
           ) as views",
        "( select last_viewed
           from media_views where media_views.id_media = media.id_media
           ) as last_viewed",
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
     * @param array $ids
     *
     * @return media_record[]
     */
    public function get_multiple(array $ids)
    {
        if( count($ids) == 0 ) return array();
        
        $prepared_ids = array();
        foreach($ids as $id) $prepared_ids[] = "'$id'";
        $prepared_ids = implode(", ", $prepared_ids);
        
        $res = $this->find(array("id_media in ($prepared_ids)"), 0, 0, "");
        if( count($res) == 0 ) return array();
        
        $return = array();
        foreach($res as $item) $return[$item->id_media] = $item;
        
        return $return;
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
                comments_count   ,
                
                last_update      ,
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
                '{$obj->comments_count   }',
                
                '{$obj->last_update      }',
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
    
    public function delete($key)
    {
        return $this->trash($key);
    }
    
    public function trash($id_media)
    {
        global $database;
        
        $database->exec("delete from media_tags where id_media = '$id_media'");
        
        $res  = $database->exec("
            update media set
                status   = 'trashed'
            where
                id_media = '$id_media'
        ");
        
        if( $res > 0 )
        {
            $item = $this->get($id_media);
            $this->hide_files(array($item->path, $item->thumbnail));
        }
        
        return $res;
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
        $find_params->limit = 30;
        
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
        global $modules, $config;
        
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
        
        $config->globals["author_ids"] = $author_ids;
        $modules["gallery"]->load_extensions("posts_repository_class", "preload_authors");
    }
    
    public function get_grouped_tag_counts($since = "", $min_hits = 10, $limit = 0)
    {
        global $database, $settings;
        
        $min_hits = empty($min_hits) ? 10 : $min_hits;
        $having   = $min_hits == 1   ? "" : "having `count` >= '$min_hits'";
        $limit    = empty($limit) ? "" : "limit $limit";
        
        if( empty($since) )
            $query = "
                select tag, count(tag) as `count` from media_tags
                group by tag
                $having
                order by tag asc
                $limit
            ";
        else
            $query = "
                select tag, count(tag) as `count` from media_tags
                where date_attached >= '{$since}'
                group by tag
                $having
                order by tag asc
                $limit
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
     * Receives an uploaded media item and process it for saving.
     *
     * @param array                       $data                   actual or forged $_POST array
     * @param array                       $file                   an item from $_FILES
     * @param bool                        $return_item_on_success If true, the item object will be returned instead of "OK".
     * @param bool                        $fake_file_upload       If true, the file will be treated as if it was uploaded.
     *                                                            Useful for programmatic imports.
     * @param null|account|account_record $owner                  If not specified, it will default to the current user
     *                                                            Useful for programmatic imports.
     * @param bool                        $do_save                Usually set to true. Set to false if you're saving it elsewhere.
     *                                                            Useful for programmatic imports.
     *
     * @return media_record|string "OK" or error message or object
     */
    public function receive_and_save(
        $data, $file, $return_item_on_success = false, $fake_file_upload = false, $owner = null, $do_save = true
    ) {
        global $modules, $config, $settings, $account;
        
        if( is_null($owner) ) $owner = $account;
        
        $current_module = $modules["gallery"];
        
        $res = $this->check_directories();
        if( ! empty($res) ) return $res;
        
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
                $item->title = stripslashes($owner->display_name . " - " . $file["name"]);
                
                if( $this->get_record_count(array("title" => addslashes($item->title))) )
                    return trim($current_module->language->messages->item_exists);
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
            if( ! $fake_file_upload )
                if( ! is_uploaded_file($file["tmp_name"]) )
                    return trim($current_module->language->messages->invalid->upload);
            
            $extension = strtolower(end(explode(".", $file["name"])));
            if( empty($extension) ) return trim($current_module->language->messages->invalid->file);
            
            if( ! isset($config->upload_file_types[$extension]) )
                return trim($current_module->language->messages->invalid->file);
            
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
                $file_name     = wp_sanitize_filename(implode(".", $parts));
                $date          = date("dHis");
                $new_file_name = "{$owner->user_name}-{$date}-{$file_name}.{$extension}";
                
                $media_manager->move_to_repository($new_file_name);
                $media_path = $media_manager->get_relative_path();
                $thumbnail  = $media_manager->get_thumbnail();
                $media_type = $media_manager->get_type();
                $mime_type  = $media_manager->get_final_mime_type();
                $dimensions = $media_manager->get_dimensions();
                $size       = $media_manager->get_size();
                if( file_exists($file["tmp_name"]) ) @unlink($file["tmp_name"]);
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
            $item->id_author         = $owner->id_account;
            $item->creation_date     = date("Y-m-d H:i:s");
            $item->last_update       = date("Y-m-d H:i:s");
            
            if( ! $fake_file_upload )
            {
                $item->creation_ip       = get_remote_address();
                $item->creation_host     = gethostbyaddr($item->creation_ip);
                $item->creation_location = forge_geoip_location($item->creation_ip);
            }
        }
        
        # if( $item->main_category != $old_item->main_category )
        #     $this->unset_category($old_item->main_category, $item->id_media);
        # $this->set_category($item->main_category, $item->id_media);
        
        if( $item->status == "published" && $old_item->status != $item->status )
            $item->publishing_date = date("Y-m-d H:i:s");
        
        $tags = extract_hash_tags($item->title . " " . $item->description);
        $featured_posts_tag = $settings->get("modules:posts.featured_posts_tag");
        if(
            ($owner->level < config::MODERATOR_USER_LEVEL && ! $owner->has_admin_rights_to_module("gallery") )
            && $settings->get("modules:posts.show_featured_posts_tag_everywhere") != "true"
            && ! empty($featured_posts_tag)
            && in_array($featured_posts_tag, $tags)
        ) {
            unset($tags[array_search($featured_posts_tag, $tags)]);
            $item->title       = str_replace("#$featured_posts_tag", $featured_posts_tag, $item->title);
            $item->description = str_replace("#$featured_posts_tag", $featured_posts_tag, $item->description);
        }
        
        if( $do_save ) $this->set_tags($tags, $item->id_media);
        if( $do_save ) $this->save($item);
        
        if( $return_item_on_success )
            return $item;
        else
            return "OK";
    }
    
    private function check_directories()
    {
        global $config, $modules;
        
        $current_module = $modules["gallery"];
        
        $dirs = array(
            "{$config->datafiles_location}/uploaded_media/",
            "{$config->datafiles_location}/uploaded_media/" . date("Y"),
            "{$config->datafiles_location}/uploaded_media/" . date("Y/m"),
            "{$config->datafiles_location}/uploaded_media/" . date("Y/m/d"),
        );
        
        foreach($dirs as $dir)
        {
            if( ! is_dir($dir) )
            {
                if( ! @mkdir($dir, 0777, true) )
                    return trim($current_module->language->messages->cannot_create_directory);
                
                @chmod($dir, 0777);
            }
        }
        
        return "";
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
        
        if( in_array($new_status, array("published", "reviewing")) &&  $res > 0 )
        {
            $item = $this->get($id_media);
            $this->unhide_files(array($item->path, $item->thumbnail));
        }
        
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
        setcookie($cookie_key, $id_media, time() + 60, "/", $config->cookies_domain);
        
        $now = date("Y-m-d H:i:s");
        return $database->exec("
            insert into media_views (
                id_media,
                views,
                last_viewed
            ) values (
                '$id_media',
                1,
                '$now'
            ) on duplicate key update
                views       = views + 1,
                last_viewed = '$now'
        ");
    }
    
    public function delete_multiple_if_unused(array $ids)
    {
        global $database, $modules, $config;
        
        $rows = $this->get_multiple($ids);
        
        foreach($ids as &$id) $id = "'$id'";
        $prepared_ids = implode(", ", $ids);
        $query = "
            update media set status = 'trashed'
            where id_media in ({$prepared_ids})
        ";
        
        $config->globals["media_repository/delete_multiple_if_unused:ids"] = $prepared_ids;
        $config->globals["media_repository/delete_multiple_if_unused:query"] = $query;
        foreach($modules as $module)
            if( ! empty($module->php_includes->media_repository_delete_multiple_if_unused) )
                include "{$module->abspath}/{$module->php_includes->media_repository_delete_multiple_if_unused}";
        $query = $config->globals["media_repository/delete_multiple_if_unused:query"];
        unset( $config->globals["media_repository/delete_multiple_if_unused:query"] );
        
        $res = $database->exec($query);
        $this->last_query = $database->get_last_query();
        
        if( ! empty($rows) )
        {
            $files = array();
            foreach($rows as $row)
            {
                $files[] = $row->path;
                $files[] = $row->thumbnail;
            }
            $this->hide_files($files);
        }
        
        return $res;
    }
    
    public function hide_all_published_by_auhtor($id_author)
    {
        global $database;
        
        $res = $database->query("
            select id_media, path, thumbnail from {$this->table_name}
            where status = 'published' and id_author = '$id_author'
        ");
        $files = array();
        while($row = $database->fetch_object($res))
        {
            $files[] = $row->path;
            $files[] = $row->thumbnail;
        }
        
        $query = "
            update {$this->table_name} set status = 'hidden'
            where status = 'published' and id_author = '$id_author'
        ";
        $this->last_query = $database->get_last_query();
        $this->hide_files($files);
        
        return $database->exec($query);
    }
    
    public function unhide_all_published_by_auhtor($id_author)
    {
        global $database;
        
        $res = $database->query("
            select id_media, path, thumbnail from {$this->table_name}
            where status = 'hidden' and id_author = '$id_author'
        ");
        $files = array();
        while($row = $database->fetch_object($res))
        {
            $files[] = $row->path;
            $files[] = $row->thumbnail;
        }
        
        $query = "
            update {$this->table_name} set status = 'published'
            where status = 'hidden' and id_author = '$id_author'
        ";
        $this->last_query = $database->get_last_query();
        $this->unhide_files($files);
        
        return $database->exec($query);
    }
    
    private function hide_files(array $list)
    {
        global $config;
        
        if( count($list) == 0 ) return;
        
        $fails = array();
        foreach($list as $file)
        {
            $source = "{$config->datafiles_location}/uploaded_media/{$file}";
            $target = "{$config->datafiles_location}/uploaded_media/{$file}.hidden";
            
            $res = $this->do_hide_unhide($source, $target);
            if( ! empty($res) ) $fails[] = $res;
        }
        
        if( empty($fails) ) return;
        
        $backtrace = debug_backtrace();
        foreach($backtrace as &$backtrace_item)
            $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        
        # $this->notify_fileops_errors($fails, $backtrace);
    }
    
    private function unhide_files(array $list)
    {
        global $config;
        
        if( count($list) == 0 ) return;
        
        $fails = array();
        foreach($list as $file)
        {
            $source = "{$config->datafiles_location}/uploaded_media/{$file}.hidden";
            $target = "{$config->datafiles_location}/uploaded_media/{$file}";
            
            $res = $this->do_hide_unhide($source, $target);
            if( ! empty($res) ) $fails[] = $res;
        }
        
        //if( empty($fails) ) return;
        //
        //$backtrace = debug_backtrace();
        //foreach($backtrace as &$backtrace_item)
        //    $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        //
        //$this->notify_fileops_errors($fails, $backtrace);
    }
    
    private function notify_fileops_errors($fails, $backtrace = array())
    {
        global $language, $settings;
        
        $subject = replace_escaped_vars( $language->file_ops->fails_subject, '{$website_name}', $settings->get("engine.website_name") );
        $message = replace_escaped_vars( $language->file_ops->fails_notification, '{$errors}', implode("<br>\n", $fails) );
        
        $webmaster_mail = ucwords($settings->get("engine.webmaster_address"));
        $webmaster_name = current(explode("@", $webmaster_mail));
        $recipients     = array($webmaster_name => $webmaster_mail);
        
        if( empty($backtrace) ) $backtrace = "N/A";
        else                    $backtrace = implode("<br>\n", $backtrace);
        $message = replace_escaped_vars( $message, '{$stack_trace}', $backtrace );
        
        send_mail($subject, $message, $recipients);
    }
    
    private function do_hide_unhide($source, $target)
    {
        global $language;
        
        if( ! file_exists($source) )
            return replace_escaped_vars(
                $language->file_ops->source_not_found,
                array('{$source}', '{$target}'),
                array($source, $target)
            );
        
        if( file_exists($target) )
            return replace_escaped_vars(
                $language->file_ops->target_exists,
                array('{$source}', '{$target}'),
                array($source, $target)
            );
        
        if( ! @rename($source, $target) )
            return replace_escaped_vars(
                $language->file_ops->cant_move,
                array('{$source}', '{$target}'),
                array($source, $target)
            );
        
        return "";
    }
    
    public function empty_trash()
    {
        global $database, $modules;
        
        $boundary = date("Y-m-d 00:00:00", strtotime("today - 7 days"));
        
        $database->exec("
          delete from media_categories where id_media in (
            select id_media from media where status = 'trashed'
            and creation_date < '$boundary'
          )
        ");
        
        $database->exec("
          delete from media_mentions where id_media in (
            select id_media from media where status = 'trashed'
            and creation_date < '$boundary'
          )
        ");
        
        $database->exec("
          delete from media_tags where id_media in (
            select id_media from media where status = 'trashed'
            and creation_date < '$boundary'
          )
        ");
        
        $database->exec("
          delete from media_views where id_media in (
            select id_media from media where status = 'trashed'
            and creation_date < '$boundary'
          )
        ");
        
        foreach($modules as $module)
            if( ! empty($module->php_includes->media_repository_empty_trash) )
                include "{$module->abspath}/{$module->php_includes->media_repository_empty_trash}";
        
        $res = $database->query("
            select path, thumbnail from {$this->table_name}
            where status = 'trashed' and creation_date < '$boundary'
        ");
        if( $database->num_rows($res) == 0 ) return;
        
        $files = array();
        while( $row = $database->fetch_object($res) )
        {
            $files[] = $row->path;
            $files[] = $row->thumbnail;
        }
        $this->delete_files($files);
        
        $database->exec("
          delete from media where status = 'trashed'
          and creation_date < '$boundary'
        ");
    }
    
    private function delete_files(array $list)
    {
        global $config;
        
        if( count($list) == 0 ) return;
        
        $fails = array();
        foreach($list as $file)
        {
            $normal = "{$config->datafiles_location}/uploaded_media/{$file}";
            $hidden = "{$config->datafiles_location}/uploaded_media/{$file}.hidden";
            
            if( is_file($normal) )
                if( ! @unlink($normal) ) $fails[] = $normal;
            
            if( is_file($hidden) )
                if( ! @unlink($hidden) ) $fails[] = $hidden;
        }
        
        //if( empty($fails) ) return;
        //
        //$backtrace = debug_backtrace();
        //foreach($backtrace as &$backtrace_item)
        //    $backtrace_item = $backtrace_item["file"] . ":" . $backtrace_item["line"];
        //
        //$this->notify_fileops_errors($fails, $backtrace);
    }
}
