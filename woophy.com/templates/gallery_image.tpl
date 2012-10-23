<div class="GalleryPhoto PositionRelative ImageContainer{lightbox}" rel="{root_url}photo/{photo_id}{suffix}">
	<a href="{root_url}photo/{photo_id}{suffix}"><img class="gallery_image" id="{photo_id}" src="{photo_url}" alt="{alt}" /></a>
	<div class="PhotoMetaContainer js_nodisplay">
		<div class="PhotoMetaBackground PositionAbsolute"></div>
		<div class="PhotoMeta PositionAbsolute">
			<div class="Info"><a class="User{map_link}" rel="username:{user_name}" href="{root_url}member/{user_name}">{user_name}</a><span class="Location"><a class="{map_link}" rel="cityid:{city_id}" href="{root_url}city/{city}/{country}">{city}</a>, <a class="{map_link}" rel="" href="{root_url}country/{country}">{country}</a></span></div>
			<div class="Counts">
				<span class="CommentCount"><span class="Number">{comment_count}</span><span class="Icon sprite"></span></span>
				<span class="FavoriteCount"><span class="Number">{favorite_count}</span><span class="Icon sprite"></span></span>
			</div>
		</div> <!-- end PhotoMeta -->
	</div> <!-- end PhotoMetaContainer -->
</div> <!-- end GalleryPhoto -->
