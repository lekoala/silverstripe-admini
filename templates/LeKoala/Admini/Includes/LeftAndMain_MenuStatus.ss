<div class="sidebar-profile-mini" data-scope-fragment="1">
    <% with $CurrentMember %>
    <a href="{$adminiURL}myprofile" class="btn sidebar-profile-mininame"
        title="<%t LeKoala\Admini\LeftAndMain.PROFILE '{name} profile' name=$Name %>"
        data-scope-hint="main-scope">
        <l-i name="person"></l-i> <span><% if $FirstName && $Surname %>$FirstName $Surname<% else_if $FirstName %>$FirstName<% else %>$Email<% end_if %></span>
    </a>
    <% end_with %>
    <a href="$LogoutURL" class="btn" title="<%t LeKoala\Admini\LeftAndMain.LOGOUT 'Log out' %>">
        <l-i name="exit_to_app"></l-i>
    </a>
</div>
