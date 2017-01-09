<?php
namespace hng2_media;

use hng2_base\account;
use hng2_base\config;
use hng2_repository\abstract_record;

class media_record extends abstract_record
{
    public $id_media          ; #bigint unsigned not null default 0,
    public $id_author         ; #bigint unsigned not null default 0,
    
    # Path is relative to the /media_server directory, E.G.:
    # Relative> /year/month/username_mediatitle_randomseed.png
    # Absolute> /media_server/year/month/username_mediatitle_randomseed.png
    # /media_server is located in /data/uploaded_media
    public $path              ; #varchar(255) not null default '',
    public $type              ; #varchar(64) not null default '',
    public $mime_type         ; #varchar(255) not null default '',
    public $dimensions        ; #varchar(255) not null default '',
    public $size              ; #int unsigned not null default 0,
    public $title             ; #varchar(255) not null default '',
    public $thumbnail         ; #varchar(255) not null default '',
    
    public $description       ; #text,
    public $main_category     ; #varchar(32) not null default '',
    
    public $visibility        ; #enum('public', 'private', 'users', 'friends', 'level_based') not null default 'public',
    public $status            ; #enum('draft', 'published', 'reviewing', 'hidden', 'trashed') not null default 'draft',
    public $password          ; #varchar(32) not null default '',
    public $allow_comments    ; #tinyint unsigned not null default 1,
    
    public $creation_date     ; #datetime default null,
    public $creation_ip       ; #varchar(15) not null default '',
    public $creation_host     ; #varchar(255) not null default '',
    public $creation_location ; #varchar(255) not null default '',
    
    public $publishing_date   ; #datetime default null,
    public $comments_count    ; #int unsigned not null default 0,
    
    public $last_update       ; #datetime default null,
    public $last_commented    ; #datetime default null,
    
    # TODO:                                                                                                  
    # TODO:  IMPORTANT! All dinamically generated members should be undefined in get_for_database_insertion! 
    # TODO:                                                                                                  
    
    # Dynamically added:
    
    public $views;
    public $last_viewed;
    
    public $author_user_name;
    public $author_display_name;
    public $author_email;
    public $author_level;
    
    public $main_category_slug;
    public $main_category_title;
    public $main_category_visibility;
    public $main_category_min_level;
    
    # Taken with a group_concat from other tables:
    public $tags_list       = array(); # from post_tags
    public $categories_list = array(); # from post_categories
    public $mentions_list   = array(); # from post_mentions
    
    private $_author_account;
    
    protected function set_from_object($object_or_array)
    {
        parent::set_from_object($object_or_array);
        
        if( ! empty($this->_author_data) )
        {
            $parts = explode("\t", $this->_author_data);
            
            $this->author_user_name    = $parts[0];
            $this->author_display_name = $parts[1];
            $this->author_email        = $parts[2];
            $this->author_level        = $parts[3];
            
            unset($this->_author_data);
        }
        
        if( ! empty($this->_main_category_data) )
        {
            $parts = explode("\t", $this->_main_category_data);
            
            $this->main_category_slug  = $parts[0];
            $this->main_category_title = $parts[1];
            $this->main_category_visibility = $parts[2];
            $this->main_category_min_level  = $parts[3];
            
            unset($this->_main_category_data);
        }
        
        if( is_string($this->tags_list) )       $this->tags_list       = explode(",", $this->tags_list);
        if( is_string($this->categories_list) ) $this->categories_list = explode(",", $this->categories_list);
        if( is_string($this->mentions_list) )   $this->mentions_list   = explode(",", $this->mentions_list);
    }
    
    public function set_from_post()
    {
        parent::set_from_post();
        if( empty($this->visibility) ) $this->visibility = "public";
    }
    
    public function set_new_id()
    {
        $this->id_media = make_unique_id("30");
    }
    
    /**
     * @return object
     */
    public function get_for_database_insertion()
    {
        $return = (array) $this;
        
        unset(
            $return["views"],
            $return["last_view"],
            
            $return["author_user_name"],
            $return["author_display_name"],
            $return["author_email"],
            $return["author_level"],
            
            $return["main_category_slug"],
            $return["main_category_title"],
            $return["main_category_visibility"],
            $return["main_category_min_level"],
            
            $return["tags_list"],
            $return["categories_list"],
            $return["mentions_list"],
            
            $return["_author_account"]
        );
        
        foreach( $return as $key => &$val ) $val = addslashes($val);
        
        return (object) $return;
    }
    
    /**
     * @param null|account $prefetched_author_record
     */
    public function set_author($prefetched_author_record = null)
    {
        if( ! is_null($prefetched_author_record) )
            $this->_author_account = $prefetched_author_record;
        else
            $this->_author_account = new account($this->id_author);
    }
    
    /**
     * @return account
     */
    public function get_author()
    {
        // TODO: Implement accounts repository for caching
        
        if( is_object($this->_author_account) ) return $this->_author_account;
        
        return new account($this->id_author);
    }
    
