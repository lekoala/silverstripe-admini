<!-- FieldGroup_holder_small -->
<div id="$HolderID" class="input-group-item field field--small<% if $extraClass %> $extraClass<% end_if %>">
    <% if $Title %>
		<label for="$ID" id="title-$ID" class="form__fieldgroup-label">$Title</label>
	<% end_if %>
	<$Tag id="$ID"
		<% if $Title %>aria-labelledby="title-$ID"<% end_if %>
		class="form__fieldgroup form-field<% if not $Title %> form-field--no-label<% end_if %><% if $Zebra %> form__fieldgroup-zebra<% end_if %><% if $extraClass %> $extraClass<% end_if %>">
		<%-- Note: _holder_small.ss overrides CompositeField.ss to force nested $SmallFieldHolder --%>
		<% loop $FieldList %>
			$SmallFieldHolder
		<% end_loop %>
	</$Tag>
</div>
