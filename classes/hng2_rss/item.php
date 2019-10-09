<?php
namespace hng2_rss;

class item
{
    public $title;
    public $link;
    public $description;
    
    public $author;
    public $creator;
    public $category;
    public $comments;
    
    /**
     * @var enclosure
     */
    public $enclosure;
    
    public $guid;
    public $pubDate;
    public $source;
    
    /**
     * @param \SimpleXMLElement $parent
     */
    public function add_to($parent)
    {
        global $settings;
        
        /** @var \SimpleXMLElement $node */
        $node = $parent->addChild("item");
        
        $webmaster_email = $settings->get("engine.webmaster_address");
        $website_name    = $settings->get("engine.website_name");
        
        if( ! empty($this->title)        ) add_cdata_node("title",       $this->title, $node);
        if( ! empty($this->link)         ) $node->addChild("link",       $this->link);
        if( ! empty($this->description)  ) add_cdata_node("description", $this->description, $node);
        
        if( ! empty($this->author)   ) $node->addChild("author",     $this->author);
        if( ! empty($this->category) ) $node->addChild("category",   $this->category);
        if( ! empty($this->comments) ) $node->addChild("comments",   $this->link); // Yes, the same as link
        
        if( ! empty($this->enclosure) )
        {
            $enclosure = $node->addChild("enclosure");
            $enclosure->addAttribute("type",   $this->enclosure->type);
            $enclosure->addAttribute("length", $this->enclosure->length);
            $enclosure->addAttribute("url",    $this->enclosure->url);
        }
        
        if( ! empty($this->guid)    ) $node->addChild("guid",    $this->guid);
        if( ! empty($this->pubDate) ) $node->addChild("pubDate", $this->pubDate);
    }
}
