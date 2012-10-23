<?php
require_once CLASS_PATH.'Response.class.php';
require_once CLASS_PATH.'Template.class.php';
require_once CLASS_PATH.'User.class.php';

class Widget extends Response{
	
	private $access;
	private $user;
	private $templates;
	
	public function __construct() {
		$this->access = ClassFactory::create('Access');
		$this->user = new User();
		$this->templates = array(
			'tpl_general'		=> new Template('feed_item_general.tpl'), 
			'tpl_tweet'			=> new Template('feed_item_tweet.tpl'), 
			'tpl_blogpost'		=> new Template('feed_item_blogpost.tpl'), 
			'tpl_user'			=> new Template('feed_item_user.tpl'), 
			'tpl_fan'			=> new Template('feed_item_fan.tpl'), 
			'tpl_photo'			=> new Template('feed_item_photo.tpl'), 
			'tpl_upload'		=> new Template('feed_item_upload.tpl'), 
			'tpl_feedback'		=> new Template('feed_item_feedback.tpl'),
			'tpl_favorite_photo'=> new Template('feed_item_favorite_photo.tpl')
		);
		parent::__construct();
	}

	function outputFeed($id, $title, $data, $list_empty=NULL, $get_more=NULL, $limit=5){
		$limit=max(1,$limit);
		$alt = TRUE;
		$more_text_shown = FALSE;
		$html = '';
		$item_count = 0;
		$html .= '<div id="'. ucfirst($id) .'Feed" class="Feed clearfix js_hidden">';
		$html .= '<h2 class="Header">' . $title . '</h2>';
		$html .= '<div class="FeedContent clearfix">';
	
		foreach($data as $item):
			($alt? $alt=FALSE:$alt=TRUE);
			$item_type = $item['type'];
			if($item_type == 'error'){
				$html .=  $this->templates['tpl_general']->parse(array(
					'label'			=> ($id=='Mixed')?'':$item->label,
					'alt'			=> $alt?' alt':'',
					'item_title' 	=> ($id=='Mixed')?'':$item->message,
					'item_text'		=> ($id=='Mixed')?'':($more_text_shown?'':'<a href="'. $get_more['url'] . '">'. $get_more['text'] . '</a>')
				));
				$more_text_shown = TRUE;
				$item_count++;
				if($id!='Mixed') break;
			}else{
				//Normalize all data across the different XML sources so we can apply it to the template->parse() method uniformly
				$item_url = ''; $item_title = ''; $item_date = ''; $thumb_src = ''; $user_name = ''; $city_id = ''; $city_name = ''; $country_name = ''; $location = ''; $comment_count = ''; $tpl = ''; $label = '';
				switch($item_type):
					case 'blogpost': 
						$item_title = $item->title;
						if($thumb_src = Utils::getImageSource($item->text)):
							if($url = parse_url($thumb_src)):
								$thumb_src = str_replace(array('/m/','/l/'), '/s/', $thumb_src);
							endif;
						endif;
						$item_url 		= $item->category_id == Blog::CATEGORY_ID_NEWSLETTER ? ROOT_PATH.'news/newsletter/'.$item->id : ROOT_PATH.'member/'.urlencode($item->user_name).'/blog/'.$item->id;
						$item_date 		= Utils::dateDiff(strtotime(($item->publication_date))) . ' ago';
						$comment_count	= $item->comment_count;
						$tpl 			= $this->templates['tpl_blogpost'];
					break;
					
					case 'twitter':
						$item_title 	= $item->title;
						$item_url		= $item->item_url;
						$item_date		= Utils::formatDate($item->date);
						$tpl 			= $this->templates['tpl_tweet'];
					break;
					
					case 'photo':
					case 'editorspick':
					case 'photo_favorite':
						$city_name		= $item->city_name;
						$country_name	= $item->country_name;
						$city_id		= $item->city_id;
						$city_link		= '<a class="MapLink" rel="cityid:'.$item->city_id.'" href="'.ROOT_PATH.'city/' . urlencode($item->city_name) .'/'. urlencode($item->country_name) .'">' . $item->city_name . '</a>';
						$country_link	= '<a href="'.ROOT_PATH.'country/' . urlencode($item->country_name) .'">' . $item->country_name . '</a>';
						$item_title 	= $city_link . (($item->city_name!=''&& $item->country_name!='')?', ':'') . $country_link;
						$user_name 		= $item->user_name;
						$thumb_src 		= Utils::getPhotoUrl($item->user_id,$item->id,'thumb');
						$item_url 		= ROOT_PATH.'photo/'.$item->id;
						if($item_type=='editorspick') $item_date = 'Selected ' . Utils::dateDiff(strtotime(($item->pick_date))) . ' ago';
						else $item_date	= Utils::dateDiff(strtotime(($item->date))) . ' ago';
						$comment_count	= $item->comment_count;
						$tpl			= ($item_type=='photo')? $this->templates['tpl_upload']:(($item_type=='editorspick')? $this->templates['tpl_photo'] : $this->templates['tpl_favorite_photo']);
					break;
					
					case 'comment':
						$item_title 	= '"'.$item->text.'"';
						$user_name 		= $item->user_name;
						$thumb_src 		= Utils::getPhotoUrl($item->user_id,$item->photo_id,'thumb');
						$item_url 		= ROOT_PATH.'photo/'.$item->photo_id;
						$item_date 		= $item->time_posted . ' ago';
						$tpl			= $this->templates['tpl_feedback'];
					break;
										
					case 'user':
					case 'fan':
						$user_name 		= $item->name;
						$city_id		= $item->city_id;
						$thumb_src 		= AVATARS_URL . $item->id . '.jpg';
						$item_url 		= ROOT_PATH.'member/'.$item->name;
						$country_name	= ($item->country_name !='')?'<a href="'.ROOT_PATH.'country/' . urlencode($item->country_name) .'">' . $item->country_name . '</a>':'';
						$city_name		= ($item->city_name !='')?'<a class="MapLink" rel="cityid:'.$city_id.'" href="'.ROOT_PATH.'city/' . urlencode($item->city_name) .'/'. urlencode($item->country_name) .'">' . $item->city_name . '</a>':'';
						$location	 	= ($city_name!='' || $country_name !='')? ' from ' . $city_name . (($city_name!=''&& $country_name!='')?', ':'') . $country_name:'';
						$item_date		= ($item_type=='user')?('Joined ' . Utils::dateDiff(strtotime($item->registration_date)) .' ago<br/>'):(Utils::dateDiff(strtotime($item->date)) . ' ago<br />');					
						$tpl 			= ($item_type=='user')? $this->templates['tpl_user']: $this->templates['tpl_fan'];
					break;
	
				endswitch;
				if($tpl){
					$html .= $tpl->parse(array(
						'label'			=> ($item->label)?'<div class="label">'.$item->label.'</div>':'',
						'alt' 			=> $alt?' alt':'',
						'item_url'		=> $item_url,
						'thumb_src'		=> $thumb_src,
						'item_title'	=> $item_title,
						'item_date'		=> $item_date,
						'user_name'		=> $user_name,
						'city_name'		=> $city_name,
						'city_id'		=> $city_id,
						'country_name'	=> $country_name,
						'location'		=> $location,
						'comment_count'	=> $comment_count,
						'comment_noun'	=> $comment_count == 1 ? 'comment' : 'comments',
						'root_url'		=> ROOT_PATH
					));
				}
				$item_count++;
			}
			if($item_count >= $limit) break;
		endforeach;
		
		for($x=$item_count;$x<$limit;$x++):
			($alt? $alt=FALSE:$alt=TRUE);
			$html .= $this->templates['tpl_general']->parse(array(
				'alt'			=> $alt?' alt':'',
				'item_title'	=> ($more_text_shown?'':($x==0?$list_empty['title']:$get_more['title'])),
				'item_text'		=> ($more_text_shown?'':($x==0?'<a href="'. $list_empty['url'] . '">'. $list_empty['text'] . '</a>':'<a href="'. $get_more['url'] . '">'. $get_more['text'] . '</a>')),
				'label'			=> ''
			));
			$more_text_shown = true;
			
		endfor;
		$html .= '</div></div> <!-- end FeedContent, Feed -->';
		
		return array('html'=>$html, 'num_items'=>$item_count);
	}

