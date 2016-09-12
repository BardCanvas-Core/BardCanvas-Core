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
        return implode("", $items);
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
    
    /**
     * Sets/replaces a variable in the vars collection
     * 
     * @param $var_name
     * @param $value
     */
    public function set($var_name, $value)
    {
        $this->vars[$var_name] = $value;
    }
    
    /**
     * APPENDS to a variable in the vars collection
     * 
     * @param $var_name
     * @param $value
     */
    public function append($var_name, $value)
    {
        $this->vars[$var_name] .= $value;
    }
    
    public function get($var_name)
    {
        return $this->vars[$var_name];
    }
    
    /**
     * Must be called within the pre_render stage, after the page_tag has been set.
     *
     * @param string $layout_settings_key Settings variable containing the layout
     * @param string $template_file       Name of the segments/file.tpl template for each sidebar item 
     * @param string $use_case            left_sidebar|right_sidebar
     */
    public function prepare_widgets($layout_settings_key, $template_file, $use_case)
    {
        global $settings;
        
        $layout = $settings->get($layout_settings_key);
        if( empty($layout) ) return;
        
        foreach( explode("\n", $layout) as $entry )
        {
            list($id, $seed, $title, $user_scope, $page_scope, $pages_list) = explode("|", trim($entry));
            $id         = trim($id);
            $seed       = trim($seed);
            $title      = trim($title);
            $user_scope = trim($user_scope);
            $page_scope = trim($page_scope);
            $pages_list = trim($pages_list);
            
            $pages_list = empty($pages_list) ? array() : explode(",", $pages_list);
            
            $res = $this->get_widget_contents(
                $id, $seed, $title, $user_scope, $page_scope, $pages_list, $template_file, $use_case
            );
            
            if( is_null($res) ) continue;
            
            if(     $use_case == "left_sidebar"  ) $this->add_left_sidebar_group($res->title, $res->content);
            elseif( $use_case == "right_sidebar" ) $this->add_right_sidebar_item($res->title, $res->content);
        }
    }
    
    /**
     * @param        $id            module.id
     * @param        $seed
     * @param        $title
     * @param string $user_scope    all|online|offline
     * @param string $page_scope    show|hide
     * @param array  $pages_list    May contain any of the cases below:
     *                              home,
     *                              post_author_index,  post_category_index,  post_archive
     *                              media_author_index, media_category_index, media_archive
     *                              TODO: add support for search_results, user_home, user_mentions,
     * @param string $template_file Name of the segments/file.tpl template for each sidebar item
     * @param string $use_case      "for" attribute used in the widget definition (in module_settings.xml)
     *
     * @return null|object {title:string, content:string}
     */
    private function get_widget_contents(
        $id, $seed, $title, $user_scope, $page_scope, $pages_list, $template_file, $use_case
    ) {
        global $modules, $account;
        
        $current_page_tag = $this->get("page_tag");
        $template_file    = "{$this->abspath}/segments/{$template_file}";
        
        if( ! is_file($template_file) )
        {
            $message = replace_escaped_vars(
                $modules["widgets_manager"]->language->messages->template_file_not_found,
                array('{$file}'),
                array( $template_file )
            );
            
            return (object) array(
                "title"   => $title,
                "content" => "<div class='framed_content state_ko'><span class='fa fa-warning'></span> {$message}</div>",
            );
        }
        
        $content_template = file_get_contents($template_file);
        
        list($module_name, $id) = explode(".", $id);
        
        if( ! isset($modules[$module_name]) )
        {
            $message = replace_escaped_vars(
                $modules["widgets_manager"]->language->messages->module_not_found,
                array('{$module_name}', '{$id}', '{$title}'),
                array( $module_name, $id, $title )
            );
            
            return (object) array(
                "title"   => $title,
                "content" => "<div class='framed_content state_ko'><span class='fa fa-warning'></span> {$message}</div>",
            );
        }
        
        $module = $modules[$module_name];
        
        if( empty($module->widgets) ) return null;
        
        $matches = $module->widgets->xpath("//widget[@for='$use_case'][@id='$id']");
        
        if( empty($matches) ) return null;
        
        /** @var \SimpleXMLElement $widget */
        $widget = current($matches);
        
        # Filtering by user case
        if( $user_scope == "online"  && ! $account->_exists ) return null;
        if( $user_scope == "offline" &&   $account->_exists ) return null;
        
        # Filtering by page tag
        if( ! empty($pages_list) )
        {
            if( $page_scope == "show" && ! in_array($current_page_tag, $pages_list) ) return null;
            if( $page_scope == "hide" &&   in_array($current_page_tag, $pages_list) ) return null;
        }
        
        $content = $this->build_widget_contents($module, $widget, $seed);
        
        if( empty($content) ) return null;
        
        $content = replace_escaped_vars(
            $content_template,
            array('{$title}', '{$content}', '{$added_classes}'),
            array(  $title,     $content,     trim($widget["added_classes"]))
        );
        
        return (object) array(
            "title"   => $title,
            "content" => $content,
        );
    }
    
    /**
     * @param module            $this_module
     * @param \SimpleXMLElement $widget
     * @param string            $seed
     * 
     * @return string
     */
    private function build_widget_contents($this_module, $widget, /** @noinspection PhpUnusedParameterInspection */ $seed)
    {
        global $modules;
        
        /** @noinspection PhpUnusedLocalVariableInspection */
        $data_key = "_right_sidebar_widgets:{$this_module->name}.{$widget["id"]}" . (empty($seed) ? "" : "_{$seed}");
        
        if( $widget["type"] == "php" )
        {
            $include = "{$this_module->abspath}/widgets/{$widget["file"]}";
            
            if( ! is_file($include) )
            {
                $message = replace_escaped_vars(
                    $modules["widgets_manager"]->language->messages->widget_file_not_found,
                    array('{$file}', '{$module}', '{$widget}'),
                    array( $widget["file"], $this_module->name, $widget["id"] )
                );
                
                return "
                    <div class='framed_content state_ko'>
                        <span class='fa fa-warning'></span>
                        {$message}
                    </div>
                ";
            }
    
            /** @noinspection PhpUnusedLocalVariableInspection */
            $template = $this;
            return include($include);
        }
        
        return "
            <div class='framed_content state_ko'>
                <span class='fa fa-warning'></span>
                {$modules["widgets_manager"]->language->messages->rs_widget_type_not_supported}
            </div>
        ";
    }
    
    public function render_tinymce_additions()
    {
        global $modules, $config, $account;
        
        foreach($modules as $module)
        {
            if( empty($module->tinymce_additions) ) continue;
            $min_level = trim($module->tinymce_additions["minimum_user_level"]);
            if( ! empty($min_level) && ! $account->_exists ) continue;
            if( ! empty($min_level) && $account->level < $min_level ) continue;
            
            foreach($module->tinymce_additions->plugin as $plugin)
                echo "
                    
                    <script type='text/javascript' src='{$config->full_root_path}/{$module->name}/{$plugin}'></script>
                    <script type='text/javascript'>
                        tinymce_custom_plugins[tinymce_custom_plugins.length] = '{$plugin["name"]}';
                    </script>
                ";
    
            if( ! empty($module->tinymce_additions->css) )
                echo "
                    <script type='text/javascript'>
                        tinymce_default_css_files[tinymce_default_css_files.length]
                            = '{$config->full_root_path}/{$module->name}/{$module->tinymce_additions->css}';
                    </script>        
                ";
            
            if( ! empty($module->tinymce_additions->toolbar) )
                echo "
                    <script type='text/javascript'>    
                        tinymce_custom_toolbar_buttons[tinymce_custom_toolbar_buttons.length] = '{$module->tinymce_additions->toolbar}';
                    </script>
                ";
            
        }
    }
    
    public function render_always_on_files()
    {
        global $config;
        
        if( empty($this->vars["always_on_files"]) ) return;
        
        foreach($this->vars["always_on_files"] as $file)
        {
            $parts = explode(".", $file);
            $ext   = array_pop($parts);
            $name  = implode(".", $parts);
            $file  = "{$name}~v{$config->scripts_version}.{$ext}";
            
            if( strtolower($ext) == "js" )
                echo "<script type='text/javascript' src='$file'></script>";
            else
                echo "<link rel='stylesheet' type='text/css' href='$file'>";
        }
    }
}
