<% if $IncludeFormTag %>
<form class="main-container" $FormAttributes>
<% else %>
<div class="main-container">
<% end_if %>

<header class="main-header">
    <div class="main-header-sidebar">
        <button type="button" class="btn btn-primary btn-flex btn-square rounded-0" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
            <l-i name="menu"></l-i>
        </button>
    </div>
    <div class="main-header-info">
        <nav aria-label="breadcrumb">
            <div class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    <% if $SectionTitle %>
                        $SectionTitle
                    <% else %>
                        <%t LeKoala\Admini\ModelAdmin.Title 'Data Models' %>
                    <% end_if %>
                </li>
            </div>
        </nav>
    </div>
    <div class="main-header-nav main-header-tabs">
        <bs-tabs responsive="1" end>
        <ul class="nav nav-tabs" role="tablist">
        <% loop $ManagedModelTabs %>
            <li class="nav-item <% if extraClass %>$extraClass<% end_if %>">
                <a class="nav-link<% if $LinkOrCurrent == 'current' %> active<% end_if %>" href="$Link">$Title</a>
            </li>
        <% end_loop %>
        </ul>
        </bs-tabs>
    </div>
</header>

$EditForm

<% if $IncludeFormTag %>
</form>
<% else %>
</div>
<% end_if %>
