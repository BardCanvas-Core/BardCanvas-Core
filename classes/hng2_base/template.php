<?php
/**
 * Template controller class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base;

use hng2_cache\disk_cache;

class template
{
    public $name;
    
    public $abspath;
    
    public $url;
    
    private $page_title;
    
    public $page_requires_login = false;
    
    public $page_contents_include;
    
    protected $main_menu_items = array();
    
    public function __construct()
    {
        global $config, $settings;
        
        $this->name = $settings->get("engine.template");
        if( empty($this->name) ) $this->name = "base";
                
        $this->abspath = ABSPATH . "/templates/{$this->name}";
        
        if( ! is_dir($this->abspath) )
            throw new \RuntimeException("Template {$this->name} not found");
        
        $this->url = "{$config->full_root_path}/templates/{$this->name}";
    }
    
    public function add_menu_item($title, $html, $priority = 0)
    {
        $this->main_menu_items[] = (object) array(
            "title"    => $title,
            "priority" => $priority,
            "html"     => $html,
        );
    }
    
    /**
     * @param string $sort_by title|priority
     *
     * @return string
     */
    public function build_menu_items($sort_by = "title")
    {
        $items = array();
        if( empty($this->main_menu_items) ) return "";
        
        foreach($this->main_menu_items as $item)
        {
            $priority = sprintf("%06d", $item->priority);
            $title    = $item->title;
            if( $sort_by == "priority" ) $items["{$priority} {$title}"] = $item->html;
            else                         $items["{$title} {$priority}"] = $item->html;
        }
        
        ksort($items);
        return implode("\n", $items);
    }
    
    public function set_page_title($title)
    {
        $this->page_title = $title;
    }
    
    public function get_page_title()
    {
        global $settings, $config;
        
        return $this->page_title
            . " - "
            . $settings->get("engine.website_name")
            . " - "
            . "v" . $config->engine_version;
    }
}
