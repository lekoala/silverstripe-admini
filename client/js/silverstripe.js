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
            });
        });
    }
}

export default SilverStripe;
