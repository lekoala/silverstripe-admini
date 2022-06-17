<% if $IncludeFormTag %>
<form $AttributesHTML>
<% end_if %>
    <% include SilverStripe/Forms/Fieldset %>

	<% if $Actions %>
	<div class="btn-toolbar">
		<% loop $Actions %>
			$Field
		<% end_loop %>
	</div>
	<% end_if %>
<% if $IncludeFormTag %>
</form>
<% end_if %>
