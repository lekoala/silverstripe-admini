<nav aria-label="breadcrumb">
    <div class="breadcrumb">
        <% loop $Breadcrumbs %>
            <% if $Last %>
            <li class="breadcrumb-break"></li>
            <li class="breadcrumb-item active" aria-current="page">$Title<% if $Extra %>$Extra<% end_if %></li>
            <% else %>
            <li class="breadcrumb-item"><a href="$Link">$Title<% if $Extra %>$Extra<% end_if %></a></li>
            <% end_if %>
        <% end_loop %>
    </div>
</nav>
