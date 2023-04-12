<sco-pe id="sidebar-scope">
    <aside id="sidebar" class="sidebar" data-bs-scroll="true">
        <% include LeKoala\\Admini\\LeftAndMain_MenuLogo %>

        <% if ListSubsitesExpanded %>
        <%-- Subsites --%>
        <select name="" id="sidebar-selector" class="form-select">
        <% loop $ListSubsitesExpanded %>
            <option value="$ID" $CurrentState<% if not $CurrentState %> style="color:$Color;background:$BackgroundColor"<% end_if %>>$Title.RAW&nbsp;&nbsp;</option>
        <% end_loop %>
        </select>
        <% end_if %>

        <% include LeKoala\\Admini\\LeftAndMain_MenuStatus %>

        <div class="sidebar-content scroller">
            <% include LeKoala\\Admini\\LeftAndMain_MenuList %>
        </div>

        <% include LeKoala\\Admini\\LeftAndMain_MenuFooter %>
    </aside>
</sco-pe>
