<% if $Message %>
<p id="{$FormName}_error" class="alert $BootstrapAlertType">$Message</p>
<% else %>
<p id="{$FormName}_error" class="alert $BootstrapAlertType" style="display: none"></p>
<% end_if %>

<fieldset>
    <% if $Legend %><legend>$Legend</legend><% end_if %>
    <% loop $Fields %>
        $FieldHolder
    <% end_loop %>
</fieldset>
