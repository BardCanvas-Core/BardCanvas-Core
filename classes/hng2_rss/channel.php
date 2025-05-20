<?php
namespace hng2_rss;

class channel
{
    public $title;
    public $link;
    public $description;
    
    public $category;
    public $cloud;
    public $copyright;
    public $docs;
    public $generator;
    
    /**
     * @var image
     */
    public $image;
    
    public $language;
    public $lastBuildDate;
    public $managingEditor;
    public $pubDate;
    public $rating;
    public $skipDays;
    public $skipHours;
    public $textInput;
    public $ttl;
    public $webMaster;
    
    /**
     * @var item[]
     */
    public $items = array();
    
    public $comments = array();
    
    /**
     * @return string
     */
    public function export()
    {
        global $config;
        
        /** @var \SimpleXMLElement $root */
        $root = simplexml_load_string(
            "<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom' xmlns:dc='http://purl.org/dc/elements/1.1/'><channel></channel></rss>"
        );
        
        /** @var \SimpleXMLElement $node */
        $node = $root->channel;
        
        $atom = $node->addChild("atom:link", "", "http://www.w3.org/2005/Atom");
        $atom->addAttribute("href", $config->full_root_url . $_SERVER["REQUEST_URI"]);
        $atom->addAttribute("rel", "self");
        $atom->addAttribute("type", "application/rss+xml");
        
        if( ! empty($this->title)        ) $node->addChild("title",       preg_replace('/[\n\s]+/', ' ', $this->title));
        if( ! empty($this->link)         ) $node->addChild("link",        $this->link);
        if( ! empty($this->description)  ) $node->addChild("description", preg_replace('/[\n\s]+/', ' ', $this->description));
        
        if( count($this->items) > 0 )
        {
            foreach($this->items as $item)
            {
                if( ! empty($item->description) )
                    $item->description = preg_replace('/[\n\s]+/', ' ', $item->description);
                
                $item->add_to($node);
            }
        }
        
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML( $root->asXML() );
        
        $xml = $doc->saveXML();
        $xml = str_replace("<author>", "<dc:creator>", $xml);
        $xml = str_replace("</author>", "</dc:creator>", $xml);
        
        return $xml;
    }
}
