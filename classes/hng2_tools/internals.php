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
use hng2_cache\object_cache;
use SqlFormatter;

class internals
{
    public static function render($referer)
    {
        global $database, $global_start_time, $config, $account, $template;
        
        if( ! $config->display_performance_details ) return;
        
        if( ! EMBED_INTERNALS )
        {
            ob_start();
            
            $USER_DATA
                = "<span class='framed_content inlined'>" 
                . get_user_ip() 
                . "</span> <span class='framed_content inlined'>"
                . gethostbyaddr(get_user_ip())
                . "</span> <span class='framed_content inlined'>"
                . forge_geoip_location()
                . "</span> <span class='framed_content inlined'>"
                . ($account->_exists ? "{$account->display_name} (#{$account->id_account})" : "Guest")
                . "</span>"
            ;
            
            $wasuuup = md5(mt_rand(1, 65535));
            echo "<!DOCTYPE html>
                <html>
                <head>
                <!-- Common -->
                <script type=\"text/javascript\" src=\"{$config->full_root_path}/lib/jquery-1.11.0.min.js\"></script>
                <script type=\"text/javascript\" src=\"{$config->full_root_path}/lib/jquery-migrate-1.2.1.js\"></script>
                <script type=\"text/javascript\"          src=\"{$config->full_root_path}/lib/jquery-ui-1.10.4.custom.min.js\"></script>
                <link rel=\"stylesheet\" type=\"text/css\" href=\"{$config->full_root_path}/lib/jquery-ui-themes-1.10.4/blitzer/jquery-ui.css\">
                <link rel=\"stylesheet\" type=\"text/css\" href=\"{$config->full_root_path}/lib/font-awesome-4.6.3/css/font-awesome.css\">
                <link rel=\"stylesheet\" type=\"text/css\" href=\"{$config->full_root_path}/lib/emojione-2.2.5/css/emojione-awesome.css\">
                
                <!-- Core functions and styles -->
                <link rel=\"stylesheet\" type=\"text/css\" href=\"{$config->full_root_path}/media/styles~v{$config->scripts_version}.css\">
                <link rel=\"stylesheet\" type=\"text/css\" href=\"{$config->full_root_path}/media/admin~v{$config->scripts_version}.css\">
                <script type=\"text/javascript\"          src=\"{$config->full_root_path}/media/functions~v{$config->scripts_version}.js\"></script>
                
                <!-- This template -->
                <link rel=\"stylesheet\" type=\"text/css\" href=\"{$template->url}/media/styles~v{$config->scripts_version}.css\">
                </head>
                <body class=\"popup\">
                <div id=\"body_wrapper\">
                    <div id=\"content\">
                        <h1 align='center'>
                            Internals report for \"{$template->get_page_title()}\"
                            &nbsp;•&nbsp;
                            <a href='./?wasuuup=$wasuuup'><span class='fa fa-reply fa-fw'></span>Go back to index</a>
                        </h1>
                        <div class='framed_content' align='center'>
                            {$USER_DATA}
                        </div>
            ";
        }
        
        $style = EMBED_INTERNALS ? "display: none;" : "";
        echo "<div class='internals framed_content state_active' style='$style'>";
            
            echo "<div class='internals framed_content' align='center'>";
                echo "
                    <span class='framed_content state_highlight'>
                        Results for: <b>" . basename($referer) . "</b>
                    </span>
                ";
                echo "
                    <span class='framed_content state_highlight'>
                        URI: <b>" . htmlspecialchars($_SERVER["REQUEST_URI"]) . "</b>
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
            
            self::render_globals();
            self::render_database_details();
            self::render_object_cache_details();
            self::render_mem_cache_details();
            self::render_disk_cache_details();
            
        echo "</div>";
        
        if( ! EMBED_INTERNALS )
        {
            echo "
                </div><!-- /#content -->
                </div><!-- /#body_wrapper -->
                </body>
                </html>
            ";
            $output = ob_get_clean();
            
            $dir = "{$config->logfiles_location}/internals";
            if( ! is_dir($dir) )
            {
                if( ! @mkdir($dir) ) throw new \Exception("Can't create $dir");
                @chmod($dir, 0777);
            }
            
            $x = array(
                "/" => "•",
                "*" => "☼",
                "?" => "≈",
                "&" => "│",
                "|" => "",
                "[" => "(",
                "]" => ")",
                "{" => "(",
                "}" => ")",
                " " => ".",
            );
            
            $userinfo = $account->_exists ? "{$account->user_name},{$account->level}" : "guest";
            $date     = date("YmdHis.") . sprintf("%03.0f", end(explode(".", microtime(true))));
            $file     = $dir . "/$date - " . str_replace(array_keys($x), $x, $_SERVER["REQUEST_URI"]) . " - {$userinfo}.html";
            @file_put_contents($file, $output);
            @chmod($file, 0777);
        }
    }
    
