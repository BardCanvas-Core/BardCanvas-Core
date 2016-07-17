<?php
/**
 * Internals renderer
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_tools;

use hng2_cache\disk_cache;

class internals
{
    public static function render($referer)
    {
        global $database, $global_start_time, $config;
    
        if( ! $config->display_performance_details ) return;
        
        echo "<div class='internals framed_content state_active' style='margin-left: 0; margin-right: 0;'>";
            
            echo "<div class='internals framed_content' align='center'>";
                echo "
                    <span class='framed_content state_highlight'>
                        Results for: <b>" . basename($referer) . "</b>
                    </span>
                ";
                if( $config->query_tracking_enabled )
                    echo "
                        <span class='framed_content'>
                            DB queries: " . number_format($database->get_tracked_queries_count()) . "
                        </span>
                    ";
                echo "
                    <span class='framed_content'>
                        Time consumption: " . number_format(microtime(true) - $global_start_time, 3) . "s
                    </span>
                    <span class='framed_content'>
                        RAM used: " . number_format(memory_get_usage(true) / 1024 / 1024, 1) . "MiB
                    </span>
                ";
            echo "</div>";
            
            self::render_database_details();
            self::render_mem_cache_details();
            self::render_disk_cache_details();
            
        echo "</div>";
    }
    
    private static function render_database_details()
    {
        global $database, $account, $config;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        $total_time   = 0;
        $rows_fetched = 0;
        foreach($database->get_tracked_queries() as $query)
        {
            $execution_time = number_format($query->execution_time * 1000, 3);
            if( $execution_time < 1 ) $execution_time = "&lt;1";
            
            if( $config->query_backtrace_enabled )
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
        
        $backtrace_th  = $config->query_backtrace_enabled ? "<th>Backtrace</th>" : "";
        $backtrace_tf  = $config->query_backtrace_enabled ? "<td>&nbsp;</td>"    : "";
        
        if( $total_time < 0.001 )
            $time_consumed = "&lt;1ms";
        else
            $time_consumed = number_format($total_time, 3) . "s";
        
        $table_style    = $account->engine_prefs["internals_db_stats_hidden"] == "true" ? "display: none;" : "";
        $new_state      = $account->engine_prefs["internals_db_stats_hidden"] == "true" ? "" : "true";
        $expand_style   = $account->engine_prefs["internals_db_stats_hidden"] == "true" ? "" : "display: none";
        $collapse_style = $account->engine_prefs["internals_db_stats_hidden"] == "true" ? "display: none" : "";
        
        $seq--;
        echo "
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_db_stats_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        Database statistics
                        ({$seq})
                    </h2>
                    <div class='framed_content hideable table_wrapper' style='{$table_style}'>
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
    
    private static function render_mem_cache_details()
    {
        global $mem_cache, $account, $config;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        foreach($mem_cache->get_hits() as $hit)
        {
            if( $config->query_backtrace_enabled )
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
        
        $backtrace_th   = $config->query_backtrace_enabled ? "<th>Backtrace</th>" : "";
        $table_style    = $account->engine_prefs["internals_memcache_stats_hidden"] == "true" ? "display: none;" : "";
        $new_state      = $account->engine_prefs["internals_memcache_stats_hidden"] == "true" ? "" : "true";
        $expand_style   = $account->engine_prefs["internals_memcache_stats_hidden"] == "true" ? "" : "display: none";
        $collapse_style = $account->engine_prefs["internals_memcache_stats_hidden"] == "true" ? "display: none" : "";
        
        $seq--;
        echo "
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_memcache_stats_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        Memory cache hits
                        ({$seq})
                    </h2>
                    <div class='framed_content hideable table_wrapper' style='{$table_style}'>
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
    
    private static function render_disk_cache_details()
    {
        global $account, $config;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        foreach(disk_cache::get_hits() as $hit)
        {
            if( $config->query_backtrace_enabled )
                $backtrace = "<td><pre style='margin: 0'>" . implode("\n", (array) $hit->backtrace) . "</pre></td>";
            
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
        
        $backtrace_th   = $config->query_backtrace_enabled ? "<th>Backtrace</th>" : "";
        $table_style    = $account->engine_prefs["internals_diskcache_stats_hidden"] == "true" ? "display: none;" : "";
        $new_state      = $account->engine_prefs["internals_diskcache_stats_hidden"] == "true" ? "" : "true";
        $expand_style   = $account->engine_prefs["internals_diskcache_stats_hidden"] == "true" ? "" : "display: none";
        $collapse_style = $account->engine_prefs["internals_diskcache_stats_hidden"] == "true" ? "display: none" : "";
        
        $seq--;
        echo "
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_diskcache_stats_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        Disk cache hits
                        ({$seq})
                    </h2>
                    <div class='framed_content hideable table_wrapper' style='{$table_style}'>
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
