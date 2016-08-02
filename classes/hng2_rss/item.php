<?php
namespace hng2_rss;

class item
{
    public $title;
    public $link;
    public $description;
    
    public $author;
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
        /** @var \SimpleXMLElement $node */
        $node = $parent->addChild("item");
        
        if( ! empty($this->title)        ) $node->addChild("title",        $this->title);
        if( ! empty($this->link)         ) $node->addChild("link",         $this->link);
        if( ! empty($this->description)  ) $node->addChild("description",  $this->description);
        
        if( ! empty($this->guid) ) $node->addChild("guid",  $this->guid);
    }
}
