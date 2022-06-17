<%--
    use this templates if your tabs are rendered elsewhere in your UI
    active pane is set in js
--%>
<div class="tab-content">
	<% loop $Tabs %>
		<% if $Tabs %>
			$FieldHolder
		<% else %>
			<div id="$id" class="tab-pane">
				<% loop $Fields %>
					$FieldHolder
				<% end_loop %>
			</div>
		<% end_if %>
	<% end_loop %>
</div>
