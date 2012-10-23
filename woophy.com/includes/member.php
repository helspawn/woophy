<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}	
	
	if(!isset($_GET['viewmode']) || (int)$_GET['viewmode']==0) $simple=false;
	else $simple = true;

	$user = ClassFactory::create('User');
	$user->buffer = false;
	
	require_once CLASS_PATH.'Utils.class.php';
	include_once CLASS_PATH.'Page.class.php';
	include_once CLASS_PATH.'TravelBlog.class.php';
	include_once INCLUDE_PATH.'gallery.php';
	include_once INCLUDE_PATH . 'userlist.php';
	include_once INCLUDE_PATH . 'widgets/search.php';

	$search_label = 'Search';
	$search_options = array(
		array('name'=>'member', 'label'=>'Members'),
		array('name'=>'blog', 	'label'=>'Blogs')
	);

	$output_mode = 'full';
	$pages = array('','photos', 'blog', 'favoritephotos', 'favoritephotographers', 'sendmessage');
	$page_labels = array('overview','photos', 'blog', 'fav photos', 'fav photographers');

	$param = explode('/', rtrim(REQUEST_PATH, '/'));
	if(count($param) >= 2){
		$user_name = urldecode(Utils::stripQueryString(Utils::stripSpecialAction($param[1])));
		if(strlen($user_name)>0){
			$xml_user = $user->getProfileByName($user_name);
			if($err = $xml_user->err) {
				$user_errormsg = 'Member <b>&quot;'.$user_name.'&quot;</b> does not exist.';
				unset($xml_user);
				unset($user_name);
				//include INCLUDE_PATH.'404.php';//no user found
			}else $user_name = $xml_user->name;//get the lcase ucase right
		}else unset($user_name);
	}
	
	$currentpage = 0;//default home
	if(count($param) >= 3){
		$i = array_search(mb_strtolower(Utils::stripQuerystring($param[2])), $pages);
		if($i !== false)$currentpage = $i;
	}
	
	$page = new Page();	
	$pid = isset($_SESSION['pid']) ? (int)$_SESSION['pid'] : 0;
	//handling form posts:
	if(isset($_POST['submit_question'])){
		if(isset($_POST['user_id'],$_POST['email'],$_POST['question'],$_POST['pid'])){
			if($_POST['pid']==$pid){
				$xml_ambassador = $user->sendAmbassadorMessage($_POST['user_id'], $_POST['email'], $_POST['question']);
				$pid = $pid + 1;
				$_SESSION['pid'] = $pid;
				session_write_close();
			}
		}
	}
	
	$main_column = '';
	
	$js = '';//inline javascript

	$blog = ClassFactory::create('Blog');
	$service_url = ABSURL.'services?&method=woophy.blog.getArchiveByCategoryId&category_id='.Blog::CATEGORY_ID_USER;
	$xml_offset = $blog->getPostOffsetByCategoryId(NULL, Blog::CATEGORY_ID_USER);
	$count_posts = (int)$xml_offset->total;

	$access = ClassFactory::create('Access');
	$isLoggedIn = $access->isLoggedIn();

	$baseurl = ROOT_PATH.Utils::stripQueryString($param[0]).'/';
	
	$search_offset = 0;
	$search_limit = 10;

	$searchblog = (isset($_GET['category']) && mb_strtolower($_GET['category'])=='blog');
	if($searchblog)$_GET['blogs']=1;//force blog tab
	
	$travelblog_id = 0;//default no travelblog_id
	$travelblog = new TravelBlog();
	
	$page->setTitle('Members'.(isset($user_name)?' - '.htmlspecialchars($user_name):''));
	
	if(isset($user_name)){
		$page->setSection($page->getSection().'Detail');
		$name_from = htmlspecialchars($user_name).'&#39;';
		if(mb_strtolower(mb_substr($xml_user->name, -1))!='s')$name_from .= 's';
		$user_id = (int)$xml_user->id;
		$blog_url = ROOT_PATH.'member/'.urlencode($user_name).'/blog/';
		
		if($currentpage == 2){//blog
			$blog = new Blog();
			$blog->buffer = false;
			$val = (int)Utils::stripQueryString(end($param));
			if($val>0){
				$post_id = $val;
				$xml_post = $blog->getPostById($post_id, $user_id, true, true);
				$travelblog_id = (int)$xml_post->travelblog_id;
			}else if($xml_user->blog_post_count == 1){
				//only one post, do not show list
				$xml_post = $blog->getLastPostByUserId($user_id);
				if($err = $xml_post->err)unset($xml_post);//blog_post_count is not up to date
				else{
					$post_id = $xml_post->id;
					$travelblog_id = (int)$xml_post->travelblog_id;
				}
			}
			if(isset($post_id))include INCLUDE_PATH.'blogpost.php';
		}
		
		if(!$simple){
			if($travelblog_id>0){
				
				$js .= 'var map=new MapSideBar({map_id:\'MapSidebar\',marker_image_dir:Page.root_url+\'images/map_markers/\', base_url:Page.root_url,service_url:Page.root_url+\'services\',';
				$js .= 'post_id:\''.(int)$post_id.'\',';
				$js .= 'travelblog_id:\''.$travelblog_id.'\',';
				$js .= 'blog_url:\''.$blog_url.'\'});';

			}else if(isset($xml_user->longitude)){

				$js .= 'var map=new MapSideBar({map_id:\'MapSidebar\',marker_image_dir:Page.root_url+\'images/map_markers/\', base_url:Page.root_url,';
				$js .= 'latitude:\''.$xml_user->latitude.'\',';
				$js .= 'longitude:\''.$xml_user->longitude.'\',';
				$js .= 'city_id:\''.$xml_user->city_id.'\'});';	
			
			}else if(isset($xml_user->country_code)){
				$page->addScript('swfobject.js');
				$js .= 'jQuery(document).ready(function(){var so = new SWFObject(\''.ROOT_PATH.'swf/map_sidebar.swf\',\'map\',\'278\',\'208\',\'9\',\'#FFFFFF\',\'high\');so.addParam(\'wmode\',\'opaque\');so.addVariable(\'base_url\',\''.ROOT_PATH.'\');';
				$js .= 'so.addVariable(\'country_code\',\''.$xml_user->country_code.'\');';
				$js .= 'so.write(\'FlashMap\');});';
			}
		}
	}

	if(!$simple){
		$page->addScript('gallery.js');
		$page->addScript('photopage.js');
		$main_column = '<div id="MainContent" class="clearfix"><div id="MainColumn">';
	}
	
	if(isset($user_name)){
		
		include_once CLASS_PATH.'Template.class.php';

		if(!$simple){
			$main_column .= '<div class="MenuBar clearfix">';

			//if($currentpage != 0){
				$main_column .= '<div id="SubNav"><ul class="clearfix">';
				$url = $baseurl.Utils::stripQueryString($param[1]).'/';
				foreach($page_labels as $k=>$v){
					$class = $currentpage==$k ? 'active' : 'inactive';
					$main_column .= '<li><a href="'.$url.$pages[$k].'" class="'.$class.'">'.$v.'</a></li>';
				}
				$main_column .= '</ul></div>';
			//}

			$main_column .= '<div class="ActionButtons clearfix">';
			if(isset($xml_post) || $currentpage==0){
				if(isset($xml_post))$title = $xml_post->title;
				else $title = $user_name .' on Woophy';
				if($title){
					$main_column .= '<div class="SocialButtons">';
					$main_column .= '<div class="ActionButton" id="FacebookButton"><a class="sprite replace" alt="Share to Facebook" title="Share to Facebook" href="http://www.facebook.com/sharer.php?u='.urlencode(ABSURL.REQUEST_PATH).'&t='.urlencode($title) .'" target="_blank">Facebook</a></div>';
					$main_column .= '<div class="ActionButton" id="TwitterButton"><a class="sprite replace" alt="Tweet this" title="Tweet this"  href="http://twitter.com/home?status=' .urlencode($title).'%20-%20'.urlencode(ABSURL.REQUEST_PATH).'" target="_blank">Twitter</a></div>';
					$main_column .= '<div class="ActionButton" id="TumblrButton"><a class="sprite replace" alt="Post to Tumblr" title="Post to Tumblr" href="http://www.tumblr.com/share/photo?source='.urlencode(WOOPHY_LOGO_URL).'&caption='.urlencode($title).'&click_thru='.urlencode(ABSURL.REQUEST_PATH).'" target="_blank">Tumblr</a></div>';
					$main_column .= '<div class="ActionButton" id="PinterestButton"><a class="sprite replace" alt="Add to Pinboard" title="Add to Pinboard" href="#">Pinterest</a></div>';
					$main_column .= '</div>';
				}
			}

			$main_column .= '<div class="ActionButton" id="SendMessageButton"><a href="'.ROOT_PATH.'member/'.urlencode($user_name).'/sendmessage" title="Send a Message" alt="Send a Message" class="open sprite replace '.($isLoggedIn?'enabled':'disabled').'">Send a Message"</a></div>';
			$main_column .= '<div class="ActionButton FavoriteUser" id="AddFavorite"><a href="'.($isLoggedIn? ABSURL .'services?method=woophy.user.addToFavorites&user_id='. $user_id:'#').'" title="Add to Favorites" alt="Add to Favorites" class="sprite replace '.($isLoggedIn?'enabled':'disabled').'">Add to favorite photographers</a></div>';
			if(isset($xml_post))$main_column .= '<div class="ActionButton" id="ReportAbuse"><a class="sprite replace" title="Report Abuse" alt="Report Abuse" href="'.ROOT_PATH.'report?&viewmode=1&url='.ABSURL.REQUEST_PATH.'" target="_blank">Report Abuse</a></div>';
			$main_column .= '</div>';
			
			$main_column .= '</div>';
		}
		
		//user page:
		$user_url = $baseurl.$param[1].'/';
		if($err = $xml_user->err){
			$main_column .= '<p class="Error">'.$err['msg'].'</p>';
		}else{
			include CLASS_PATH.'Photo.class.php';
			$photo = new Photo();
			$photo->buffer = false;
			
			switch($currentpage){
				case 1://photos page
					
					$main_column .= '<div class="OuterContentContainer clearfix">';
					$main_column .= outputGalleryHTML(array('limit'=>30,'user_xml'=>$xml_user, 'show_full_gallery'=>TRUE));
					$main_column .= '</div> <!-- end OuterContentContainer -->';
					break;
				case 2://blog
					
					$print_list = true;
					if(isset($xml_post)){
						if($err=$xml_post->err){
							$main_column .= '<p class="Error">'.$err['msg'].'</p>';
						}else{
							$page->setTitle($xml_post->title);

							//diplay next previous link:
							$xml_prevnext = $blog->getPrevNextByUserId($user_id, $post_id, $xml_post->publication_date);
							$prevnext = '';

							if($prev_id = $xml_prevnext->prev_id)if(mb_strlen($prev_id)>0)$prevnext .= '<li><a class="Previous" href="'.$blog_url.$prev_id.'">&laquo; Previous Post</a></li>';
							if($next_id = $xml_prevnext->next_id)if(mb_strlen($next_id)>0)$prevnext .= '<li><a class="Next" href="'.$blog_url.$next_id.'">Next Post &raquo;</a></li>';
							if(mb_strlen($prevnext)>0)$main_column .= '<div class="MenuBar PrevNext" id="SubNav2"><ul class="clearfix">'.$prevnext.'</ul></div>';

							$page->addScript('swfobject.js');//blog videos
							$main_column .= outputBlogPost($xml_post, 'js');
							$print_list = false;
						}
					}
					if($print_list){
						$main_column .= '<div class="Section"><div class="MainHeader DottedBottom clearfix"><h1>'.$name_from.' blog</h1></div>';
						$noposts = true;
						if($xml_user->blog_post_count>0){
							$posts_xml = $blog->getRecentPostsByUserId($user_id);
							if($error = $posts_xml->err) {
								$main_column .= '<div class="Error">'.$error['msg'].'</div>';
								$noposts = false;
							}else{
								$posts = $posts_xml->post;
								if(count($posts)>0){
									include INCLUDE_PATH.'blogpostlist.php';
									$main_column .= outputBlogPostList($posts);
									$noposts = false;
								}
							}
						}
						if($noposts){
							$main_column .= '<div class="Notice">'.$xml_user->name.' is not blogging.</div>';
						}else $page->addRSS(ROOT_PATH.'feeds/blog?&user_id='.$user_id, 'Woophy Recently added Member Blogposts');
						$main_column .= '</div>';
					}
					
					break;
				case 3://favorite photos page
					$main_column .= '<div class="OuterContentContainer clearfix">';
					$main_column .= outputGalleryHTML(array('gallery_type'=>'favorites', 'limit'=>30,'user_xml'=>$xml_user, 'show_full_gallery'=>TRUE));
					$main_column .= '</div> <!-- end OuterContentContainer -->';
					break;
				case 4://favorite user page
					$main_column .= '<div class="OuterContentContainer clearfix">';
				
					$main_column .= '<div class="MainHeader DottedBottom"><h1>'.$name_from.' favorite photographers</h1>';
					if((int)$xml_user->public_favorites==1){
						$limit = 30;
						$max_limit = 1000;
						$offset = 0;
						if(isset($_GET['offset']))$offset = max(0,(int)$_GET['offset']);
						$offset = round($offset/$limit)*$limit;
						$offset = min($max_limit-$limit,$offset);
						$xml_favs = $user->getFavoritesByUserId($user_id, $offset, $limit);
						$total = min($max_limit, (int)$xml_favs['total_users']);
						$main_column .= ($total>0?$offset+1:0).'&nbsp;-&nbsp;'.(min($offset+$limit,$total)).'&nbsp;of&nbsp;'.$total.($total == $max_limit?'+':'').'&nbsp;total</div>';
						if($total>0){
							$pagingnav = Utils::getPagingNav($offset, $total, $limit);
							$main_column .= $pagingnav;
							$main_column .= getListFavUsers($xml_favs->user);
							$main_column .= $pagingnav;
						}else $main_column .= '<div class="Notice">'.$xml_user->name.' has no favorite photographers yet!</div>';
					
					}else $main_column .= '</div><div class="Notice">The favorite photographers of '.$xml_user->name.' are not public.</div>';
					$main_column .= '</div> <!-- end OuterContentContainer -->';
					break;
				case 5://send message
					ob_start();
					include INCLUDE_PATH.'sendmessage.php';
					$main_column .= ob_get_clean();
					break;
				default://MEMBER OVERVIEW PAGE
					$total = 0;
					$main_column .= '<div class="OuterContentContainer clearfix">';
					$main_column .= outputGalleryHTML(array('user_xml'=>$xml_user, 'orderby'=>'recent', 'link_to_more'=>TRUE));
 					$main_column .= '</div> <!-- end OuterContentContainer -->';

 					if($total>6){//TRICKY: $total is defined by outputGalleryHTML function
						$main_column .= '<div class="OuterContentContainer clearfix">';
						$main_column .= outputGalleryHTML(array('user_xml'=>$xml_user, 'orderby'=>'rating', 'link_to_more'=>TRUE));
 						$main_column .= '</div> <!-- end OuterContentContainer -->';
 					}

					// LATEST BLOGPOST
					$blog_post_count = (int)$xml_user->blog_post_count;
					$main_column .= '<div class="Section">';
					$main_column .= '<div class="MainHeader DottedBottom"><h2>'.$name_from.' latest blogpost</h2>';
					if($blog_post_count>1) $main_column .= '<a href="'.$user_url.'blog">View all ('.$blog_post_count.')</a>';
					$main_column .= '</div>';
					if($blog_post_count>0){
						$blog = ClassFactory::create('Blog');
						$blog->buffer = false;
						$posts_xml = $blog->getRecentPostsByUserId($user_id, 1);
						if($error = $posts_xml->err) $main_column .= '<div class="Error">'.$error['msg'].'</div>';
						else{
							$posts = $posts_xml->post;
							if(count($posts)>0){
								include INCLUDE_PATH.'blogpostlist.php';
								$main_column .= outputBlogPostList($posts);
							}
						}
					}else $main_column .= '<div class="Notice">'.htmlspecialchars($user_name).' is not blogging.</div>';
					$main_column .= '</div> <!-- end Section -->';
					$limit = 6;
					if((int)$xml_user->public_favorites==1){
						$max_limit = 1000;
						$main_column .= '<div class="OuterContentContainer clearfix">';
						$main_column .= outputGalleryHTML(array('user_xml'=>$xml_user, 'gallery_type'=>'favorites', 'link_to_more'=>TRUE));
						$main_column .= '</div> <!-- end OuterContentContainer -->';
						
						$main_column .= '<div class="OuterContentContainer clearfix">';
						$main_column .= '<div class="MainHeader DottedBottom"><h2>'.$name_from.' favorite photographers</h2>';
						
						$xml_favs = $user->getFavoritesByUserId($user_id, 0, $limit);
						$total = (int)$xml_favs['total_users'];
						$main_column .= ' <a href="'.$user_url.$pages[4].'">View&nbsp;all&nbsp;favorites&nbsp;('.($total>$max_limit?$max_limit.'+':$total).')</a>';
						$main_column .= '</div>';
						if($total>0)$main_column .= getListFavUsers($xml_favs->user);
						else $main_column .= '<div class="Notice">This member has no favorite photographers yet!</div>';
						$main_column .= '</div> <!-- end OuterContentContainer -->';
						
					}else $main_column .= '<div class="OuterContentContainer clearfix"><div class="MainHeader DottedBottom"><h2>'.$name_from.' favorites</h2></div><div class="Notice">The favorites of '.htmlspecialchars($user_name).' are not public.</div></div>';
			}
		}
	}else{
		if(isset($user_errormsg))$main_column .= '<div class="Error" style="margin-bottom:20px;">'.$user_errormsg.'</div>';
		
		$main_column .= '<div class="MenuBar clearfix"><div id="SubNav"><ul class="clearfix">';
		$labels = array('overview','blogs','ambassadors');
		$active = 0;
		foreach($labels as $k=>$v){
			if(array_key_exists($v, $_GET)){
				$active = $k;
				break;
			}
		}
		foreach($labels as $k=>$v){
			$main_column .= '<li><a href="'.$baseurl.'?'.$v.'" class="'.($k==$active?'active':'inactive').'">'.$v.'</a></li>';
		}
		$main_column .= '</ul></div></div>';
		
		function getOffset($offset, $limit, $max_limit){
		
			$offset = max(0,(int)$offset);
			$offset = round($offset/$limit)*$limit;
			$offset = min($max_limit-$limit,$offset);
			return $offset;
		}
		
		$limit = 10;
		$max_limit = 1000;//default;
		$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
		
		//homepage/browse/search:
		if(isset($_GET['search']) || isset($_GET['member']) || isset($_GET['blog'])){
			$search_value = isset($_GET['search'])?$_GET['search']:(isset($_GET['member'])?$_GET['member']:$_GET['blog']);
		}
		
		if(!isset($_GET['blogs'])){
			$page->addScript('photo.js');
			$page->addScript('userlist.js');
			$page->addStyle('selectlist.css');
			$js .= 'jQuery(document).ready(function(){var e = jQuery(\'#select_motm\');if(e.length){var photo_motm = new Photo();photo_motm.getRecent(1,e.val());e.change(function(){photo_motm.getRecent(1,this.value)});}e=jQuery(\'form[name="search_member"] #input\');if(e.length){var userlist = new UserList({inputObj:e[0]});jQuery(userlist).on(\'clickItem\', function(evt,n){if(n)document.location.href=\''.$baseurl.'\'+encodeURIComponent(n);});jQuery(\'form[name="search_member"] #category\').change(function(){userlist.setEnabled(this.value==\'member\');});}});';

		}	
		if(!isset($_GET['ambassadors'])){
			$main_column .= '<form name="search_member" action="'.ROOT_PATH.'member" method="get" class="SearchBar clearfix green_bg">';
			$main_column .= '<input type="text" autocomplete="off" name="search" id="input" class="text" alt="Search" value="'.(isset($search_value)?$search_value:'').'" />';
			$main_column .= '<input name="type" type="hidden" value="0" /><input type="hidden" name="offset" value="0" />';
			$main_column .= '<div class="DropdownContainer"><select name="category" id="category" class="sprite"><option value="member">Members</option><option value="blog"'. (isset($_GET['blogs'])?' selected="true"':'').'>Blogs</option></select></div>';
			$main_column .= '<input type="submit" name="submit_search" value="Search" class="submit GreenButton" />';
			$main_column .= '</form>';
		}

		if(isset($search_value) && mb_strlen(trim($search_value))>0 && $search_value != $search_label){
			
				//search results
				$search_offset = getOffset($offset, $limit, $max_limit);
				if($searchblog){
					//blog search
					include_once INCLUDE_PATH.'blogsearchresults.php';
					$main_column .= '<div class="Section">';
					$main_column .= outputBlogSearchResults($search_value, NULL, $search_offset, $search_limit, '&category=blog');
					$main_column .= '</div>';
				}else{
					//member search
					$search_type = isset($_GET['type']) ? (int)$_GET['type'] : 0;
					
					$xml_search = $user->getUsersByName($search_value, $search_offset, $search_limit, $search_type, true);
					
					$users = $xml_search->user;
					echo $users->asXML();
					$main_column .= '<div class="OuterContentContainer clearfix"><div class="DottedBottom MainHeader"><h1>Search Results</h1>';
					if(count($users)>0){
						$search_total = $xml_search['total_users'];
						$main_column .= ($search_offset+1).' - '.(min($search_offset+$search_limit,$search_total)).' of '.$search_total.' total for <span class="strong">'.$search_value.'</span></div>';	
						
						$pagingnav = Utils::getPagingNav($search_offset, $search_total, $search_limit, '&search='.urlencode($search_value));
						$main_column .= $pagingnav;
						
						$main_column .= '<div class="UsersListContainer"><div class="UsersList clearfix">';
						$main_column .= getListUsers($users);

						$main_column .= '</div></div>';

					}else $main_column .= '</div><p>Your search - <span class="strong">'.$search_value.'</span> - did not match any members.</p>';
					$main_column .= '</div>';
				}
			
		}else if(isset($_GET['blogs'])){
			$page->addRSS(ROOT_PATH.'feeds/blog', 'Woophy Recently added Member Blogposts');
			$blog = ClassFactory::create('Blog');
			$blog->buffer = false;
			$posts_xml = $blog->getRecentPostsByCategoryId(Blog::CATEGORY_ID_USER);
			if($error = $posts_xml->err) $main_column .= $error['msg'];
			else{
				include INCLUDE_PATH.'blogpostlist.php';
				$main_column .= '<div class="Section">';
				$main_column .= outputBlogPostList($posts_xml->post);
				$main_column .= '</div>';
			}
		}else if(isset($_GET['ambassadors'])){

			$js .= "jQuery(document).ready(function(){jQuery('input.AskQuestion').click(function(){jQuery('form',jQuery(this).parent().parent()).slideToggle(200);return false;})});";

			$js .= "function submitQuestion(e){if(e){var fb=document.getElementById(e.name+'_feedback'),input=['email','question'],text=['Fill in your e-mail','Fill in your question'],i=input.length;while(i--){if(el=e[input[i]]){if(jQuery.trim(el.value).length==0){if(fb)fb.innerHTML=text[i];if(el.focus)el.focus();return false;}}};if(fb)fb.innerHTML='Sending...';return true;};return false;}";

			$main_column .= '<div class="OuterContentContainer" id="AmbassadorsList"><div class="MainHeader DottedBottom"><h1>Ambassadors</h1></div>';

			$qst_name = $access->getUserName();
			$qst_email = $access->getUserEmail();
			$qst_text = '';
			$qst_fid = -1;
			$qst_feedback = '';
			if(isset($xml_ambassador)){
				if($err = $xml_ambassador->err){
					$qst_feedback = $err['msg'];
					$qst_text = $_POST['question'];
					$qst_email = $_POST['email'];
					$qst_fid = (int)$_POST['fid'];
				}else{
					$main_column .= '<div class="Notice">Your question has been sent.</div>';
				}
			}
			$main_column .= '<p>The Woophy language is English, but for every country there is a contact person, who is a designated Woophy-Ambassador and will be glad to answer any questions you might have in your native language.</p>';
			$xml_users = $user->getAmbassadors();

			$users = $xml_users->user;
			$languages = array();
			$hash = array();
			foreach($users as $u){
				$code = (string)$u->language_code;
				if(!isset($hash[$code])){
					$n = explode(';',(string)$u->language_name_native);
					$native = trim($n[0]);
					$languages[] = array('code'=>$code,'native'=>$native, 'name'=>(string)$u->language_name,'users'=>array());
					$hash[$code] = count($languages)-1;
				}
				$languages[$hash[$code]]['users'][]=array(
					'id'=>(int)$u->id,
					'name' => (string)$u->name,
					'registration_date' => (string)$u->registration_date,
					'country_name' => (string)$u->country_name,
					'country_code' => (string)$u->country_code,
					'city_name' => (string)$u->city_name);
			}
			
			$l = count($languages);
			$num_cols = 3;
			$num_rows = ceil($l/$num_cols);
			$countries_list = '<ul id="CountriesList" class="clearfix">';
			$ambassadors_list = '';
			$i=0;
			foreach($languages as $l){
				$name = trim($l['name']);
				$ambassadors_list .= '<a name="'.$l['code'].'"></a><h2 class="DottedTop">'.$name;
				$native = $l['native'];
				if(mb_strtolower($native) != mb_strtolower($name))$native =' ('.$native.')';
				else $native = '';
				$countries_list .= '<li><a class="link" href="'.$baseurl.'?ambassadors#'.$l['code'].'">'.$name.'</a>'.$native.'</li>';
				$ambassadors_list .= $native .'</h2>';
				$ambassadors_list .= '<div class="UsersList clearfix">';
				$j = 0;
				$n = count($l['users']);
				foreach($l['users'] as $u){
					$uid = $u['id'];
					$ambassadors_list .= '<div class="User ';
					if($j < $n-1) $ambassadors_list .= ' DottedBottom';
					$ambassadors_list .= '"><div class="clearfix">';
					$url = ROOT_PATH.'member/'.urlencode($u['name']);
					$ambassadors_list .= '<a class="Thumb sprite" href="'.$url.'"><img src="'.AVATARS_URL.$uid.'.jpg" /></a>';
					$ambassadors_list .= '<input type="button" class="AskQuestion GreenButton" value="Ask a question"/>';
					$ambassadors_list .= '<div class="Content"><div><a href="'.$url.'">'.$u['name'].'</a>';
					
					if($l['code']=='pt' && $uid==9243){//Laura C wants Brazilian flag
						$u['country_code'] = 'BR';
						$u['country_name'] = 'Brazil';
					}
					if(strlen($u['country_code'])>0)$ambassadors_list .= '&nbsp;&nbsp;<span class="flag flag-'. strtolower($u['country_code']) .' replace">'.$u['country_name'] .'</span>';

					$ambassadors_list .= '</div><div>Registered: '.Utils::formatDate($u['registration_date']);
					$str = '';
					$str = $u['city_name'];
					if(mb_strlen($str)>0) $str .= ', ';
					$str .= $u['country_name'];
					if(mb_strlen($str)>0)$ambassadors_list .= '<br/>From: '.$str;
					$ambassadors_list .= '</div></div></div>';
					
					$ambassadors_list .= '<form onsubmit="return submitQuestion(this);" style="display:'.($qst_fid==$i?'block':'none').';" name="frm'.$i.'" class="FormArea green_bg question" method="post">';
					$ambassadors_list .= '<h3>Ask '.htmlspecialchars($u['name']).' a question</h3>';
					if($isLoggedIn){
						$ambassadors_list .= '<div class="FormRow clearfix"><label for="name">Your name</label><input readonly="true" style="color:#999999" type="text" name="name" value="'.$qst_name.'"/></div>';
						$ambassadors_list .= '<div class="FormRow clearfix"><label for="email">Your e-mail</label><input type="text" name="email" value="'.$qst_email.'"/>*</div>';
						$ambassadors_list .= '<div class="FormRow clearfix"><label for="question">Your question</label><textarea cols="50" rows="3" name="question">'.($qst_fid==$i?$qst_text:'').'</textarea> *</div>';
						$ambassadors_list .= '<div class="FormSubmit clearfix"><input type="hidden" name="fid" value="'.$i.'"/><input type="hidden" name="pid" value="'.$pid.'"/><input type="hidden" name="user_id" value="'.$uid.'"/>';
						$ambassadors_list .= '<input class="submit GreenButton" type="submit" name="submit_question" value="Send!"/><span id="frm'.$i.'_feedback" class="Error">'.($qst_fid==$i?$qst_feedback:'').'</span></div>';
					
					}else $ambassadors_list .= '<a href="'.Utils::stripSpecialAction($_SERVER['REQUEST_URI']).'/Login?r=1">Log in</a> to ask '.htmlspecialchars($u['name']).' a question.';
					$ambassadors_list .= '</form></div>';
					$i++;
					$j++;
				}
				$ambassadors_list .= '</div>';
			}
			$countries_list .= '</ul>';
			$main_column .= $countries_list;
			$main_column .= $ambassadors_list;
			$main_column .= '</div>';
		}else if(isset($_GET['recent'])){
			$max_limit = 200;//TRICKY: woophy got more than 200 new users, so we do not have to check the total amount
			$limit = 30;
			$offset = getOffset($offset, $limit, $max_limit);
			$xml_users = $user->getRecent($offset, $limit);
			$users = $xml_users->user;
			if(count($users)<$limit)$limit = $max_limit= count($users);//comment to test paging	
			$main_column .= '<div class="OuterContentContainer clearfix"><div class="MainHeader DottedBottom"><h1>New Members</h1> '.($offset+1).' - '.($offset+$limit).' of '.$max_limit.'</div>';
			if($err = $xml_users->err)$main_column .= '<div class="Error">'.$err['msg'].'</div>';
			else{
				$pagingnav = Utils::getPagingNav($offset, $max_limit, $limit, '&recent');
				$main_column .= $pagingnav;
				$main_column .= '<div class="UsersListContainer"><div class="UsersList clearfix">' . getListUsers($users) . '</div></div>';
				$main_column .= $pagingnav;
			}
			$main_column .= '</div>';
		}else if(isset($_GET['favorite'])){
			$max_limit = 1000;//TRICKY: woophy got more than 1000 favorited users, so we do not have to check the total amount
			$limit = 30;
			$offset = getOffset($offset, $limit, $max_limit);
			$xml_favorites = $user->getUsersMostFavorited($offset, $limit);
			$users = $xml_favorites->user;
			//if(count($users)<$limit)$limit = $max_limit= count($users);//comment to test paging
			$main_column .= '<div class="OuterContentContainer clearfix"><div class="MainHeader DottedBottom"><h1>Most Favorited Photographers</h1> '.($offset+1).' - '.($offset+$limit).' of '.$max_limit.'+</div>';
			if($err = $xml_favorites->err)$main_column .= '<div class="Error">'.$err['msg'].'</div>';
			else{
				$pagingnav = Utils::getPagingNav($offset, $max_limit, $limit, '&favorite');
				$main_column .= $pagingnav;
				$main_column .= '<div class="UsersListContainer"><div class="UsersList clearfix">' . getListUsers($users) . '</div></div>';
				$main_column .= $pagingnav;
			}
			$main_column .= '</div>';	
		//}else if(isset($_GET['active'])){
		//	$main_column .= 'Most Active paging';
		}else{
			//homepage member section
			$limit = 10;
			$xml_users = $user->getRecent(0, $limit);
			$users = $xml_users->user;
			$main_column .= '<div class="OuterContentContainer clearfix">';
			$main_column .= '<div class="MainHeader DottedBottom"><h2>New Members</h2>';
			if(count($users) == $limit) $main_column .= ' <a href="'.$baseurl.'?&recent">View last 200</a>';
			$main_column .= '</div>';
			if($err = $xml_users->err) $main_column .= '<div class="Error">'.$err['msg'].'</div>';
			else $main_column .= '<div class="UsersListContainer"><div class="UsersList clearfix">' . getListUsers($users) . '</div></div>';
			$main_column .= '</div> <!-- end OuterContentContainer -->';

			$posts_xml = $blog->getRecentPostsByCategoryId(Blog::CATEGORY_ID_USER);
			if($error = $posts_xml->err) $main_column .= $error['msg'];
			else{
				$main_column .= '<div class="Section">';
				include INCLUDE_PATH.'blogpostlist.php';
				$post = $posts_xml->post[0];
				$main_column .= '<div class="MainHeader DottedBottom"><h2>Latest Blogpost</h2></div>';
				$main_column .= outputBlogPostList(array($post));
				$main_column .= '</div>';
			}
			
			$xml_favorites = $user->getUsersMostFavorited(0, $limit, TRUE);
			$users = $xml_favorites->user;
			$main_column .= '<div class="OuterContentContainer clearfix">';
			$main_column .= '<div class="MainHeader DottedBottom"><h2>Most Favorited Members This Month</h2>';
			$main_column .= ' <a href="'.$baseurl.'?&favorite">View top 1000 All Time</a>';
			$main_column .= '</div>';
			if($err = $xml_favorites->err)$main_column .= '<div class="Notice">'.$err['msg'].'</div>';
			else $main_column .= '<div class="UsersListContainer"><div class="UsersList clearfix">' . getListUsers($users) . '</div></div>';
			$main_column .= '</div> <!-- end OuterContentContainer -->';
		}
	}
	
	if(!$simple) $main_column .= '</div> <!-- end MainColumn -->';

	$right_column = '<div id="RightColumn">';

	if(isset($user_name)){
		
		$right_column .= outputSearchBox($search_label, $baseurl, $search_options);

		if($xml_user->city_id != '') $right_column .= '<div id="MapSidebar" class="Section"></div>';
		
		if($travelblog_id>0){//blog
			$right_column .= '<div class="AdContainer" id="azk76744"></div>';
			$page->addScript('simpleblogarchive.js');
			$right_column .= '<div class="Section">';
			$right_column .= '<div class="clearfix"><div class="Header clearfix"><h2>'.$name_from.' TravelBlog</h2></div></div>';
			$right_column .= '<table style="margin:0;" class="profile">';
			$right_column .= '<tr><td class="label"><img src="'.AVATARS_URL.$user_id.'.jpg" /></td><td style="overflow:hidden">'.(isset($xml_user->about)?$xml_user->about:'&nbsp;').'</td></tr></table></div>';

			$xml_offset = $travelblog->getPostOffsetByBlogId($post_id, $travelblog_id);
			
			$service_url = ABSURL.'services?&method=woophy.travelblog.getArchiveByBlogId&travelblog_id='.$travelblog_id;

			$count_posts = (int)$xml_offset->total;
			$offset = $xml_offset->offset;
			$right_column .= '<div class="Section">';
			$right_column .= '<div class="Header clearfix"><h2>Travel Blogposts</h2>';
			$right_column .= '<div class="Nav"><a class="PagingLeft sprite replace" id="page_forward">&laquo;&nbsp;next</a><a class="PagingRight sprite replace" id="page_backward">back&nbsp;&raquo;</a></div></div><div id="TravelBlogposts" class="archive"></div></div>';
			$right_column .= '<script type="text/javascript">//<![CDATA['.PHP_EOL;
			$right_column .= 'jQuery(document).ready(function(){var blogarchive = new SimpleBlogArchive({divObj:document.getElementById(\'TravelBlogposts\'),page_forward:document.getElementById(\'page_backward\'),page_backward:document.getElementById(\'page_forward\'),count_items:'.$count_posts.',current_item_id:'.$post_id.',offset:'.$offset.',limit:10,service_url:\''.$service_url.'\',blog_url:\''.$blog_url.'\'});});'.PHP_EOL;
			$right_column .= '//]]></script>';
				
		}else{
			$right_column .= '<div class="Section Profile">';
			$right_column .= '<div class="Header clearfix"><h2>'.htmlspecialchars($user_name).'</h2>';
			$right_column .= '<div class="Awards">';
			$camera = (int)$xml_user->camera;
			if($camera>0){
				//TODO: retreive this from dbase!!
				$camera_labels = array('Bronze camera, member has published more than 10 photos with above average rating','Silver camera, member has published more than 10 photos with high rating','Gold camera, member has published more than 10 photos with very high rating');
				$right_column .= '<span class="sprite award camera-'.$camera.' replace" title="'.$camera_labels[$camera-1].'">'.$camera_labels[$camera-1].'</span>';
			}
			$awards_labels = explode(',', AWARDS);
			foreach($xml_user->user_award as $awd){
				$cat_id = (int)$awd['category_id'];
				if($cat_id<=count($awards_labels)){
					$right_column .= '<span class="sprite award award-'.$cat_id.' replace" title="'.$awards_labels[$cat_id-1].', '.Utils::formatDate($awd['date']).'">'.$awards_labels[$cat_id-1].', '.Utils::formatDate($awd['date']).'</span>';
				}
			}
			if(isset($xml_user->ambassador))$right_column .= '<a class="sprite award ambassador replace" href="'.$baseurl.'?ambassadors#'.(string)$xml_user->ambassador.'">Ambassador</a>';

			$right_column .= '</div></div>';
			$right_column .= '<div class="Content">';
			$right_column .= '<div class="clearfix"><div class="ImageContainer sprite Avatar"><img src="'.AVATARS_URL.$user_id.'.jpg" /></div><p class="Bio">'.$xml_user->about.'&nbsp;</p></div>';
			$right_column .= '<div class="clearfix"><span class="label">Country:</span><span>'.(isset($xml_user->country_name)?$xml_user->country_name.(isset($xml_user->country_code)?('&nbsp;&nbsp;<a class="flag flag-'. strtolower($xml_user->country_code) .' replace" href="'.ROOT_PATH.'country/'.$xml_user->country_code.'">' . $xml_user->country_code . '</a>'):''):'-').'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">City:</span><span>'.(isset($xml_user->city_name)?$xml_user->city_name:'-').'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Registered:</span><span>'.Utils::formatDate($xml_user->registration_date).'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Num of photos:</span><span>'.$xml_user->photo_count.'</span></div>';
			if(isset($xml_user->email))$right_column .= '<div class="clearfix"><span class="label">E-mail:</span><span>'.$xml_user->email.'</span></div>';
			if(isset($xml_user->date_of_birth))$right_column .= '<div class="clearfix"><span class="label">Age:</span><span>'.Utils::calculateAge($xml_user->date_of_birth).'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Fans:</span><span>'.$xml_user->favorite_count.'</span></div>';
			$right_column .= '<div class="clearfix"><span class="label">Photogear:</span><span>'.(isset($xml_user->photogear)?$xml_user->photogear:'-').'</span></div>';
			$right_column .= '</div> <!-- end Profile -->';
			
			$right_column .= '</div>';
			$right_column .= '<div class="AdContainer" id="azk76744"></div>';

			if($currentpage == 2){//blog
				if((int)$xml_user->blog_post_count>0){
					$page->addScript('simpleblogarchive.js');
					$service_url = ABSURL.'services?&method=woophy.blog.getArchiveByUserId&user_id='.$user_id;

					$post_id = isset($xml_post) ? (int)$xml_post->id : 0;
					$limit = 10;
					if(isset($_GET['total'], $_GET['offset']) && !isset($_GET['search'])){
						$total = (int)$_GET['total'];
						$offset = min($total, (int)$_GET['offset']);
						$offset -= fmod($offset, $limit);
					}else{
						$xml_offset = $blog->getPostOffsetByUserId((int)$post_id, $user_id);
						$total = (int)$xml_offset->total;
						$offset = $xml_offset->offset;
					}

					$right_column .= '<div id="BlogPosts" class="Section">';
					$right_column .= '<div class="Header clearfix"><h2>Blogposts</h2>';
					$right_column .= '<div class="Nav"><a class="PagingLeft sprite replace" id="page_forward">&laquo;&nbsp;next</a><a class="PagingRight sprite replace" id="page_backward">back&nbsp;&raquo;</a></div></div><div id="BlogArchive" class="archive"></div></div>';
					$right_column .= '<script type="text/javascript">//<![CDATA['.PHP_EOL;
					$right_column .= 'jQuery(document).ready(function(){var blogarchive = new SimpleBlogArchive({divObj:document.getElementById(\'BlogArchive\'),page_forward:document.getElementById(\'page_backward\'),page_backward:document.getElementById(\'page_forward\'),count_items:'.$total.',current_item_id:'.($post_id==0?'undefined':$post_id).',offset:'.$offset.',limit:'.$limit.',service_url:\''.$service_url.'\',blog_url:\''.$blog_url.'\'});});'.PHP_EOL;
					$right_column .= '//]]></script>';
				}
			}
		}
	}else{

		if(!isset($_GET['blogs'])){
			//include CLASS_PATH.'Status.class.php';//included in Blog.class 
			//member of the month:	
			$status = new Status();
			$xml_users = $status->getMembersOfTheMonth();
			$right_column .= '<div class="Section clearfix" id="MotmContainer">';
			$right_column .= '<div class="Header clearfix"><h2>Member of the Month</h2></div>'.PHP_EOL;
			if($err = $xml_users->err) $right_column .= $err['msg'];
			else{
				$right_column .= '<div class="DropdownContainer"><select id="select_motm" name="select_motm" class="sprite">';
				$users = $xml_users->user;
				foreach($users as $u){
					$right_column .= '<option value="'.$u->id.'">'.date('F, Y', strtotime($u->date)).'</option>';
				}
				$right_column .= '</select></div><div id="MotM" class="User clearfix"></div>';
			}
			$right_column .= '</div>';
			
			//members birthday:			
			$page->addScript('membersbirthday.js');
			$js .= 'jQuery(document).ready(function(){var mb = new MembersBirthday({divObj:document.getElementById(\'MembersBirthday\'),service_url:\''.ABSURL.'services?method=woophy.user.getBirthdayUsers\',offset:0,limit:6,output_mode:\'xml\',page_forward:document.getElementById(\'page_forward1\'),page_backward:document.getElementById(\'page_backward1\')});});';
			$right_column .= '<div class="Section">';
			$right_column .= '<div class="Header clearfix"><h2>Birthdays Today</h2>';
			$right_column .= '<div class="Nav"><a class="PagingLeft sprite replace" id="page_backward1">&laquo;&nbsp;back</a><a class="PagingRight sprite replace" id="page_forward1">next&nbsp;&raquo;</a></div></div><div id="MembersBirthday" class="UsersList clearfix"></div></div>';
		}

		//member blogposts:
		
		$right_column .= '<div class="AdContainer" id="azk76744"></div>';
		$right_column .= '<div class="Section">';
		$right_column .= '<div class="Header clearfix"><h2>Member Blogposts</h2>';
		$right_column .= '<div class="Nav"><a class="PagingLeft sprite replace" id="page_forward2">&laquo;&nbsp;next</a><a class="PagingRight sprite replace" id="page_backward2">back&nbsp;&raquo;</a></div>';
		$right_column .= '</div><div id="BlogPosts" class="archive"></div></div>';
		$page->addScript('blogarchive.js');
		$js .= 'jQuery(document).ready(function(){var blogarchive = new BlogArchive({divObj:document.getElementById(\'BlogPosts\'),page_forward:document.getElementById(\'page_backward2\'),page_backward:document.getElementById(\'page_forward2\'),count_items:'.$count_posts.',offset:0,limit:6,service_url:\''.$service_url.'\'});});';

		if(isset($_GET['blogs'])){
			
			//latest travelblogs:
			$xml_recent = $travelblog->getRecentPosts(3);
			
			$right_column .= '<div class="Section">';
			$right_column .= '<div class="Header clearfix"><h2>Recent Travelblogs</h2></div>';
			
			if($err = $xml_recent->err) $right_column .= $err['msg'];
			else{
				$n = 0;
				$t = count($xml_recent);
				foreach($xml_recent as $it){
					$n++;
					$name_from = $it->user_name.'&#39;';
					if(mb_strtolower(mb_substr($it->user_name, -1))!='s')$name_from .= 's';
					$url = $baseurl.urlencode($it->user_name).'/blog/'.$it->id;
					$right_column .= '<div class="Excerpt clearfix';
					if($n == $t) $right_column .= ' last';
					$right_column .= '"><a class="Thumb sprite" href="'.$url.'"><img src="'.AVATARS_URL.$it->user_id.'.jpg" /></a>';
					$right_column .= '<div class="ExcerptContent"><div class="Title clearfix"><a href="'.$url.'">'.$name_from.' TravelBlog</a></div>';
					$right_column .= '<div>'.$it->post_count.' posts</div><div class="Meta">last post added '.Utils::dateDiff(strtotime($it->date)).' ago</div></div></div>';
				}
			}
			$right_column .= '</div>';
		}
	}
	
	$right_column .= '</div><!-- end RightColumn  -->';
	
	if(!$simple){
		$page->addInlineScript($js);
		echo $page->outputHeader(2);
	}else{
		//echo $page->outputHeaderSimple();//do not use header in case of inline popup window
	}
	echo $main_column;
	if(!$simple){
		echo $right_column;
		echo '</div> <!-- end MainContent  -->';
		echo $page->outputFooter();
	}
	//else echo $page->outputFooterSimple();//do not use footer in case of inline popup window
?>
