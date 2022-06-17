<%-- this allow simple toast message to be generated from php. Please prefer using toaster js function --%>
<div class="toast-container position-absolute p-3 bottom-0 end-0" style="z-index:1050">
<% if ToastMessage %>
    <div class="toast align-items-center text-white bg-$ToastMessage.ThemeColor border-0 " role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
            $ToastMessage.Message
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
<% end_if %>
</div>

