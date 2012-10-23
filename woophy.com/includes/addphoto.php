<?php
	if(!defined('VALID_INCLUDE')) {header('HTTP/1.1 403 Forbidden'); exit();}
	
	include_once CLASS_PATH.'Photo.class.php';
	include_once CLASS_PATH.'Location.class.php';
	include_once CLASS_PATH.'PhotoFolder.class.php';
	include_once CLASS_PATH.'TravelBlog.class.php';
	include_once CLASS_PATH.'Access.class.php';

	$photo = new Photo();
	$photo->buffer = false;

	$access = ClassFactory::create('Access');

	$upload_id = uniqid('');

	$page = ClassFactory::create('Page');
	$page->addStyle('selectlist.css');
	$page->addScript('citylist.js');
	$page->addScript('uploadprogress.js');

	$js = PHP_EOL . "init_global_pre.add(function(){
		var f = document.forms[0];
		Page.uploadprogress = new UploadProgress({key:'{$upload_id}'});
		jQuery(Page.uploadprogress).on('uploadComplete', function(evt, success, msg, url, id){
			f['submitaddphoto'].disabled = false;
			if(success){
				jQuery('#FileName').text('No photo selected');
				var p = f['photo_file'];
				f['description'].value = f['tags'].value = p.value = '';
				if(p.value.length)p.parentNode.replaceChild(p.cloneNode(), p);
				var c = f['categories[]'], i = c.length;
				while(i--) c[i].checked = false;
				if(typeof url != 'undefined' && url.length>0){
					photo_page_url = Page.root_url+'photo/'+id;
					jQuery('#upload_status').append('<a href=\"'+photo_page_url+'\"><img src=\"'+url+'\"></a>');
					jQuery('#upload_status').append('<a href=\"'+photo_page_url+'\">View photo</a>');
				}
			}
		});
		onSelectCategory = function(el){
			var e = f['categories[]'], i = e.length, n = 0;
			while(i--)if(el !== e[i] && e[i].checked && ++n==3)return false;
			return true;
		};
		onSubmitForm = function(){
			Page.uploadprogress.clearStatus();
			var e = ['photo_file','country_code','city_id'];
			var a = ['Please select a photo.','Please select a country.','Please enter a city.'];
			for(var i=0,l=a.length;i<l;i++){
				var h = document.getElementById('hdr_'+e[i]);
				if(f[e[i]].value.length==0){
					h.className = 'error';
					alert(a[i]);
					document.location.hash = e[i];
					return false;
				}else h.className = '';
			}
			if(jQuery('#description').val()=='describe your photo'){
				jQuery('#description').val('');
			}
			var c = f['categories[]'], i = c.length, h=document.getElementById('hdr_categories');
			while(i--)if(c[i].checked){
				f['submitaddphoto'].disabled = true;
				h.className = '';";
		//if(function_exists('apc_fetch')) echo 'uploadprogress.startProgress();';
		if(function_exists('uploadprogress_get_info')) $js .= 'jQuery(\'#ProgressOuter\').removeClass(\'nodisplay\');Page.uploadprogress.startProgress();';
		else $js .= 'Page.uploadprogress.setStatus(\'Uploading...\');';
		$js .= "return true;
			}
			document.location.hash = 'categories';
			h.className = 'error';
			alert('Please select a category.');
			return false;
		}" . PHP_EOL;
		$js .= "var citylist = new CityList({inputObj:f['city']});
		jQuery(citylist).on('selectItem', function(evt, listItem){f['city_id'].value = listItem ? listItem.ufi : '';});
		jQuery('select[name=\"country_code\"]').on('change mouseup', function(){
			var v = jQuery(this).val();
			if(v == citylist.getCountryCode()) return;
			var c = jQuery('input[name=\"city\"]').val('');
			jQuery('input[name=\"city_id\"]').val('');
			citylist.setCountryCode(v);
			//f['city'].focus();//causes IE to submit form
		});
		jQuery('input[name=\"city\"]').attr('disabled', 'true');
	});" . PHP_EOL;
	$page->addInlineScript($js);
?>
<div class="Section">
	<div class="MainHeader DottedBottom clearfix"><h1>Upload new photo</h1></div>
	<form action="<?php echo ROOT_PATH?>upload" name="frmprofile" id="AddPhoto" method="post" target="target_upload" enctype="multipart/form-data" onsubmit="return onSubmitForm();">
		<input type="hidden" id="UPLOAD_IDENTIFIER" name="UPLOAD_IDENTIFIER" value="<?php echo $upload_id?>"><?php //TRICKY:order matters! hidden input before file input!!
		//<input type="hidden" name="APC_UPLOAD_PROGRESS" value="$upload_id"/>
