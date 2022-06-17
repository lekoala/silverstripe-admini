<ul class="nav nav-tabs">
<% loop $Tabs %>
    <li class="nav-item $FirstLast $MiddleString $extraClass" role="presentation">
        <button class="nav-link<% if IsFirst %> active<% end_if %>" id="tab-$id"
            data-bs-toggle="tab" data-bs-target="#$id"
            type="button" role="tab" aria-controls="$id" aria-selected="<% if IsFirst %>true<% else %>false<% end_if %>">
            $Title
        </button>
    </li>
<% end_loop %>
</ul>
