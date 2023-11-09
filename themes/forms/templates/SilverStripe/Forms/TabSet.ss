<div $getAttributesHTML("class") class="$extraClass">
	<% include SilverStripe/Forms/Tabs %>

	<div class="tab-content">
	  <% loop $Tabs %>
		  <% if $Tabs %>
			$FieldHolder
		  <% else %>
			<div $getAttributesHTML("class") class="tab-pane fade<% if $extraClass %> $extraClass<% end_if %><% if IsFirst && not HasPopover %> active<% end_if %>">
				<% loop $Fields %>
					$FieldHolder
				<% end_loop %>
			</div>
		  <% end_if %>
	  <% end_loop %>
	</div>
</div>
