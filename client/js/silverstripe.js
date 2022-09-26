class SilverStripe {
    static init() {
        this.attachShowOnClick();
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
