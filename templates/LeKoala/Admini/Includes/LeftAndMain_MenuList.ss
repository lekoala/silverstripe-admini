<div class="sidebar-content scroller" data-scope-fragment>
    <ul class="sidebar-nav">
        <% loop $MainMenu %>
            <% if Header %>
            <li class="sidebar-nav-header">$Title</li>
            <% else %>
            <li class="sidebar-item $FirstLast" id="Menu-$Code" title="$Title.ATT">
                <a href="$Link" data-scope-hint="main-scope" class="sidebar-link <% if $LinkingMode != 'link' %>active<% end_if %>" $AttributesHTML><l-i name="$IconName"></l-i> <span>$Title</span></a>
                <% if Badge %>
                <span class="sidebar-badge badge bg-primary js-mobile-tooltip" data-bs-toggle="tooltip">$Badge</span>
                <% end_if %>
            </li>
            <% end_if %>
        <% end_loop %>
    </ul>

    <% if SidebarCta %>
    <% with SidebarCta %>
    <!-- this will collapse to a button when sidebar is collapsed -->
    <div class="sidebar-cta dropdown">
        <button class="btn dropdown-toggle" id="sidebar-cta-toggle" aria-expanded="false">
            <l-i name="$IconName"></l-i>
        </button>
        <div class="sidebar-cta-content dropdown-menu">
            $Content
        </div>
    </div>
    <% end_with %>
    <% end_if %>
</div>
