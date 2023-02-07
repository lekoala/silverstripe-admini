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
        <% include LeKoala\\Admini\\BackLink_Button %>
        <% with $Controller %>
            <% include LeKoala\\Admini\\CMSBreadcrumbs %>
        <% end_with %>
    </div>
    <div class="main-header-nav<% if $Fields.hasTabset %> main-header-tabs<% end_if %>">
        <% if $Fields.hasTabset %>
            <% with $Fields.fieldByName('Root') %>
            <bs-tabs linkable="1" responsive="1" end>
            <ul class="nav nav-tabs" role="tablist">
            <% loop $Tabs %>
                <li class="nav-item<% if extraClass %> $extraClass<% end_if %>">
                    <a class="nav-link"
                        id="tab-$id" data-bs-toggle="tab" data-bs-target="#$id" type="button"
                        role="tab" aria-controls="$id" aria-selected="false">$Title</a>
                </li>
            <% end_loop %>
            </ul>
            </bs-tabs>
            <% end_with %>
        <% end_if %>
    </div>
</header>

<section class="scroller">
    <div class="container-fluid py-3">
    <% with $Controller %>
        $EditFormTools
    <% end_with %>

    <% include SilverStripe/Forms/Fieldset %>
    </div>
</section>

<% if $Actions %>
<footer class="main-footer btn-toolbar" role="toolbar">
    <% loop $Actions %>
        $FieldHolder
    <% end_loop %>
</footer>
<% end_if %>

<% if $IncludeFormTag %>
</form>
<% else %>
</div>
<% end_if %>
