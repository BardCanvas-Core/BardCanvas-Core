<?php
namespace hng2_base\repository;

use hng2_base\account;

class media_record extends abstract_record
{
    public $id_media          ; #varchar(32) not null default '',
    public $id_author         ; #varchar(32) not null default '',
    
    # Path is relative to the /media_server directory, E.G.:
    # Relative> /year/month/username_mediatitle_randomseed.png
    # Absolute> /media_server/year/month/username_mediatitle_randomseed.png
    # /media_server is located in /data/uploaded_media
    public $path              ; #varchar(255) not null default '',
    public $content_type      ; #varchar(64) not null default '',
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
    public $views             ; #int unsigned not null default 0,
    public $comments_count    ; #int unsigned not null default 0,
    public $tags              ; #varchar(255) not null default '',
    
    public $last_update       ; #datetime default null,
    public $last_viewed       ; #datetime default null,
    public $last_commented    ; #datetime default null,
    
    # TODO:                                                                                                  
    # TODO:  IMPORTANT! All dinamically generated members should be undefined in get_for_database_insertion! 
    # TODO:                                                                                                  
    
    # Dynamically added:
    public $author_user_name;
    public $author_display_name;
    public $author_email;
    public $author_level;
    
    public $main_category_slug;
    public $main_category_title;
    
    # Taken with a group_concat from other tables:
    public $tags_list       = array(); # from post_tags
    public $categories_list = array(); # from post_categories
    public $mentions_list   = array(); # from post_mentions
    
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
        
        if( is_string($this->tags_list) )       $this->tags_list       = explode(",", $this->tags_list);
        if( is_string($this->categories_list) ) $this->categories_list = explode(",", $this->categories_list);
        if( is_string($this->mentions_list) )   $this->mentions_list   = explode(",", $this->mentions_list);
    }
    
    public function set_new_id()
    {
        $this->id_media = uniqid();
    }
    
    /**
     * @return object
     */
    public function get_for_database_insertion()
    {
        $return = (array) $this;
        
        unset(
            $return["author_user_name"],
            $return["author_display_name"],
            $return["author_email"],
            $return["author_level"],
            
            $return["main_category_slug"],
            $return["main_category_title"],
            
            $return["tags_list"],
            $return["categories_list"],
            $return["mentions_list"]
        );
        
        foreach( $return as $key => &$val ) $val = addslashes($val);
        
        return (object) $return;
    }
    
    /**
     * @return account
     */
    public function get_author()
    {
        // TODO: Implement accounts repository for caching
        
        return new account($this->id_author);
    }
    
    /**
     * Returns the title with all output processing.
     */
    public function get_processed_title()
    {
        $contents = $this->title;
        $contents = convert_emojis($contents);
        
        # TODO: Add get_processed_title() extension point
        
        return $contents;
    }
    
    /**
     * Returns the excerpt with all output processing.
     */
    public function get_processed_description()
    {
        $contents = $this->description;
        $contents = convert_emojis($contents);
        
        # TODO: Add get_processed_description() extension point
        
        return $contents;
    }
    
    public function get_image_url()
    {
        global $config;
        
        $return = "$config->full_root_url/mediaserver/{$this->path}";
        
        return $return;
    }
    
    public function get_thumbnail_url()
    {
        global $config;
        
        $return = "$config->full_root_url/";
        
        if( empty($this->thumbnail) ) $return .= "media/missing_image.png";
        else                          $return .= "mediaserver/{$this->thumbnail}";
        
        return $return;
    }
}
