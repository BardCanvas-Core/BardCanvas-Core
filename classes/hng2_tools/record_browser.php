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
     */
    public function build_vars($default_limit = 20, $default_order = 1)
    {
        $return = array();
        
        if( $_REQUEST["mode"] == "set_filter" )
        {
            foreach($_REQUEST as $key => $val)
            {
                if(stristr($key, "search_") !== false)
                {
                    if(is_array($val)) $_SESSION["{$this->data_vars_prefix}_nav_filter_".$key] = implode(",", $val);
                    else               $_SESSION["{$this->data_vars_prefix}_nav_filter_".$key] = $val;
                }
            }
            if( ! is_numeric($_REQUEST["limit"]) ) $_REQUEST["limit"] = $default_limit;
            $_SESSION["{$this->data_vars_prefix}_nav_limit"] = $_REQUEST["limit"];
            $_SESSION["{$this->data_vars_prefix}_nav_order"] = $_REQUEST["order"];
        }
        
        foreach($_SESSION as $key => $val)
        {
            if(stristr($key, "{$this->data_vars_prefix}_nav_filter_") !== false)
            {
                $substracted_key = str_replace("{$this->data_vars_prefix}_nav_filter_", "", $key);
                $return[$substracted_key] = $val;
            }
        }
        
        $return["offset"] = empty($_REQUEST["offset"])                              ? 0  : $_REQUEST["offset"];
        $return["limit"]  = empty($_SESSION["{$this->data_vars_prefix}_nav_limit"]) ? $default_limit : $_SESSION["{$this->data_vars_prefix}_nav_limit"];
        $return["order"]  = empty($_SESSION["{$this->data_vars_prefix}_nav_order"]) ? $default_order : $_SESSION["{$this->data_vars_prefix}_nav_order"];
        
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
            if( $node->layout["xclass"] )  $item->xnowrap = trim($node->layout["xclass"]);
            
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
            
            $return[] = $item;
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
        $total_pages      = floor($total_records / $limit);
        $this_page_number = ($total_pages+1)-floor(($total_records-$offset) / $limit);
        if($total_records % $limit > 0) $total_pages++;
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
            "previous_page"      => $pagina_anterior,
            "start_offset"       => $start_offset,
            "end_offset"         => $end_offset,
            "offset_start_point" => $offset_start_point,
            "next_page"          => $pagina_siguiente,
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
}
