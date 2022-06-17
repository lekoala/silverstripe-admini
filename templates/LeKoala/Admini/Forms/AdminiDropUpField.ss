<button id="$ID" type="button" class="btn btn-default" data-bs-toggle="dropdown" aria-expanded="false">
<l-i name="more_vert" size="24" style="--size:24px;"></l-i>
</button>
<ul class="dropdown-menu" aria-labelledby="$ID">
  <% loop $FieldList %>
  <li>$Field</li>
<% end_loop %>
</ul>
