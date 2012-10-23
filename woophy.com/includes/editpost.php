<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	
	$access = ClassFactory::create('Access');
	$uid = $access->getUserId();
	$blog = ClassFactory::create('Blog');
	$blog->buffer = false;

	include CLASS_PATH.'TravelBlog.class.php';

	$action = isset($_GET['action']) ? strtolower($_GET['action']) : 'add';//start with add
	$post_id = isset($_GET['post_id']) ? $_GET['post_id'] : (isset($_POST['post_id']) ? $_POST['post_id'] : NULL);
	if((int)$post_id == 0)$post_id = NULL;
	$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
	
	$blogurl = ROOT_PATH.'member/'.urlencode($access->getUserName()).'/blog/';
	$html = '';
	$js = '';
	if(isset($_POST['submit_edit'])){
		if(isset($post_id)){
			//edit
			if(!isset($_POST['city_id']))$_POST['city_id']=NULL;
			if(!isset($_POST['old_city_id']))$_POST['old_city_id']=NULL;
			$xml_edit = $blog->updatePost($post_id,$_POST['post_title'],$_POST['post_text'],$_POST['post_status'],$_POST['publication_date'],$_POST['category_id'],$_POST['travelblog_id'],$_POST['city_id'],$_POST['old_city_id']);
			if($err = $xml_edit->err) $err_msg = $err['msg'];
			else {
				$update_msg= 'Post is updated!';
				if($_POST['post_status']=='published')$update_msg.=' <a class="view" href="'.$blogurl.$post_id.'">View post</a>';
			}
		}else{
			//add
			if(!isset($_POST['city_id']))$_POST['city_id']=NULL;
			$xml_add = $blog->addPost($_POST['post_title'],$_POST['post_text'],$_POST['post_status'],$_POST['publication_date'],$_POST['category_id'],$_POST['travelblog_id'],$_POST['city_id']);
			if($err = $xml_add->err) $err_msg = $err['msg'];
			else{
				
				$post_id = $xml_add->post_id;
				$update_msg = 'Post is added!';
				if($_POST['post_status']=='published')$update_msg.=' <a class="view" href="'.$blogurl.$post_id.'">View post</a>';
			}
		}
	}

	if($action == 'delete'){
		if(isset($post_id)){
			$xml_delete = $blog->deletePost($post_id);
			if($err = $xml_delete->err) $delete_msg = $err['msg'];
		}
	}
	if(($action == 'edit' && isset($post_id)) || $action == 'add'){
		if($action == 'edit'){
			if(isset($post_id)){
				$xml_post = $blog->getPostById($post_id, $uid, false);
				if($err = $xml_post->err) $err_msg = $err['msg'];
				else{
					$post_publication_date = $xml_post->publication_date;
					$post_title = $xml_post->title;
					$post_text = $xml_post->text;
					$post_category_id = $xml_post->category_id;
					$post_travelblog_id = $xml_post->travelblog_id;
					$post_status = $xml_post->status;
					$post_id = $xml_post->id;
					$post_views = $xml_post->views;
					if(isset($xml_post->video))$post_video = $xml_post->video;
					$comment_count = $xml_post->comment_count;
					$city_id = $xml_post->city_id;
					$city_name = $xml_post->city_name;
					$country_code = mb_strtolower($xml_post->country_code);
				}
			}
		}else {
			$post_publication_date = '';
			$post_title = '';
			$post_text = '';
			$post_travelblog_id = '';
			$post_category_id = Blog::CATEGORY_ID_USER;
			$post_status = 'draft';
			$post_views = 0;
			$comment_count = 0;
			$city_id = '';
			$city_name = '';
			$country_code = '';
		}
		
		$js .= 'jQuery(document).ready(function(){';//begin domready
		
		//print form:
		if(isset($err_msg))$html .= '<p class="Error">'.$err_msg.'</p>';
		else{
			
			$upload_id = uniqid('');
			if(isset($update_msg))$html .= '<div class="Notice">'.$update_msg.'</div>';

			$page = ClassFactory::create('Page');
			$page->addScript('dateselector.js');
			$page->addScript('selectionformat.js');
			$page->addScript('uploadprogress.js');
			$page->addScript('citylist.js');
			$page->addScript('blog.js');
			$page->addStyle('datefield.css');
			$page->addStyle('selectlist.css');

			$html .= '<form id="BlogForm" action="'.Utils::stripQueryString($_SERVER['REQUEST_URI']).'?&action=edit" name="frmeditpost" method="post" enctype="multipart/form-data" onsubmit="if(parseInt(this.category_id.value)!='.TravelBlog::CATEGORY_ID.')this.travelblog_id.value=\'\';">';
			$html .= '<input type="hidden" id="UPLOAD_IDENTIFIER" name="UPLOAD_IDENTIFIER" value="'.$upload_id.'">';
	
			if(isset($post_id))$html .= '<input type="hidden" id="post_id" name="post_id" value="'.$post_id.'">';
	
			$html .= '<div class="FormArea">';
			$html .= '<div class="FormRow clearfix"><label for="post_title">title</label><input type="text" class="text" id="post_title" name="post_title" value="'.$post_title.'" />*</div>';

			//contributor_role
			$xml_cat = $blog->getCategoriesByUserId($uid);
			$newsletter = false;
			if($err = $xml_cat->err) die('ERROR:'.$err['msg']);//TODO: proper error handling
			else{	
				$cats = $xml_cat->category;
				if(count($cats)==0) $html .= '<input type="hidden" name="category_id" value="'.$post_category_id.'" />';
				else{
					$travelblog = false;
					$html .= '<div class="FormRow clearfix"><label for="category_id">category</label>';
					$html .= '<div id="CategoryContainer" class="DropdownContainer"><select class="sprite" id="category_id" name="category_id">';
					$html .= '<option value="'.Blog::CATEGORY_ID_USER.'">blog</option>';//default value, every user can post blog
					foreach($cats as $cat){
						if((int)$cat->id == TravelBlog::CATEGORY_ID){
							//category travel: only allow if travelblog is present:
							$tb = new TravelBlog();
							$xml_travelblog = $tb->getBlogByUserId($uid);
							if($title = $xml_travelblog->title)$travelblog = true;
							else{
								unset($xml_travelblog);
								continue;
							}
						}elseif((int)$cat->id == Blog::CATEGORY_ID_NEWSLETTER)$newsletter = true;
						$html .= '<option value="'.$cat->id.'"';
						if((int)$cat->id == (int)$post_category_id) $html .= ' selected="true"';
						$html .= '>'.$cat->name.'</option>';
					}
					$html .= '</select></div></div>';

					if($travelblog){
						$js .= 'jQuery(\'select#category_id\').change(function(){var bln=this.value=='.TravelBlog::CATEGORY_ID.';jQuery(\'#ShowCity\').attr(\'class\', bln?\'blockdisplay\':\'nodisplay\');if(datesel){datesel.setRange(bln?null:rangeStart, null);datesel.setSelectedDate(rangeStart);}});';
						$html .= '<div id="ShowCity" class="'.($post_category_id==TravelBlog::CATEGORY_ID?'block':'no').'display">';
						$html .= '<div class="FormRow clearfix"><label>country</label><div class="CountryDropdown DropdownContainer"><select class="sprite" name="country_code"><option value="">-</option>';
						include_once CLASS_PATH.'Location.class.php';
						$location = new Location();
						$country_xml = $location->getAllCountries();
						if($error = $country_xml->err) $html .= $error['msg'];
						else{
							foreach ($country_xml->country as $c){
								$html .= '<option value="'.$c['cc'].'"';
								if($country_code == mb_strtolower($c['cc']))$html .= ' selected="true"';
								$html .= '>'.$c.'</option>';
							}
						}
						$html .= '</select></div></div>';
						$html .= '<div class="FormRow clearfix"><label>city</label><input class="text '.(strlen($city_name)>0?'Focus':'DisabledText').'" autocomplete="off" id="city_name" value="'.$city_name.'" name="city" type="text" /></div>';
						$html .= '<input name="city_id" id="city_id" type="hidden" value="" /><input name="old_city_id" id="old_city_id" type="hidden" value="'.$city_id.'" />';//city_id = UFI
						$html .= '</div>';
					}
				}
			}

			$html .= '<input type="hidden" id="post_views" name="post_views" value="'.(int)$post_views.'" /><input type="hidden" id="comment_count" name="comment_count" value="'.(int)$comment_count.'" /><input type="hidden" id="travelblog_id" name="travelblog_id" value="';
			if(mb_strlen($post_travelblog_id)>0) $html .= $post_travelblog_id;
			else if(isset($xml_travelblog)) $html .= $xml_travelblog->id;
			$html .='" />';
			$html .= '<div class="FormRow clearfix"><label for="publication_date">publication date</label><input type="text" class="text DateField sprite_admin" id="publication_date" name="publication_date" readonly="true" value="'.$post_publication_date.'" />*</div>';

			if(isset($post_video))$html .= '<label for="post_video">video attached</label>'.$post_video.'<br/>';

			$html .= '</div>';
			$html .= '<div id="EditorButtons" class="FormRow clearfix green_bg">';
			$html .= '<img id="btn_bold" src="'. ROOT_PATH.'images/forum_buttons/button_bold.gif" width="21" height="21" border="0" alt="bold" />';
			$html .= '<img id="btn_italic" src="'.ROOT_PATH.'images/forum_buttons/button_italic.gif" width="21" height="21" border="0" alt="italic" />';
			$html .= '<img id="btn_underlined" src="'.ROOT_PATH.'images/forum_buttons/button_underlined.gif" width="21" height="21" border="0" alt="underline" />';
			$html .= '<img id="btn_url" src="'.ROOT_PATH.'images/forum_buttons/button_url.gif" width="21" height="21" border="0" alt="url" />';
			$html .= '<img id="btn_header" src="'.ROOT_PATH.'images/forum_buttons/button_header.gif" width="21" height="21" border="0" alt="header" />';
			$html .= '<img id="btn_hr" src="'.ROOT_PATH.'images/forum_buttons/button_hr.gif" width="21" height="21" border="0" alt="horizontal rule" />';
			$html .= '<img id="submit_insert" src="'.ROOT_PATH.'images/forum_buttons/button_image.gif" alt="insert image" />';
			$html .= '<input class="submit GreenButton floatright" type="submit" name="submit_preview" id="submit_preview" value="Preview post" />';
			$html .= '</div> <!-- end EditorButtons -->';
			$html .= '<div class="FormRow FormArea clearfix nodisplay green_bg" id="InsertImageHolder">';
			$html .= '<input class="button_simple" type="button" id="cancel_insert" name="cancel_insert" value="Cancel" />';
			$html .= '<div class="DropdownContainer"><select id="option_insert" class="sprite" name="option_insert">';
			$html .= '<option value="0" selected="true">Woophy Photo #</option>';
			$html .= '<option value="1">Image url</option>';
			$html .= '<option value="2">YouTube video url</option>';

			if($newsletter)$html .= '<option value="3">Upload blog photo</option><option value="4">Browse blog photos</option>';
				
			$html .= '</select></div>';
			$html .= '<span id="InsertImage"></span>';
			$html .= '</div> <!-- end InsertImageHolder -->';
			
			$html .= '<div id="upload_option"><div id="ProgressOuter" class="sprite_admin nodisplay"><div id="ProgressInner"></div></div><div class="small" id="upload_status">&nbsp;</div></div>';
			$html .= '<div id="browse_option"></div>';
			$html .= '<textarea name="post_text" cols="90" rows="18" >'.Utils::decodeTags($post_text).'</textarea>';
			$html .= '<div class="FormRow clearfix PostButtons">';
			$html .= '<div class="floatleft"><input class="RedButton submit" onclick="window.location.href=\'?&action=edit&offset='.$offset.'\'" type="button" name="submit_cancel" value="Cancel" /><input class="OrangeButton submit" onclick="window.location.href=\''.$_SERVER['REQUEST_URI'].'\'" type="button" name="revert" value="Revert to last version" /></div>';
			$html .= '<div class="floatright"><input class="submit GreenButton" type="submit" id="submit_draft" name="submit_edit" value="Save as Draft" /><input class="submit GreenButton" type="submit" id="submit_publish" name="submit_edit" value="Publish" /></div>';
			$html .= '</div><input type="hidden" name="post_status" value="'.$post_status.'" /></form>'.PHP_EOL;
			$html .= '<iframe id="target_upload" name="target_upload" src="" style="width:0;height:0;border:0"></iframe>';


			$js .= 'var delimiter = \'-\';var f1 = document.forms[0][\'publication_date\'];var datesel = new DateField(f1);datesel.dateFormatter = function(d){return d.getFullYear() + delimiter + (d.getMonth()+1) + delimiter + d.getDate()};var rangeStart = new Date(';
			if(strlen($post_publication_date)>0)$js .= date('Y,n,d', strtotime($post_publication_date.' -1 month'));
			$js .= ');datesel.setRange(rangeStart, null);f1.value = datesel.dateFormatter(rangeStart);datesel.setSelectedDate(rangeStart);var blog = new Blog({user_id:'.(int)$uid.',upload_id:\''.$upload_id.'\'});';

			$js .= "var f = document.forms[0];if(f){var citylist = new CityList({ inputObj:f['city']});jQuery(citylist).on('selectItem', function(evt, listItem){f['city_id'].value = listItem ? listItem.ufi : ''});var e = f['country_code'];if(e){e.onchange = function(){var v = this.options[this.selectedIndex].value;f['city'].value = '';f['city_id'].value = '';citylist.setCountryCode(v);};";
			if(strlen($country_code)>0) $js .= 'citylist.setCountryCode(\''.$country_code.'\');';
			$js .= '}};';

		}
		$js .= '});';//end domready
		$page->addInlineScript($js);

	}else{

		if(isset($err_msg))$html .= '<p class="Error">'.$err_msg.'</p>';
		else{
			//list posts:
			$limit = 20;

			$xml_posts = $blog->getPostsByUserId($uid, $offset, $limit);
			$posts = $xml_posts->post;
			
			$nav = Utils::getPagingNav($offset, (int)$xml_posts['total_posts'], $limit, '&action=edit');
			if(count($posts)>0){
				$html .= $nav;
				$html .= '<table class="edit"><tr><th class="posttitle">Title</th><th class="postdate">Date</th><th class="poststatus">Status</th><th class="comments">Comments</th><th class="edit">&nbsp;</td><th class="view">&nbsp;</th><th class="delete">&nbsp;</th></tr>';
				$i = 0;
				foreach($posts as $post){
					$html .= (fmod(++$i,2)==0)? '<tr class="even">' : '<tr class="odd">';
					$html .= '<td class="posttitle">'.$post->title.'</td><td>'.Utils::formatDateShort($post->publication_date).'</td><td>'.$post->status.'</td><td>'.$post->comment_count.'</td><td class="view"><a target="_blank" href="'.ABSURL.'viewpost?&post_id='.$post->id.'">View</a></td><td class="edit"><a href="?&action=edit&offset='.$offset.'&post_id='.$post->id.'">Edit</a></td><td class="delete"><a href="?&action=delete&offset='.$offset.'&post_id='.$post->id.'" onclick="return confirm(\'Are you sure you want to delete this post?\')">Delete</a></td></tr>'.PHP_EOL;
				}
				$html .= '</table>'.PHP_EOL;
				$html .= $nav;
			}else $html .= '<div class="Notice">There are no posts yet!</div>'.PHP_EOL;
		}
	}

	echo '<div class="Section">';
	echo '<div class="MainHeader DottedBottom"><h1>'.($action == 'edit'?'Edit':'Add').' post</h1></div>';
	echo $html;
	echo '</div>';

?>