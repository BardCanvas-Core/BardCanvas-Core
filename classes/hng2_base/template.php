<?php
/**
 * Template controller class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_base;

class template
{
    public $name;
    
    public $abspath;
    
    public $url;
    
    private $page_title;
    
    public $page_requires_login = false;
    
    public $page_contents_include;
    
    protected $main_menu_items = array();
    
    protected $left_sidebar_groups = array();
    
    protected $right_sidebar_items = array();
    
    public $layout;
    
    protected $vars = array();
    
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
    
    public function init($calling_layout)
    {
        $layout = preg_replace('/\.php|\.inc/i', "", basename($calling_layout));
        $this->layout = $layout;
    }
    
    public function add_menu_item($title, $html, $priority = 100)
    {
        $this->main_menu_items[] = (object) array(
            "title"    => trim($title),
            "priority" => $priority,
            "html"     => trim($html),
        );
    }
    
    public function add_left_sidebar_group($title, $html, $priority = 100)
    {
        $this->left_sidebar_groups[] = (object) array(
            "title"    => trim($title),
            "priority" => $priority,
            "html"     => trim($html),
        );
    }
    
    public function add_right_sidebar_item($title, $html, $priority = 100)
    {
        $this->right_sidebar_items[] = (object) array(
            "title"    => trim($title),
            "priority" => $priority,
            "html"     => trim($html),
        );
    }
    
    /**
     * @param string $sort_by title|priority
     *
     * @return string
     */
    public function build_menu_items($sort_by = "title")
    {
        return $this->build_items_collection($this->main_menu_items, $sort_by);
    }
    
    /**
     * @param string $sort_by (nothing)|title|priority
     *
     * @return string
     */
    public function build_left_sidebar_groups($sort_by = "")
    {
        return $this->build_items_collection($this->left_sidebar_groups, $sort_by);
    }
    
    /**
     * @param string $sort_by (nothing)|title|priority
     *
     * @return string
     */
    public function build_right_sidebar_items($sort_by = "")
    {
        return $this->build_items_collection($this->right_sidebar_items, $sort_by);
    }
    
    public function count_menu_items()
    {
        return count($this->main_menu_items);
    }
    
    public function count_left_sidebar_groups()
    {
        return count($this->left_sidebar_groups);
    }
    
    public function count_right_sidebar_items()
    {
        return count($this->right_sidebar_items);
    }
    
    /**
     * @param        $collection
     * @param string $sort_by (nothing)|title|priority
     *
     * @return string
     */
    private function build_items_collection($collection, $sort_by)
    {
        $items = array();
        if( empty($collection) ) return "";
        
        $index = 100000;
        foreach($collection as $item)
        {
            $priority = sprintf("%06d", $item->priority);
            $title    = $item->title;
            if( $sort_by == "priority" ) $items["{$priority} {$title} {$index}"] = $item->html;
            elseif( $sort_by == "title") $items["{$title} {$priority} {$index}"] = $item->html;
            else                         $items[]                       = $item->html;
            $index++;
        }
        
        if( ! empty($sort_by) ) ksort($items);
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
    
    public function set($var_name, $value)
    {
        $this->vars[$var_name] = $value;
    }
    
    public function append($var_name, $value)
    {
        $this->vars[$var_name] .= $value;
    }
    
    public function get($var_name)
    {
        return $this->vars[$var_name];
    }
}