	public function getTwitterFeed($twitter_id, $max=5){
		$data = json_decode($this->getCachedDataFromURL('https://api.twitter.com/1/statuses/user_timeline.json?trim_user=true&include_rts=true&screen_name='.$twitter_id. '&count='.$max,3600), TRUE);
		$XMLObject = $this->getXMLObject('<items />');
		$twitter_link_re = '/(http:\/\/t.co\/\S+\s+)/';
		$twitter_link_nospace_re = '/(http:\/\/t.co\/\S+)/';
		foreach($data as $tweet):
			$item = $XMLObject->addChild('item');
			$item->addAttribute('type', 'twitter');
			$item->addAttribute('timestamp', strtotime($tweet['created_at']));
			$tweet_text = htmlspecialchars($tweet['text'], ENT_QUOTES, 'UTF-8');
			/* HACK TO REMOVE THE AUTO-GENERATED LINK TO TWITPIC */
			$tweet_array = preg_split($twitter_link_re, $tweet_text,-1,PREG_SPLIT_DELIM_CAPTURE);
			if(count($tweet_array)>2 && preg_match($twitter_link_nospace_re, $tweet_array[count($tweet_array)-1]) && preg_match($twitter_link_nospace_re, $tweet_array[count($tweet_array)-2])) $twitpic = array_pop($tweet_array);
			$tweet_text = implode(' ', $tweet_array);
			/* END HACK */
			$item->addChild('title', Utils::parseLinks($tweet_text));
			
			$item->addChild('item_url', 'https://twitter.com/#!/' .$twitter_id.'/status/' . $tweet['id_str']);
			$item->addChild('date', date('F jS, Y', strtotime($tweet['created_at'])));
		endforeach;
		return $XMLObject;
	}

