<% cached 'menufooter' %>
<div class="sidebar-footer" data-scope-fragment>
    <button class="btn btn-default sidebar-toggle js-sidebar-toggle">
        <l-i name="menu_open"></l-i>
    </button>
    <div class="dropup">
        <button class="btn btn-default" id="help-dropdown-btn" data-bs-toggle="dropdown">
        <l-i name="help_outline"></l-i>
        </button>
        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="help-dropdown-btn">
        <% loop getHelpLinks %>
        <li><a class="dropdown-item" href="$URL">$Title</a></li>
        <% end_loop %>
        <li>
            <hr class="dropdown-divider" />
        </li>
        <p class="m-3 mb-0">
            <small>$CMSVersion</small>
        </p>
        </ul>
    </div>
</div>
<% end_cached %>
