<p id="$ID" tabIndex="0" class="form-control<% if $extraClass %> $extraClass<% end_if %>" <% include SilverStripe/Forms/AriaAttributes %>>
	$Value
</p>
<% if $IncludeHiddenField %>
	<input $getAttributesHTML("id", "type") id="hidden-{$ID}" type="hidden" />
<% end_if %>
