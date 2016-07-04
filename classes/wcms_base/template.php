<?php
/**
 * Template controller class
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace wcms_base;

use wcms_cache\disk_cache;

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
        global $_ROOT_URL, $settings;
        
        $this->name = $settings->get("engine.template");
        if( empty($this->name) ) $this->name = "base";
                
        $this->abspath = ABSPATH . "/templates/{$this->name}";
        
        if( ! is_dir($this->abspath) )
            throw new \RuntimeException("Template {$this->name} not found");
        
        $this->url = $_ROOT_URL . "/templates/{$this->name}";
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
    
    public function render_internals()
    {
        global $database, $global_start_time;
        
        echo "<div class='internals framed_content' align='center'>";
        
        if( defined("ENABLE_QUERY_TRACKING") && ENABLE_QUERY_TRACKING )
            echo "
                <span class='framed_content'>
                    DB queries: " . number_format($database->get_tracked_queries_count()) . "
                </span>
                •
            ";
        
        echo "
            <span class='framed_content'>
                Time consumption: " . number_format(microtime(true) - $global_start_time, 3) . "s
            </span>
            •
        ";
        
        echo "
            <span class='framed_content'>
                RAM used: " . number_format(memory_get_usage(true) / 1024 / 1024, 1) . "MiB
            </span>
        ";
        
        echo "</div>";
        
        if( defined("DISPLAY_PERFORMANCE_DETAILS") && DISPLAY_PERFORMANCE_DETAILS )
        {
            $this->render_database_details();
            $this->render_mem_cache_details();
            $this->render_disk_cache_details();
        }
    }
    
    protected function render_database_details()
    {
        global $database;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        $total_time   = 0;
        $rows_fetched = 0;
        foreach($database->get_tracked_queries() as $query)
        {
            $execution_time = number_format($query->execution_time * 1000, 3);
            if( $execution_time < 1 ) $execution_time = "&lt;1";
            
            if( ENABLE_QUERY_BACKTRACE )
                $backtrace = "<td><pre style='margin: 0'>" . implode("\n", $query->backtrace) . "</pre></td>";
            
            $output .= "
                <tr>
                    <td align='right'>{$seq}</td>
                    <td>{$query->host_and_db}</td>
                    <td class='fixed_font'>{$query->query}</td>
                    <td align='right'>{$query->rows_in_result}</td>
                    <td align='right'>{$execution_time}</td>
                    {$backtrace}
                </tr>
            ";
    
            $rows_fetched += $query->rows_in_result;
            $total_time   += $query->execution_time;
            $seq++;
        }
        
        $backtrace_th  = ENABLE_QUERY_BACKTRACE ? "<th>Backtrace</th>" : "";
        $backtrace_tf  = ENABLE_QUERY_BACKTRACE ? "<td>&nbsp;</td>"    : "";
        
        if( $total_time < 0.001 )
            $time_consumed = "&lt;1ms";
        else
            $time_consumed = number_format($total_time, 3) . "s";
        
        echo "
            <div class='internals'>
                <section>
                    <h2>Database statistics</h2>
                    <div class='framed_content table_wrapper'>
                        <table class='nav_table'>
                            <thead>
                            <tr>
                                <th>Call #</th>
                                <th>Host/DB</th>
                                <th>Query</th>
                                <th>Rows</th>
                                <th>Time (MS)</th>
                                {$backtrace_th}
                            </tr>
                            </thead>
                            <tbody>
                                {$output}
                            </tbody>
                            <tfoot>
                            <tr>
                                <td align='right' colspan='3'>Total rows fetched &amp; time consumed by database querys:</td>
                                <td align='right'>{$rows_fetched}</td>
                                <td align='right'>{$time_consumed}</td>
                                {$backtrace_tf}
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            </div>
        ";
    }
    
    protected function render_mem_cache_details()
    {
        global $mem_cache;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        foreach($mem_cache->get_hits() as $hit)
        {
            if( ENABLE_QUERY_BACKTRACE )
                $backtrace = "<td><pre style='margin: 0'>" . implode("\n", $hit->backtrace) . "</pre></td>";
            
            $output .= "
                <tr>
                    <td align='right'>{$seq}</td>
                    <td>{$hit->type}</td>
                    <td align='right'>{$hit->timestamp}</td>
                    <td>{$hit->key}</td>
                    {$backtrace}
                </tr>
            ";
            $seq++;
        }
        
        $backtrace_th  = ENABLE_QUERY_BACKTRACE ? "<th>Backtrace</th>" : "";
        
        echo "
            <div class='internals'>
                <section>
                    <h2>Memory cache hits</h2>
                    <div class='framed_content table_wrapper'>
                        <table class='nav_table'>
                            <thead>
                            <tr>
                                <th>Call #</th>
                                <th>Type</th>
                                <th>Timestamp</th>
                                <th>Key</th>
                                {$backtrace_th}
                            </tr>
                            </thead>
                            <tbody>
                                {$output}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        ";
    }
    
    protected function render_disk_cache_details()
    {
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        foreach(disk_cache::get_hits() as $hit)
        {
            if( ENABLE_QUERY_BACKTRACE )
                $backtrace = "<td><pre style='margin: 0'>" . implode("\n", $hit->backtrace) . "</pre></td>";
            
            $output .= "
                <tr>
                    <td align='right'>{$seq}</td>
                    <td>{$hit->file}</td>
                    <td>{$hit->type}</td>
                    <td align='right'>{$hit->timestamp}</td>
                    <td>{$hit->key}</td>
                    {$backtrace}
                </tr>
            ";
            $seq++;
        }
        
        $backtrace_th  = ENABLE_QUERY_BACKTRACE ? "<th>Backtrace</th>" : "";
        
        echo "
            <div class='internals'>
                <section>
                    <h2>Disk cache hits</h2>
                    <div class='framed_content table_wrapper'>
                        <table class='nav_table'>
                            <thead>
                            <tr>
                                <th>Call #</th>
                                <th>File</th>
                                <th>Type</th>
                                <th>Timestamp</th>
                                <th>Key</th>
                                {$backtrace_th}
                            </tr>
                            </thead>
                            <tbody>
                                {$output}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        ";
    }
}
