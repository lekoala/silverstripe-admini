<% if ListSubsitesExpanded %>
<%-- Subsites --%>
<select name="" id="sidebar-selector" class="form-select" data-scope-fragment>
<% loop $ListSubsitesExpanded %>
    <option value="$ID" $CurrentState<% if not $CurrentState %> style="color:$Color;background:$BackgroundColor"<% end_if %>>$Title.RAW&nbsp;&nbsp;</option>
<% end_loop %>
</select>
<% end_if %>