	public function getJoinNowPhotoUrl(){
		$force_update = FALSE;//set to TRUE to reset cache
		$update = $force_update;
		$limit = 10;
		$refresh_interval = 300; //in seconds
		$now = time();
		$photo_url = NULL;
		if(!$force_update){
			if($xmlstr = $this->getFromCache(__METHOD__)){
				$xmlobject = $this->getXMLObject($xmlstr);
				if((int)$xmlobject['timestamp'] < $now-$refresh_interval){
					$update = TRUE;
					$this->clear();
				}
			}else{$update = TRUE;}
		}
		if($update){
			$this->clear();
			$xmlobject = $this->getXMLObject();
			$pattern = '*.[Jj][Pp][Gg]';
			$join_now_path = 'join_now/';
			$photos = glob(IMAGES_PATH.$join_now_path . $pattern, GLOB_BRACE);
			//$current_ordinal = rand(0, count($photos)-1);
			//echo $current_ordinal;
			if(count($photos)>0){
				$photo_filepath = $photos[rand(0, count($photos)-1)];
				$photo_url = IMAGES_URL.$join_now_path.substr($photo_filepath, strrpos($photo_filepath, '/')+1);	
				$xmlobject->addAttribute('timestamp', $now);
				$xmlobject->addChild('photo_url', $photo_url);
			}
			$this->saveToCache(__METHOD__, $this->send(), false, 0);
				
		}else{
			$photo_url = $xmlobject->photo_url;
		}
		return $photo_url;
	}
}
