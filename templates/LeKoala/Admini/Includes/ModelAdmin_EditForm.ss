<!-- ModelAdmin_EditForm -->
<%-- this basically serves simply to render the first level list --%>
<section class="scroller">
    <div class="container-fluid py-3">
    <% with $Controller %>
        $EditFormTools
    <% end_with %>

    <% include SilverStripe/Forms/Fieldset %>
    </div>
</section>
