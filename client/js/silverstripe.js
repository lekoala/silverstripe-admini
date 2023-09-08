import initialize from "../../node_modules/admini/src/js/utils/initialize.js";

let pingIntervalSeconds = 0;

class SilverStripe {
    static init() {
        this.attachShowOnClick();
        this.subsiteSelector();
        this.ping();
    }

    static subsiteSelector() {
        initialize("#sidebar-selector", (dropdown) => {
            dropdown.addEventListener("change", (ev) => {
                const val = dropdown.value;
                var queryParams = new URLSearchParams(window.location.search);
                queryParams.set("SubsiteID", val);
                window.location.replace(
                    `${window.location.pathname}?${queryParams}`
                );
            });
        });
    }

    static attachShowOnClick() {
        initialize(".showOnClick > a", (a) => {
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
        // Already configured
        if (pingIntervalSeconds > 0) {
            return;
        }
        pingIntervalSeconds = 5 * 60;

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
            }).then((response) => {
                onSessionLost(response);
            });
        }, pingIntervalSeconds * 1000);
    }
}

export default SilverStripe;