?><a name="photo_file"></a>
		<div class="FormArea DottedBottom">
			<h2>Select your photo</h2>
			
			<div class="FormRow clearfix">
				<label id="hdr_photo_file" for="photo_file">Photo</label>
				<div class="InputArea clearfix"><input type="file" id="FileSelect" name="photo_file" accept="image/pjpeg,image/x-png,image/jpeg, image/gif" value="" /></div>
			</div>
			<div class="FormRow clearfix">
				<label for="photo_file">Folder</label>
				<div class="InputArea clearfix">
					<div id="FolderSelectContainer" class="clearfix">
<?php
	$photofolder = new PhotoFolder();
	$xml_folders = $photofolder->getFolders();
	$folders = $xml_folders->folder;
	if(count($folders)>0){
		echo '<div id="FolderDropdown" class="DropdownContainer"><select name="folder_id" class="sprite">';
		echo '		<option value="0" class="mainfolder">Main folder</option>';
		foreach($folders as $folder){
			echo '		<option value="'.$folder['id'].'">'.$folder['name'].'</option>';
		}
		echo '		</select></div>';
	}else{
		echo '<span class="Notice">You don\'t have any folders yet</span>';
	}
?>
					</div>
				</div>
			</div>
		<a name="country_code"></a>
		</div>
		<div class="FormArea DottedBottom">
			<h2>Where was this photo taken?</h2>
			<div class="FormRow clearfix">
				<label id="hdr_country_code" for="country_code">Country</label>
				<div class="InputArea clearfix">
					<div class="CountryDropdown DropdownContainer"><select name="country_code" class="sprite">
							<option value="">-</option>
<?php	
		$location = new Location();
		$country_xml = $location->getAllCountries();
		if($error = $country_xml->err) echo $error['msg'];
		else{
			foreach ($country_xml->country as $c)echo '<option value="'.$c['cc'].'">'.$c.'</option>';
		}
?>
					</select></div>
				</div>
			</div>
			<a name="city_id"></a>
			<div class="FormRow clearfix">
				<label id="hdr_city_id" for="city">City</label>
				<div class="InputArea clearfix">
					<input id="CityInput" class="text" autocomplete="off" value="" name="city" type="text" />
					<input name="city_id" type="hidden" value="" />
				</div>
			</div>
		</div>
		
		<div class="FormArea">
			<div class="FormRow clearfix">
				<label for="tags">Keywords</label>
				<div class="InputArea clearfix">
					<input type="text" class="text" name="tags" alt="Separate keywords with commas or spaces" value="" />
				</div>
			</div>
			<div class="FormRow clearfix">
				<label id="hdr_categories" for="categories">Categories</label>
				<div class="InputArea clearfix">	
<?php
		$xml = $photo->getCategories();
		if($error = $xml->err){
			$td1 = '&nbsp;';
			$td2 = $error['msg'];
		}else{
			$td1 = $td2 = '';
			foreach ($xml->category as $c){
				echo '<div class="Category clearfix">';
				echo '<input id="c_'.$c->id.'" onclick="return onSelectCategory(this);" type="checkbox" value="'.$c->id.'" name="categories[]"/><label for="c_'.$c->id.'">'.$c->name.'</label>';
				echo '</div>';
			}
		}
?>
				</div>
			</div>
			<div class="FormRow clearfix">
				<label for="description">Description</label>
				<div class="InputArea clearfix">
					<textarea id="description" name="description" rows="3" cols="40" alt="Describe your photo"></textarea>
				</div>
			</div>
		</div>

		<div class="FormRow SubmitRow clearfix">
			<div class="small">By uploading this photo I state that I have read the <a href="<?php echo ROOT_PATH?>termsofuse" target="_blank">terms of use</a> and agree with them.</div>
			<input type="submit" class="OrangeButton submit" name="submitaddphoto" value="Upload your photo" />
			<div class="Cancel">Or <a href="<?php echo ROOT_PATH ?>account/upload">cancel</a></div>
			<div id="ProgressOuter" class="sprite_admin nodisplay"><div id="ProgressInner" class="sprite_admin"></div></div>
			<span id="upload_status" class="small" style="width:300px;"></span>
		</div>

	</form>
<iframe id="target_upload" name="target_upload" src="" style="width:0;height:0;border:0"></iframe>
</div>