    /**
     * Returns the title with all output processing.
     *
     * @param bool $include_autolinks If false, <a> tags wont be added. Useful when the title is inserted into an <a> tag.
     *
     * @return string
     */
    public function get_processed_title($include_autolinks = true)
    {
        global $config;
        
        $contents = $this->title;
        $contents = convert_emojis($contents);
        if( $include_autolinks ) $contents = autolink_hash_tags($contents, "{$config->full_root_path}/tag/", "/media");
        
        # TODO: Add get_processed_title() extension point
        
        return $contents;
    }
    
    /**
     * Returns the excerpt with all output processing.
     */
    public function get_processed_description()
    {
        global $config;
        
        $contents = $this->description;
        $contents = convert_emojis($contents);
        $contents = autolink_hash_tags($contents, "{$config->full_root_path}/tag/", "/media");
        
        # TODO: Add get_processed_description() extension point
        
        return $contents;
    }
    
    public function get_description_excerpt()
    {
        global $config, $settings;
        
        $contents = make_excerpt_of($this->description, $settings->get("modules:gallery.excerpt_length", 30));
        $contents = convert_emojis($contents);
        $contents = autolink_hash_tags($contents, "{$config->full_root_path}/tag/", "/media");
        
        return $contents;
    }
    
    /**
     * Returns the display name with all output processing
     */
    public function get_processed_author_display_name()
    {
        global $config, $modules;
        
        $contents = $this->author_display_name;
        $contents = convert_emojis($contents);
        
        $config->globals["processing_id_account"]  = $this->id_author;
        $config->globals["processing_contents"] = $contents;
        $modules["gallery"]->load_extensions("media_record_class", "get_processed_author_display_name");
        $contents = $config->globals["processing_contents"];
        unset( $config->globals["processing_contents"] );
        
        return $contents;
    }
    
    public function get_item_url($fully_qualified = false)
    {
        global $config;
        
        if( $fully_qualified ) $return = "$config->full_root_url/mediaserver/{$this->path}";
        else                   $return = "$config->full_root_path/mediaserver/{$this->path}";
        
        return $return;
    }
    
    public function get_page_url($fully_qualified = false)
    {
        global $config;
        
        if( $fully_qualified ) $return = "$config->full_root_url/media/{$this->id_media}";
        else                   $return = "$config->full_root_path/media/{$this->id_media}";
        
        return $return;
    }
    
    /**
     * Alias of get_page_url
     * 
     * @param bool $fully_qualified
     *
     * @return string
     */
    public function get_permalink($fully_qualified = false)
    {
        return $this->get_page_url($fully_qualified);
    }
    
    public function get_item_embeddable_url($fully_qualified = false)
    {
        global $config;
        
        if( $fully_qualified ) $return = "$config->full_root_url/mediaserver-embed/{$this->path}";
        else                   $return = "$config->full_root_path/mediaserver-embed/{$this->path}";
        
        return $return;
    }
    
    public function get_item_width()
    {
        return current(explode("x", $this->dimensions));
    }
    
    public function get_item_height()
    {
        return end(explode("x", $this->dimensions));
    }
    
    public function get_thumbnail_url($fully_qualified = false)
    {
        global $config;
        
        if( $fully_qualified ) $return = "$config->full_root_url/";
        else                   $return = "$config->full_root_path/";
        
        if( empty($this->thumbnail) ) $return .= "media/missing_image.png";
        else                          $return .= "mediaserver/{$this->thumbnail}";
        
        return $return;
    }
    
    public function can_be_edited()
    {
        global $account;
        
        if( ! $account->_exists ) return false;
        if( $this->status == "trashed" ) return false;
        if( $account->has_admin_rights_to_module("gallery") ) return true;
        if( $account->level >= config::MODERATOR_USER_LEVEL ) return true;
        if( $this->id_author != $account->id_account ) return false;
        if( $this->status == "draft" ) return true;
        
        return false;
    }
    
    public function get_filtered_tags_list()
    {
        global $config, $modules;
        
        $list = $this->tags_list;
        if( empty($list) ) return array();
        
        if( is_string($list) ) $list = explode(",", $list);
        
        $config->globals["media_record/filtering_tags_list"] = $list;
        foreach($modules as $module)
            if( ! empty($module->php_includes->media_record_filtering_tags_list) )
                include "{$module->abspath}/{$module->php_includes->media_record_filtering_tags_list}";
        $list = $config->globals["media_record/filtering_tags_list"];
        
        return $list;
    }
    
    public function can_be_deleted()
    {
        global $account;
        
        if( $account->has_admin_rights_to_module("gallery") ) return true;
        if( $account->level >= config::MODERATOR_USER_LEVEL ) return true;
        if( $account->level < config::AUTHOR_USER_LEVEL ) return false;
        if( $account->id_account != $this->id_author ) return false;
        if( $this->status == "trashed" ) return false;
        
        return true;
    }
}
