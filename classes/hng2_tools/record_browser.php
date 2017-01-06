<?php
/**
 * Database browser helper
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

namespace hng2_tools;

class record_browser
{
    private $data_vars_prefix;
    
    public function __construct($data_vars_prefix)
    {
        $this->data_vars_prefix = $data_vars_prefix;
    }
    
    /**
     * @param int $default_limit
     * @param int $default_order
     *
     * @return array
     * 
     * @throws \Exception
     */
    public function build_vars($default_limit = 20, $default_order = 1)
    {
        global $account;
        
        if( empty($this->data_vars_prefix) ) throw new \Exception("Data vars empty. Can't build navigation vars.");
        
        if( $account->_exists ) return $this->build_vars_in_account_prefs($default_limit, $default_order);
        else                    return $this->build_vars_in_cookies($default_limit, $default_order);
    }
    
    /**
     * @param int $default_limit
     * @param int $default_order
     *
     * @return array
     */
    private function build_vars_in_cookies($default_limit, $default_order)
    {
        global $config;
        
        $return = array();
        
        if( $_REQUEST["mode"] == "set_filter" )
        {
            //foreach($_REQUEST as $key => $val)
            //{
            //    if(stristr($key, "search_") !== false)
            //    {
            //        $value = is_array($val) ? implode(",", $val) : $val;
            //        
            //        if( $_COOKIE["{$this->data_vars_prefix}_nav_filter_{$key}"] != $value )
            //            setcookie(
            //                "{$this->data_vars_prefix}_nav_filter_{$key}",
            //                $value,
            //                time() + (86400 * 30),
            //                $config->full_root_path,
            //                $config->cookies_domain
            //            );
            //    }
            //}
            
            if( ! is_numeric($_REQUEST["limit"]) ) $_REQUEST["limit"] = $default_limit;
            
            if( $_REQUEST["limit"] != $_COOKIE["{$this->data_vars_prefix}_nav_limit"])
                setcookie(
                    "{$this->data_vars_prefix}_nav_limit",
                    $_REQUEST["limit"],
                    time() + (86400 * 30),
                    $config->full_root_path,
                    $config->cookies_domain
                );
    
            if( $_REQUEST["order"] != $_COOKIE["{$this->data_vars_prefix}_nav_order"])
                setcookie(
                    "{$this->data_vars_prefix}_nav_order",
                    $_REQUEST["order"],
                    time() + (86400 * 30),
                    $config->full_root_path,
                    $config->cookies_domain
                );
        }
        
        //foreach($_COOKIE as $key => $val)
        //{
        //    if(stristr($key, "{$this->data_vars_prefix}_nav_filter_") !== false)
        //    {
        //        $substracted_key = str_replace("{$this->data_vars_prefix}_nav_filter_", "", $key);
        //        $return[$substracted_key] = $val;
        //    }
        //}
        
        $saved_limit = $_COOKIE["{$this->data_vars_prefix}_nav_limit"];
        $saved_order = $_COOKIE["{$this->data_vars_prefix}_nav_order"];
        
        $return["offset"] = empty($_REQUEST["offset"]) ? 0 : $_REQUEST["offset"];
        $return["limit"]  = empty($saved_limit) ? $default_limit : $saved_limit;
        $return["order"]  = empty($saved_order) ? $default_order : $saved_order;
        
        foreach($_REQUEST as $key => $val)
            if( substr($key, 0, 7) == "search_" )
                $return[$key] = stripslashes($val);
        
        return $return;
    }
    
    /**
     * @param int $default_limit
     * @param int $default_order
     *
     * @return array
     */
    private function build_vars_in_account_prefs($default_limit, $default_order)
    {
        global $account;
        
        $return = array();
        
        if( $_REQUEST["mode"] == "set_filter" )
        {
            //foreach($_REQUEST as $key => $val)
            //{
            //    if(stristr($key, "search_") !== false)
            //    {
            //        $value = is_array($val) ? implode(",", $val) : $val;
            //        
            //        if( $account->engine_prefs["{$this->data_vars_prefix}_nav_filter_{$key}"] != $value )
            //            $account->set_engine_pref( "{$this->data_vars_prefix}_nav_filter_{$key}", $value );
            //    }
            //}
            
            if( ! is_numeric($_REQUEST["limit"]) ) $_REQUEST["limit"] = $default_limit;
            
            if( $account->engine_prefs["{$this->data_vars_prefix}_nav_limit"] != $_REQUEST["limit"] )
                $account->set_engine_pref( "{$this->data_vars_prefix}_nav_limit", $_REQUEST["limit"] );
    
            if( $account->engine_prefs["{$this->data_vars_prefix}_nav_order"] != $_REQUEST["order"] )
                $account->set_engine_pref( "{$this->data_vars_prefix}_nav_order", $_REQUEST["order"] );
        }
        
        //foreach($account->engine_prefs as $key => $val)
        //{
        //    if(stristr($key, "{$this->data_vars_prefix}_nav_filter_") !== false)
        //    {
        //        $substracted_key = str_replace("{$this->data_vars_prefix}_nav_filter_", "", $key);
        //        $return[$substracted_key] = $val;
        //    }
        //}
        
        $saved_limit = $account->engine_prefs["{$this->data_vars_prefix}_nav_limit"];
        $saved_order = $account->engine_prefs["{$this->data_vars_prefix}_nav_order"];
        
        $return["offset"] = empty($_REQUEST["offset"]) ? 0 : $_REQUEST["offset"];
        $return["limit"]  = empty($saved_limit) ? $default_limit : $saved_limit;
        $return["order"]  = empty($saved_order) ? $default_order : $saved_order;
        
        foreach($_REQUEST as $key => $val)
            if( substr($key, 0, 7) == "search_" )
                $return[$key] = stripslashes($val);
        
        return $return;
    }
    
    /**
     * @param \SimpleXMLElement $source_language_node
     *
     * @return object[]
     */
    public function build_table_header($source_language_node)
    {
        $return = array();
        
        $children = $source_language_node->children();
        if( empty($children) ) return array();
        
        /** @var \SimpleXMLElement $node */
        foreach($children as $node)
        {
            if( empty($node->caption) ) continue;
            
            $item = (object) array( "content" => trim($node->caption));
            if( $node->layout["xalign"] )  $item->xalign  = trim($node->layout["xalign"]);
            if( $node->layout["xwidth"] )  $item->xwidth  = trim($node->layout["xwidth"]);
            if( $node->layout["xnowrap"] ) $item->xnowrap = trim($node->layout["xnowrap"]) == "true";
            if( $node->layout["xclass"] )  $item->xclass  = trim($node->layout["xclass"]);
            
            if( $node->order_asc )
                $item->sort_asc = (object) array(
                    "enabled" => true,
                    "order"   => trim($node->order_asc["id"]),
                    "alt"     => trim($node->order_asc)
                );
            
            if( $node->order_desc )
                $item->sort_desc = (object) array(
                    "enabled" => true,
                    "order"   => trim($node->order_desc["id"]),
                    "alt"     => trim($node->order_desc)
                );
            
            $name          = $node->getName();
            $return[$name] = $item;
        }
        
        return $return;
    }
    
    /**
     * @param int $record_count
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function build_pagination($record_count, $limit, $offset)
    {
        $total_records    = $record_count;
        
        if( $limit == 0 )
        {
            $total_pages      = 1;
            $this_page_number = 1;
        }
        else
        {
            $total_pages      = floor($total_records / $limit);
            $this_page_number = ($total_pages+1)-floor(($total_records-$offset) / $limit);
            if($total_records % $limit > 0) $total_pages++;
        }
        
        $pagina_anterior    = $offset - $limit;
        $pagina_siguiente   = $offset + $limit;
        $ultima_pagina      = $limit * ($total_pages-1);
        if ( $total_pages <= 10 ) {
            $start_offset       = 1;
            $end_offset         = $total_pages;
            $offset_start_point = 0;
        } else {
            if ( $this_page_number < 5 ) {
                $start_offset       = 1;
                $end_offset         = 10;
                $offset_start_point = 0;
            } else {
                $start_offset       = $this_page_number - 5;
                $end_offset         = $this_page_number + 5;
                $offset_start_point = ($start_offset-1) * $limit;
            }
        }
        if( $end_offset == 0 ) $end_offset = $this_page_number = $pagina_siguiente = $pagina_final = $total_pages = 1;
        if( $end_offset > $total_pages ) $end_offset = $total_pages;
        
        return array(
            "this_page_number"   => $this_page_number,
            "total_pages"        => $total_pages,
            "total_records"      => $total_records,
            "previous_page"      => $pagina_anterior > 0 ? $pagina_anterior : 0,
            "start_offset"       => $start_offset,
            "end_offset"         => $end_offset,
            "offset_start_point" => $offset_start_point,
            "next_page"          => $total_pages > 1 ? $pagina_siguiente : 0,
            "last_page"          => $ultima_pagina,
            "limit"              => $limit,
        );
    }
    
    public function render_pagination_controls($pagination_function_name, array $pagination_vars)
    {
        $backward_disabled = $pagination_vars["this_page_number"] == 1 ? "disabled" : "";
        $next_disabled     = $pagination_vars["this_page_number"] >= $pagination_vars["total_pages"] ? "disabled" : "";
        $last_disabled     = $pagination_vars["total_pages"] == 1 || $pagination_vars["this_page_number"] == $pagination_vars["total_pages"] ? "disabled" : "";
        
        if($pagination_vars["total_pages"] < 1)
        {
            $middle_buttons = "<button disabled>1</button>";
        }
        else
        {
            $middle_buttons = "";
            $offset_start_point = $pagination_vars["offset_start_point"];
            for( $cpage = $pagination_vars["start_offset"]; $cpage <= $pagination_vars["end_offset"]; $cpage++ )
            {
                if( $cpage <= 0)
                {
                    $offset_start_point += $pagination_vars["limit"];
                    continue;
                }
    
                $disabled = $cpage == $pagination_vars["this_page_number"] ? "disabled" : "";
                $middle_buttons .= "
                    <button {$disabled} onclick='{$pagination_function_name}({$offset_start_point})'>{$cpage}</button>
                ";
                $offset_start_point += $pagination_vars["limit"];
            }
        }
        
        echo "
            <button {$backward_disabled} onclick='{$pagination_function_name}(0);'>
                <span class='fa fa-fw fa-step-backward'></span>
            </button>
            
            <button {$backward_disabled} onclick='{$pagination_function_name}({$pagination_vars["previous_page"]});'>
                <span class='fa fa-fw fa-caret-left'></span>
            </button>
            
            {$middle_buttons}
            
            <button {$next_disabled} onclick='{$pagination_function_name}({$pagination_vars["next_page"]});'>
                <span class='fa fa-fw fa-caret-right'></span>
            </button>
            
            <button {$last_disabled} onclick='{$pagination_function_name}({$pagination_vars["last_page"]});'>
                <span class='fa fa-fw fa-step-forward'></span>
            </button>
        ";
    }
    
    public function render_pagination_links($url_prefix, array $pagination_vars)
    {
        $backward_disabled = $pagination_vars["this_page_number"] == 1 ? "disabled" : "";
        $next_disabled     = $pagination_vars["this_page_number"] >= $pagination_vars["total_pages"] ? "disabled" : "";
        $last_disabled     = $pagination_vars["total_pages"] == 1 || $pagination_vars["this_page_number"] == $pagination_vars["total_pages"] ? "disabled" : "";
        
        $query = stristr($url_prefix, "?") === false ? "?" : "&";
        
        if($pagination_vars["total_pages"] < 1)
        {
            $middle_buttons = "<a disabled href='{$url_prefix}{$query}offset=0'>1</a>";
        }
        else
        {
            $middle_buttons = "";
            $offset_start_point = $pagination_vars["offset_start_point"];
            for( $cpage = $pagination_vars["start_offset"]; $cpage <= $pagination_vars["end_offset"]; $cpage++ )
            {
                if( $cpage <= 0)
                {
                    $offset_start_point += $pagination_vars["limit"];
                    continue;
                }
                
                $disabled = $cpage == $pagination_vars["this_page_number"] ? "disabled" : "";
                $middle_buttons .= "
                    <a {$disabled} href='{$url_prefix}{$query}offset={$offset_start_point}'>{$cpage}</a>
                ";
                $offset_start_point += $pagination_vars["limit"];
            }
        }
        
        echo "
            <a {$backward_disabled} href='{$url_prefix}{$query}offset=0'>
                <span class='fa fa-fw fa-step-backward'></span>
            </a>
            
            <a {$backward_disabled} href='{$url_prefix}{$query}offset={$pagination_vars["previous_page"]}'>
                <span class='fa fa-fw fa-caret-left'></span>
            </a>
            
            {$middle_buttons}
            
            <a {$next_disabled} href='{$url_prefix}{$query}offset={$pagination_vars["next_page"]}'>
                <span class='fa fa-fw fa-caret-right'></span>
            </a>
            
            <a {$last_disabled} href='{$url_prefix}{$query}offset={$pagination_vars["last_page"]}'>
                <span class='fa fa-fw fa-step-forward'></span>
            </a>
        ";
    }
    
    public function get_pagination_button(
        $which_button, $pagination_function_name, array $pagination_vars, $override_icon = "", $added_caption = ""
    ) {
        $backward_disabled = $pagination_vars["this_page_number"] == 1 ? "disabled" : "";
        $next_disabled     = $pagination_vars["this_page_number"] >= $pagination_vars["total_pages"] ? "disabled" : "";
        $last_disabled     = $pagination_vars["total_pages"] == 1 || $pagination_vars["this_page_number"] == $pagination_vars["total_pages"] ? "disabled" : "";
        
        $icon = empty($override_icon) ? "fa-step-backward" : $override_icon;
        if( $which_button == "first" ) return "
            <button {$backward_disabled} onclick='{$pagination_function_name}(0);'>
                <span class='fa fa-fw {$icon}'></span> {$added_caption}
            </button>
        ";
    
        $icon = empty($override_icon) ? "fa-caret-left" : $override_icon;
        if( $which_button == "previous" ) return "
            <button {$backward_disabled} onclick='{$pagination_function_name}({$pagination_vars["previous_page"]});'>
                <span class='fa fa-fw {$icon}'></span> {$added_caption}
            </button>
        ";
    
        $icon = empty($override_icon) ? "fa-caret-right" : $override_icon;
        if( $which_button == "next" ) return "
            <button {$next_disabled} onclick='{$pagination_function_name}({$pagination_vars["next_page"]});'>
                <span class='fa fa-fw {$icon}'></span> {$added_caption}
            </button>
        ";
    
        $icon = empty($override_icon) ? "fa-step-forward" : $override_icon;
        if( $which_button == "last" ) return "
            <button {$last_disabled} onclick='{$pagination_function_name}({$pagination_vars["last_page"]});'>
                <span class='fa fa-fw {$icon}'></span> {$added_caption}
            </button>
        ";
        
        throw new \Exception("Invalid pagination button invoked");
    }
}