    private static function render_globals()
    {
        global $config, $account;
        
        if( empty($config->globals["internals:debug_info"]) ) return;
        
        $table_style    = $account->engine_prefs["internals_globals_hidden"] == "true" ? "display: none;" : "";
        $new_state      = $account->engine_prefs["internals_globals_hidden"] == "true" ? "" : "true";
        $expand_style   = $account->engine_prefs["internals_globals_hidden"] == "true" ? "" : "display: none";
        $collapse_style = $account->engine_prefs["internals_globals_hidden"] == "true" ? "display: none" : "";
        $wasuuup        = md5(mt_rand(1, 65535));
        
        $output = "";
        foreach($config->globals["internals:debug_info"] as $key => $val)
            $output .= "
                <section>
                    <h3>$key</h3>
                    <pre class='framed_content' style='margin-bottom: 10px;'>" . print_r($val, true) . "</pre>
                </section>
            ";
        
        echo "
            <a name='debug_info'></a>
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_globals_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        <a href='?wasuuup={$wasuuup}#debug_info'>Debug info</a>
                    </h2>
                    <div class='framed_content hideable' style='{$table_style}'>
                        {$output}
                    </div>
                </section>
            </div>
        ";
    }
    
    private static function render_database_details()
    {
        global $database, $account, $config;
        
        $formatter = new SqlFormatter();
        
        $querys = $database->get_tracked_queries();
        if( count($querys) == 0 ) return;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        $total_time   = 0;
        $rows_fetched = 0;
        
        foreach($querys as $query)
        {
            $execution_time = number_format($query->execution_time * 1000, 3);
            if( $execution_time < 1 ) $execution_time = "&lt;1";
        
            if( $config->query_backtrace_enabled )
                $backtrace = "<td><pre style='margin: 0'>" . implode("\n", $query->backtrace) . "</pre></td>";
        
            $output .= "
                <tr>
                    <td align='right'>{$seq}</td>
                    <td>{$query->host_and_db}</td>
                    <td class='fixed_font scrollable'>{$formatter->format($query->query)}</td>
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
        $wasuuup        = md5(mt_rand(1, 65535));
        
        $seq--;
        echo "
            <a name='db_stats'></a>
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_db_stats_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        <a href='?wasuuup={$wasuuup}#db_stats'>Database statistics</a>
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
    
    private static function render_object_cache_details()
    {
        global $account, $config;
        
        $hits = object_cache::get_hits();
        if( count($hits) == 0 ) return;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        foreach($hits as $hit)
        {
            if( $config->query_backtrace_enabled )
                $backtrace = "<td><pre style='margin: 0'>" . implode("\n", (array) $hit->backtrace) . "</pre></td>";
        
            $output .= "
                <tr>
                    <td align='right'>{$seq}</td>
                    <td>{$hit->owner}</td>
                    <td>{$hit->type}</td>
                    <td align='right'>{$hit->timestamp}</td>
                    <td>{$hit->key}</td>
                    {$backtrace}
                </tr>
            ";
            $seq++;
        }
        
        $backtrace_th   = $config->query_backtrace_enabled ? "<th>Backtrace</th>" : "";
        $table_style    = $account->engine_prefs["internals_objectcache_stats_hidden"] == "true" ? "display: none;" : "";
        $new_state      = $account->engine_prefs["internals_objectcache_stats_hidden"] == "true" ? "" : "true";
        $expand_style   = $account->engine_prefs["internals_objectcache_stats_hidden"] == "true" ? "" : "display: none";
        $collapse_style = $account->engine_prefs["internals_objectcache_stats_hidden"] == "true" ? "display: none" : "";
        $wasuuup        = md5(mt_rand(1, 65535));
        
        $seq--;
        echo "
            <a name='object_cache'></a>
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_objectcache_stats_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        <a href='?wasuuup={$wasuuup}#object_cache'>Object (RAM) cache hits</a>
                        ({$seq})
                    </h2>
                    <div class='framed_content hideable table_wrapper' style='{$table_style}'>
                        <table class='nav_table'>
                            <thead>
                            <tr>
                                <th>Call #</th>
                                <th>Repository</th>
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
    
    private static function render_mem_cache_details()
    {
        global $mem_cache, $account, $config;
        
        $keys           = $mem_cache->get_all_keys();
        $table_style    = $account->engine_prefs["internals_memcache_keys_hidden"] == "true" ? "display: none;" : "";
        $new_state      = $account->engine_prefs["internals_memcache_keys_hidden"] == "true" ? "" : "true";
        $expand_style   = $account->engine_prefs["internals_memcache_keys_hidden"] == "true" ? "" : "display: none";
        $collapse_style = $account->engine_prefs["internals_memcache_keys_hidden"] == "true" ? "display: none" : "";
        $wasuuup        = md5(mt_rand(1, 65535));
        
        $lis = "";
        foreach($keys as $key)
        {
            $val = $mem_cache->get($key, true);
            if( ! is_string($val) ) $val = "(object|array)";
            $lis .= "<li>$key := <span class='framed_content inlined'>$val</span></li>\n";
        }
        
        echo "
            <a name='mem_keys'></a>
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_memcache_keys_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        <a href='?wasuuup={$wasuuup}#mem_keys'>MemCache keys</a>
                    </h2>
                    <div class='framed_content hideable' style='{$table_style}'>
                        <ul>
                            $lis
                        </ul>
                    </div>
                </section>
            </div>
        ";
        
        $hits = $mem_cache->get_hits();
        if( count($hits) == 0 ) return;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        foreach($hits as $hit)
        {
            if( $config->query_backtrace_enabled )
                $backtrace = "<td><pre style='margin: 0'>" . (is_array($hit->backtrace) ? implode("\n", $hit->backtrace) : "&mdash;") . "</pre></td>";
            
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
        $wasuuup        = md5(mt_rand(1, 65535));
        
        $seq--;
        echo "
            <a name='mem_hits'></a>
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_memcache_stats_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        <a href='?wasuuup={$wasuuup}#mem_hits'>MemCache hits</a>
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
        
        $hits = disk_cache::get_hits();
        if( count($hits) == 0 ) return;
        
        $backtrace    = "";
        $output       = "";
        $seq          = 1;
        foreach($hits as $hit)
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
        $wasuuup        = md5(mt_rand(1, 65535));
        
        $seq--;
        echo "
            <a name='disk_cache'></a>
            <div class='internals'>
                <section>
                    <h2>
                        <span class='toggler' onclick='$(this).find(\"span\").toggle(); $(this).closest(\"section\").find(\".hideable\").toggle(); set_engine_pref(\"internals_diskcache_stats_hidden\", \"{$new_state}\")'>
                            <span class='fa pseudo_link fa-caret-right fa-border fa-fw' style='{$expand_style}'></span>
                            <span class='fa pseudo_link fa-caret-down  fa-border fa-fw' style='{$collapse_style}'></span>
                        </span>
                        <a href='?wasuuup={$wasuuup}#disk_cache'>Disk cache hits</a>
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
