<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}

	include_once CLASS_PATH.'PhotoFolder.class.php';
	include_once CLASS_PATH.'Photo.class.php';
	
	$page = ClassFactory::create('Page');
	$access = ClassFactory::create('Access');
	$user_id = $access->getUserId();
	$photofolder = new PhotoFolder();
	$photofolder->buffer = false;
	$photo = new Photo();
	$photo->buffer = false;
	
	$photos_limit = 12;//pics per page

	$offset = isset($_GET['offset']) ? (int)trim($_GET['offset']) : 0;
	$total = isset($_GET['total']) ? (int)trim($_GET['total']) : 0;
	$folder_id = isset($_GET['folder_id'])&& strlen($_GET['folder_id'])>0 ? (int)trim($_GET['folder_id']) : NULL;
	$search = isset($_GET['search']) && strlen(trim($_GET['search']))>0 ? trim($_GET['search']) : NULL;
	$search_cat = isset($search,$_GET['search_cat']) && strlen(trim($_GET['search_cat']))>0 ? trim($_GET['search_cat']) : NULL;
	$orderby = isset($_GET['orderby']) && mb_strtolower($_GET['orderby']) == 'asc' ? 'ASC' : 'DESC';
	$photo_id = isset($_GET['photo_id']) ? (int)$_GET['photo_id'] : NULL;if($photo_id==0)$photo_id = NULL;

	$pid = isset($_SESSION['pid']) ? (int)$_SESSION['pid'] : 0;

	//handling form posts:
	if(isset($_REQUEST['submit_addfolder'])){
		$xml = $photofolder->add($_REQUEST['folder_name']);
		if ($error = $xml->err) {
			$error_addfolder = $error['msg'];
		}
		if(isset($_REQUEST['output_mode'])){
			if($_REQUEST['output_mode']=='json'){
				if(isset($error_addfolder)) echo '{"error":"'.$error_addfolder. '"}';
				else echo '{"message":"your folder has been created.","folder_id":"'.$xml->folder_id.'","folder_name":"'.$xml->folder_name.'"}';
				die();
			}
		}
	}else if(isset($_POST['submit_editfolder'], $_POST['folder_id'], $_POST['folder_name'])){
		$xml = $photofolder->edit($_POST['folder_id'], $_POST['folder_name']);
		if($error=$xml->err)$err_edit = $error['msg'];
		else $msg_edit = 'Changes saved';
		$folder_id = (int)$_POST['folder_id'];
	}else if(isset($_POST['submit_delete'])){
		if(isset($_POST['photo_ids']) && is_array($_POST['photo_ids'])){//multiple
			$xml_delete = $photo->removePhotos($_POST['photo_ids']);
			if($error = $xml_delete->err){
				$msg_delete = $error['msg'];
			}else {
				$num = count($_POST['photo_ids']);
				$msg_delete = 'Your photo'.($num>1?'s have':' has').' been deleted.';
				$offset = Utils::calculatePagingOffset($offset, $photos_limit, $total-$num);
			}
		}else if(isset($_POST['photo_id'])){//single delete
			$xml_delete = $photo->removePhoto($_POST['photo_id']);
			if($error = $xml_delete->err){
				$msg_delete = $error['msg'];
			}else{
				$offset = Utils::calculatePagingOffset($offset, $photos_limit, $total-1);
				$photo_id = NULL;
				$msg_delete = 'Your photo has been deleted.';
			}
		}
		if(isset($folder_id))$photofolder->resetLastImage($folder_id);
	}else if(isset($_POST['submit_movetofolder'],$_POST['folder_id'],$_POST['old_folder_id'])){
		if(isset($_POST['photo_ids'])){
			if(strlen($_POST['folder_id'])>0){
				$fid = (int)$_POST['folder_id'];
				if($fid==0)$fid=NULL;
				$offset = Utils::calculatePagingOffset($offset, $photos_limit, $total-count($_POST['photo_ids']));
				$photofolder->moveToFolder($_POST['photo_ids'], $fid);
				if(isset($fid))$photofolder->resetLastImage($fid);
				if(strlen($_POST['old_folder_id'])>0)$photofolder->resetLastImage($_POST['old_folder_id']);
			}
		}
	}else if(isset($_POST['submit_edit'],$_POST['pid']) && $_POST['pid']==$pid){
		//update:
		$photo_id = $_POST['photo_id'];
		$ufi = trim($_POST['city_id']);
		if(strlen($ufi)==0)$ufi = NULL;
		$old_city_id = $_POST['old_city_id'];
		$city_name =  $_POST['city'];
		$description = $_POST['description'];
		$tags = $_POST['tags'];
		$cats = isset($_POST['categories']) ? $_POST['categories'] : array();
		$folder_id = 0;
		if(isset($_POST['folder_id']))$folder_id = (int)$_POST['folder_id'];
		$xml_edit = $photo->editPhoto($photo_id, $ufi, $cats, $description, $tags, $folder_id, $old_city_id, $city_name);
		if(isset($_POST['old_folder_id']) && $_POST['old_folder_id'] != $folder_id){
			$photofolder->resetLastImage((int)$_POST['old_folder_id']);
		}
		
		if($error = $xml_edit->error)$edit_msg = $error['msg'];
		else $edit_msg = 'Photo changes saved.';
		//$photo_id = NULL;//redirect to folder
		
	}else if(isset($_POST['submit_removetag'],$_POST['pid']) && $_POST['pid']==$pid){
		$xml_tag = $photo->removeTag($_POST['photo_id'], $_POST['submit_removetag']);
	}else if(isset($_POST['submit_deletefolder'],$_POST['folder_id'])){
		$xml_delete = $photofolder->remove($_POST['folder_id']);
		if($error = $xml_delete->error)$edit_msg = $error['msg'];
		else {
			$edit_msg = 'Folder has been deleted.';
			$folder_id = NULL;
		}
	}
	if(isset($xml_tag) || isset($xml_edit)){
		$pid = $pid + 1;
		$_SESSION['pid'] = $pid;
		session_write_close();
	}
	
	$js = 'var checkflag = false;
	function removeTag(){return confirm(\'Are you sure you want to delete this keyword?\')};
	function checkUncheckAll(field) {
		checkflag = !checkflag;
		if(!field.length)field = [field];
		var i = field.length;
		while(i--) field[i].checked = checkflag;
		return checkflag;
	}
	jQuery(document).ready(function(){
		new ToolTip(\'helpdelete\',\'You can only delete empty folders.\');
		new ToolTip(\'helporganize\',\'You can organize your photos by putting them in folders.\');
		jQuery(\'form#EditPhoto\').bind(\'submit\', function(){$cats = jQuery(\'input[name="categories[]"]:checked\'); if($cats.length<1){ alert(\'Please select at least one category.\'); return false;}});
	});
	';
	$page->addInlineScript($js);


	//edit photo:
	if(isset($photo_id)){
		echo '<div class="Section">';
		echo '<div class="MainHeader clearfix DottedBottom"><h1>Edit photo</h1>';
		$xml_photo = $photo->getMoreInfo($photo_id, false);
		if($error = $xml_photo->err){
			echo '</div>' . $error['msg'];
		}else{
			
			$uri = Utils::stripQueryString($_SERVER['REQUEST_URI']);
			if($xml_photo->folder_id)$folder_id = (int)$xml_photo->folder_id;
			else $folder_id = '';
			if(isset($folder_id))$uri .= '?&folder_id='.$folder_id;
			echo '<a class="sprite_admin LevelUpLink" href="'.$uri.'">Up a level</a></div>';
			if((int)$xml_photo->user_id == $user_id){

				echo '<form id="EditPhoto" method="post" action="'.$_SERVER['REQUEST_URI'].'" target="_self">';
				if(isset($edit_msg)) echo '<p class="Notice">'.$edit_msg.'</p>';
				echo '<div class="FormArea DottedBottom">';
				echo '<div class="FormRow clearfix">';
				echo '<label>Photo #'.$photo_id.'</label><img class="Thumb" src="'.Utils::getPhotoUrl($xml_photo->user_id,$photo_id,'thumb').'" />';
				echo '</div>';
				echo '</div><div class="FormArea DottedBottom">';
				echo '<div class="FormRow clearfix">';
				include CLASS_PATH.'Location.class.php';
				$location = new Location();
				$country_xml = $location->getAllCountries();
				$country_code = '';
				if($error = $country_xml->err){
					echo $error['msg'];
				}else{
					$country_code = mb_strtolower($xml_photo->country_code);
					echo '<label for="country_code">Country</label><div class="CountryDropdown DropdownContainer"><select id="country_code" name="country_code" class="sprite"><option value="">-</option>';
					foreach ($country_xml->country as $c){
						echo '<option ';
						if($country_code == mb_strtolower($c['cc'])) echo 'selected="true" ';
						echo 'value="'.$c['cc'].'">'.$c.'</option>';
					}
					echo '</select></div>';
				}
				echo '</div>';
				
				echo '<div class="FormRow clearfix">';
				echo '<label for="city">City</label><input id="city" class="text" autocomplete="off" value="'.$xml_photo->city_name.'" name="city" type="text" />';
				echo '<input name="city_id" id="city_id" type="hidden" value="" />';//we need ufi here!
				echo '<input name="old_city_id" id="old_city_id" type="hidden" value="'.$xml_photo->city_id.'" />';
				echo '</div>';
				echo '</div><div class="FormArea DottedBottom">';
				echo '<div class="FormRow clearfix">';
				echo '<label for="tags">Keywords</label>';
				$tags = $xml_photo->tags->tag;
				echo '<input type="text" class="text" name="tags" value="" />';
				echo '<div id="Tags" class="RadioGroup">';
				foreach ($tags as $tag){
					echo '<div class="Tag clearfix"><label for="submit_removetag">'.$tag.' </label><input class="RemoveTag sprite_admin" onclick="return removeTag()" type="submit" class="submit" name="submit_removetag" value="'.$tag['id'].'"/></div>';
				}
				echo '</div>';
				echo '</div>';
				echo '</div><div class="FormArea DottedBottom">';
				echo '<div class="FormRow clearfix">';
				echo '<label>Categories</label><div class="RadioGroup">';
				$xml_cat = $photo->getCategories();
				if($error = $xml_cat->err)echo $error['msg'];
				else{
					$xml_photo_cats = $photo->getCategoriesByPhotoId($photo_id);
					$photo_cats = array();
					//echo $xml_photo_cats->asXML();
					foreach ($xml_photo_cats->category_id as $c)$photo_cats[] = (string)$c;
					foreach ($xml_cat->category as $c){
						echo '<div class="clearfix"><input id="c_'.$c->id.'" onclick="return onSelectCategory(this);" type="checkbox" value="'.$c->id.'" name="categories[]"';
						if(in_array($c->id, $photo_cats)) echo ' checked="true"';
						echo '/><label for="c_'.$c->id.'">'.$c->name.'</label></div>';
					}
				}
				echo '</div>';
				echo '</div>';
				echo '<div class="FormRow clearfix">';
				echo '<label for="description">Description</label><textarea id="description" name="description" rows="3" cols="40">'.Utils::br2nl($xml_photo->description).'</textarea>';
				echo '</div>';

				$xml_folders = $photofolder->getFolders();
				$folders = $xml_folders->folder;
				
				if(count($folders)>0){
					echo '<div class="FormRow clearfix">';
					echo '<label>Organize</label>';
					echo '<div id="FolderDropdown" class="DropdownContainer"><select name="folder_id" class="sprite">';
					echo '<option value="0" class="mainfolder">Main folder</option>';
					
					foreach($folders as $folder){
						echo '<option value="'.$folder['id'].'"';
						if($folder_id == (int)$folder['id'])echo ' selected="true"';
						echo '>'.$folder['name'].'</option>';
					}
					echo '</select></div><input type="hidden" name="old_folder_id" value="'.$folder_id.'" />';
					echo '</div>';
				}
				echo '</div><div class="SubmitRow clearfix">';
				echo '<input class="submit GreenButton" type="submit" name="submit_edit" value="Save" />';
				echo '<input class="submit RedButton" onclick="return confirm(\'Are you sure you want to delete this photo?\')" type="submit" name="submit_delete" value="Delete" />';
				echo '<input type="hidden" name="pid" value="'.$pid.'" /><input type="hidden" name="photo_id" value="'.$photo_id.'" />';
				echo '</div>';
				echo '</form>';

				$page->addStyle('selectlist.css');
				$page->addScript('citylist.js');
				$js = "jQuery(document).ready(function(){var f = document.forms[0];if(f){var citylist = new CityList({inputObj:f['city']});jQuery(citylist).on('selectItem', function(evt, listItem){f['city_id'].value = listItem ? listItem.ufi : ''});var e = f['country_code'];e.onchange = function(){var v = this.options[this.selectedIndex].value;f['city'].value = '';f['city_id'].value = '';citylist.setCountryCode(v);};";
				if(strlen($xml_photo->country_code)>0) $js .= 'citylist.setCountryCode(\''.$xml_photo->country_code.'\');';
				$js .= "}});onSelectCategory = function(el){var e = document.forms[0]['categories[]'], i = e.length, n = 0;while(i--)if(el !== e[i] && e[i].checked && ++n==3)return false;return true;};";	
				$page->addInlineScript($js);
			}else echo '<p class="Error">No record found</p>';
		}
		echo '</div>';//end .Section
	}else{
		echo '<div class="OuterContentContainer">';

		//get folders+photos:
		$xml_folders = $photofolder->getFolders();
		$xml_photos = $photofolder->getPhotosByFolderId($folder_id, $offset, $photos_limit, $orderby, $search, $search_cat);
		$photos_total = (int)$xml_photos->photos['total'];
		if(!($error = $xml_folders->err))$folders = $xml_folders->folder;
		if(isset($folder_id)){
			$xml_folder = $photofolder->getFolderById($folder_id);
			//check if folder is valid:
			if($error = $xml_folder->err){
				echo '<p class="Error">'.$error['msg'].'</p>';
				$folder_id = NULL;
			}else{
				echo '<div class="MainHeader DottedBottom clearfix"><h1>Edit folder: ' . $xml_folder->folder[0]['name'] . '</h1></div>';
				$uri = Utils::stripQueryString($_SERVER['REQUEST_URI']);
				echo '<form action="'.$uri.'" name="FrmEditFolder" method="post" class="EditPhotosForm FolderForm DottedBottom clearfix">';
				echo '<a class="sprite_admin LevelUpLink" href="'.$uri.'">Up a level</a>';
				echo '<input type="hidden" name="folder_id" value="'.$folder_id.'" />';
				echo '<span class="sprite_admin FolderIcon"></span><input type="text" class="FolderName text" name="folder_name" maxlength="20" value="'.$xml_folder->folder[0]['name'].'" />';
				echo '<input type="submit" name="submit_editfolder" value="Rename" class="submit GreenButton" />';
				echo '<input class="submit RedButton"';
				if($photos_total>0)echo 'disabled="true"';
				echo ' type="submit" name="submit_deletefolder" value="Delete" />';
				if($photos_total>0)echo '<span class="strong" id="helpdelete">[?]</span>';
				if(isset($err_edit))echo '<span class="Error">'.$err_edit.'</span>';
				if(isset($msg_edit))echo '<span class="Notice">'.$msg_edit.'</span>';
				echo '</form>';
				
			}
		}else{
?>
			<div class="MainHeader DottedBottom clearfix"><h1>Edit my photos</h1></div>

<?php
			
			if(!isset($search) && $photos_total == 0){
				echo '<div class="Notice">You have no photos in the top level.</div>';
			}
?>
			<div class="FormArea EditPhotosForm DottedBottom">
				<h2>Folders</h2>
				<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" name="frmaddfolder" class="clearfix AddFolderForm" method="post">
						<label style="width:150px">New folder name <span id="helporganize">[?]</span></label>
						<input name="folder_name" class="text" value="" type="text" /><input name="submit_addfolder" type="submit" class="submit GreenButton" value="Create" /><?php 
							if(isset($error_addfolder)){
								echo '<span class="Error label">'.$error_addfolder.'</span>';
							}?>
				</form>
<?php		
			//print folder list:
			if(!isset($folders)){ 
				if($error = $xml_folders->err) echo '<div class="Error">'.$error['msg'].'</div>';
			}else{
				$num_folders = count($folders);
				if($num_folders==0){
					//echo 'No folders';
					unset($folders);//we use this later
				}else{
					echo '<ul class="Folders clearfix">'.PHP_EOL;
					$counter = 0;
					foreach($folders as $folder){
						$a = '<a href="'.Utils::stripQueryString($_SERVER['REQUEST_URI']).'?&folder_id='.$folder['id'].'">';
						echo '<li class="Folder sprite_admin"><div class="Thumb"';
						if(isset($folder['last_photo_id'])) echo ' style="background-image:url('.Utils::getPhotoUrl($user_id,$folder['last_photo_id'],'thumb').')"';		
						echo '>'.$a.'</a></div>';
						echo '<div class="FolderLabel">'.$a.$folder['name'].'</a></div></li>';
					}
					echo '</ul>';
				}	
			}
			echo '</div>';//end PhotoFolders
		}
		echo '<div class="FormArea EditPhotosForm">';
		echo '<h2>Photos</h2>';
		if(isset($search) || $photos_total>0){//do not display search if folder is empty
			echo '<form class="SearchBar clearfix" action="'.$_SERVER['REQUEST_URI'].'" name="frmsearch" method="get">';
			echo '<input type="text" class="text" name="search" alt="Search" value="';
			if(isset($search))echo $search;
			echo '" /><span class="label">by</span>';
			echo '<div class="DropdownContainer"><select class="sprite" name="search_cat">';
			$search_cats_labels = array('Keyword','City','Country','Photo id');
			foreach(PhotoFolder::$search_categories as $k=>$v){
				echo '<option value="'.$v.'"';
				if(mb_strtolower($search_cat) == $v)echo ' selected="true"';
				echo '>'.$search_cats_labels[$k].'</option>';

			}
			echo '</select></div>';

			echo '<span class="label">order</span>';
			echo '<div class="DropdownContainer"><select name="orderby" class="sprite"><option value="desc">Last added</option><option value="asc"';
			if(mb_strtolower($orderby) == 'asc')echo ' selected="true"';
			echo '>First added</option></select></div>';
			echo '<input class="submit GreenButton" type="submit" name="search_submit" value="Search" />';
			echo '<input type="button" class="submit OrangeButton ResetForm" value="Reset" />';
			echo '<input type="hidden" name="folder_id" value="'.(isset($folder_id)?$folder_id:'').'" />';
			echo '</form>';
		}

		echo '</div>';//FormArea.EditPhotosForm

		$p = $xml_photos->photos;
		if(strlen($p)>0)$photos = explode(',',$xml_photos->photos);
		else $photos = array();


		if($photos_total>0){
			echo '<a name="nav"></a>';
			echo '<form action="'.$_SERVER['REQUEST_URI'].'" id="EditPhotos" name="FrmEdit" method="post">';
			
			$l = count($photos);
			
			//navigation:
			$qs = '';
			if(isset($folder_id))$qs.='&folder_id='.$folder_id;
			if(isset($search))$qs.='&search='.urlencode($search);
			if(isset($search_cat))$qs.='&search_cat='.urlencode($search_cat);
			if(isset($orderby))$qs.='&orderby='.$orderby;
			$qs.='&total='.$photos_total.'#nav';
			$pagingnav = Utils::getPagingNav($offset, $photos_total, $photos_limit, $qs);
			//end navigation
			
			echo '<div class="clearfix">';
			echo $pagingnav;


			$uri = Utils::stripQueryString($_SERVER['REQUEST_URI']);
			$uri.='?';
			if(isset($folder_id)) $uri.= 'folder_id='.$folder_id;
			

			echo '<div class="Gallery clearfix" id="EditPicsGallery">';

			foreach($photos as $photo){
				$u = $uri.'&photo_id='.$photo;
				echo '<div class="PhotoContainer clearfix">';
					echo '<div class="GalleryPhoto PositionRelative ImageContainer" style="background-image:none;">';
					echo '<a href="'.$u.'"><img src="'.Utils::getPhotoUrl($user_id,$photo,'medium').'" id="'.$photo.'" class="gallery_image"/></a>';
					echo '</div>';
					echo '<div class="GalleryPhotoCheckbox"><label><input type="checkbox" value="'.$photo.'" name="photo_ids[]"></label></div>';
				echo '</div>';
			}
			echo '</div>';

			echo $pagingnav;
			echo '</div><div class="clearfix DottedTop EditPhotosForm">';
			$lbl = 'Delete selected';
			if(isset($folders)){
				if(isset($folder_id) || (!isset($folder_id) && count($folders)>0)){
					$lbl = 'Delete';
					echo '<a class="SelectAll" href="#" onclick="this.innerHTML=checkUncheckAll(document.forms[\'FrmEdit\'][\'photo_ids[]\'])?\'Deselect All\':\'Select All\';return false;">Select All</a>';
					echo '<label>With Selected</label>';
					echo '<span class="label">Move to Folder</span>';
					echo '<div class="FolderSelect DropdownContainer"><select class="sprite" name="folder_id">';

					if(isset($folder_id))echo '<option value="0" class="mainfolder">Main folder</option>';

					foreach($folders as $folder){
						if(isset($folder_id) && $folder_id == (int)$folder['id'])continue 1;
						echo '<option value="'.$folder['id'].'">'.$folder['name'].'</option>';
					}
					echo '</select></div>';
					echo '<input type="hidden" name="old_folder_id" value="'.(isset($folder_id)?$folder_id:'').'" />';
					echo '<input type="submit" class="submit GreenButton MovePhoto" name="submit_movetofolder" value="Move" />';
				}
			}
			echo '<input onclick="return confirm(\'Are you sure you want to delete these photos?\')" type="submit" class="submit RedButton" name="submit_delete" value="'.$lbl.'" />';
			echo '</div>';
			
			echo '</form>';
		}else{
			if(isset($msg_delete)){
				echo '<div class="Notice">'.$msg_delete.'</div>';
				unset($msg_delete);
			}else if(isset($search))echo '<div class="Notice">No photos match your search criteria.</div>';
			else echo '<div class="Notice">You have no photos in this folder.</div>';
		}
		
		if(isset($msg_delete))echo '<div class="Notice">'.$msg_delete.'</div>';
		
		echo '</div>';//OuterContentContainer
	}
?>