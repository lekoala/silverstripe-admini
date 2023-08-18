class SilverStripe {
    static init() {
        this.attachShowOnClick();
        this.subsiteSelector();
        this.ping();
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

    static ping() {
        const pingIntervalSeconds = 5 * 60;

        let interval = null;
        var loginPopup = null;

        let onSessionLost = async (response) => {
            if (response.status < 400) {
                return;
            }
            const text = await response.text();
            if (text != 0) {
                return;
            }
            // only open a new window when window doesn't exist or it was previously closed
            if (!loginPopup || loginPopup.closed) {
                loginPopup = window.open("Security/login");

                if (!loginPopup) {
                    alert("Please enable pop-ups for this site");

                    // stop bothering people if they don't want pop-ups...
                    clearInterval(interval);
                }
            }

            if (loginPopup) {
                loginPopup.focus();
            }
        };

        // setup pinging for login expiry
        interval = setInterval(() => {
            fetch("Security/ping", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: postData,
            }).then((response) => {
                onSessionLost(response);
            });
        }, pingIntervalSeconds * 1000);
    }
}

export default SilverStripe;
