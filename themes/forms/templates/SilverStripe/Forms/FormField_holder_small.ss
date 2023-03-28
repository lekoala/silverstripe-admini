<div id="$HolderID" class="input-group-item field field--small<% if $extraClass %> $extraClass<% end_if %>">
	<% if $Title %>
		<label for="$ID" id="title-$ID" class="form__fieldgroup-label">$Title.RAW</label>
	<% end_if %>
	$Field
</div>
