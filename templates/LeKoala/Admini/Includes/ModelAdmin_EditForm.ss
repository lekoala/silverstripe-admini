<%-- this basically serves simply to render the first level list --%>
<div class="main-container">
    <section class="scroller">
        <div class="container-fluid py-3">
        <% with $Controller %>
            $EditFormTools
        <% end_with %>

        <% include SilverStripe/Forms/Fieldset %>
        </div>
    </section>
</div>
