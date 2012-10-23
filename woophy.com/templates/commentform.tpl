<a name="bottom"></a>
<div class="CommentPost">
	<h2>Post comment</h2>
	<form method="post" action="{form_action}#bottom" id="frmpostcomment">
		<textarea name="comment_text" maxlength="750" rows="6" cols="70">{text}</textarea>
		<div>
			<input type="submit" class="submit GreenButton" name="submit_comment" value="Submit" />
			<input type="hidden" name="post_id" value="{post_id}" /><input type="hidden" name="pid" value="{pid}" /><div class="Error">{error}</div>
		</div>
	</form>
</div>