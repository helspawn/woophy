<?php
//
class Feed{
	
	private $outputstr;
	private $items;
	
	public $title;
	public $link;
	public $description;
	public $lastBuildDate;
	public $language;
	
	public function __construct() {
		$this->title = 'Woophy';
		$this->description = '';
		$this->link = '';
		$this->lastBuildDate = strtotime('now');
		$this->pubDate = '';
		$this->language = 'en-us';
		$this->items = array();
	}
	public function addItem($param){
		$str = TAB.TAB.'<item>'.PHP_EOL;
		if(isset($param['title']))$str .= TAB.TAB.TAB.'<title>'.$param['title'].'</title>'.PHP_EOL;
		if(isset($param['link']))$str .= TAB.TAB.TAB.'<link>'.$param['link'].'</link>'.PHP_EOL;
		if(isset($param['description']))$str .= TAB.TAB.TAB.'<description>'.$param['description'].'</description>'.PHP_EOL;
		if(isset($param['pubDate']))$str .= TAB.TAB.TAB.'<pubDate>'.$this->formatDate($param['pubDate']).'</pubDate>'.PHP_EOL;
		if(isset($param['author']))$str .= TAB.TAB.TAB.'<author>'.$param['author']."</author>\n";
		if(isset($param['comments']))$str .= TAB.TAB.TAB.'<comments>'.$param['comments'].'</comments>'.PHP_EOL;	
		$str .= TAB.TAB.TAB.'<content:encoded><![CDATA[';
		if(isset($param['content']))$str .= $param['content'];
		$str .= ']]></content:encoded>'.PHP_EOL;
		$str .= TAB.TAB.'</item>'.PHP_EOL;
		$this->items[] = $str;
	}
	public function output(){
		$outputstr = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
		$outputstr .= '<rss ';
		$outputstr .= 'xmlns:content="http://purl.org/rss/1.0/modules/content/" ';
		$outputstr .= 'xmlns:dc="http://purl.org/dc/elements/1.1/" ';
		$outputstr .= 'version="2.0">'.PHP_EOL;
		$outputstr .= TAB.'<channel>'.PHP_EOL;
		if(mb_strlen($this->title)>0)$outputstr .= TAB.TAB.'<title>'.$this->title.'</title>'.PHP_EOL;
		if(mb_strlen($this->description)>0)$outputstr .= TAB.TAB.'<description>'.$this->description.'</description>'.PHP_EOL;
		if(mb_strlen($this->language)>0)$outputstr .= TAB.TAB.'<language>'.$this->language.'</language>'.PHP_EOL;
		if(mb_strlen($this->lastBuildDate)>0)$outputstr .= TAB.TAB.'<lastBuildDate>'.$this->formatDate($this->lastBuildDate).'</lastBuildDate>'.PHP_EOL;
		if(mb_strlen($this->pubDate)>0)$outputstr .= TAB.TAB.'<pubDate>'.$this->formatDate($this->pubDate).'</pubDate>'.PHP_EOL;
		if(mb_strlen($this->link)>0)$outputstr .= TAB.TAB.'<link>'.$this->link.'</link>'.PHP_EOL;

		foreach($this->items as $item){
			$outputstr .= $item;
		}
		$outputstr .= TAB.'</channel>'.PHP_EOL;
		$outputstr .= '</rss>';
		return $outputstr;
	}
	private function formatDate($d){
		$format = 'D, d M Y H:i:s O';
		return (string)isset($d) && mb_strlen($d) > 0 ? date($format, strtotime($d)) : date($format);
	}
}
