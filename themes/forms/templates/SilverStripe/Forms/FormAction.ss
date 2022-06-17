<% if $UseButtonTag %>
	<button $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>">
		<% if $ButtonContent %>$ButtonContent<% else %><% if Icon %><l-i name="$Icon"></l-i> <% end_if %><span>$Title.XML</span><% end_if %>
	</button>
<% else %>
	<input $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>"/>
<% end_if %>
