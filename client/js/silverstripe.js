class SilverStripe {
    static init() {
        this.attachShowOnClick();
        this.subsiteSelector();
    }

    static subsiteSelector() {
        const dropdown = document.querySelector("#sidebar-selector");
        if (dropdown) {
            dropdown.addEventListener("change", (ev) => {
                const val = dropdown.value;
                var queryParams = new URLSearchParams(window.location.search);
                queryParams.set("SubsiteID", val);
                window.location.replace(
                    `${window.location.pathname}?${queryParams}`
                );
            });
        }
    }

    static attachShowOnClick() {
        document.querySelectorAll(".showOnClick > a").forEach((a) => {
            a.addEventListener("click", (e) => {
                e.preventDefault();
                const hidden = a.parentElement.querySelector(".d-none");
                hidden.classList.remove("d-none");
                a.classList.add("d-none");

                // add value to hidden field if any
                const input =
                    a.parentElement.querySelector("input[type=hidden]");
                if (input) {
                    input.value = 1;
                }
            });
        });
    }
}

export default SilverStripe;
