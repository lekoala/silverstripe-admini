"use strict";

// import * as bootstrap from "bootstrap";
import bootstrap from "../../node_modules/admini/src/js/custom-bootstrap.js";
import "bs-companion/bs-companion.js";
import AdminiUi from "../../node_modules/admini/src/js/ui.js";
import escapeHTML from "../../node_modules/admini/src/js/utils/escape-html.js";
import SilverStripe from "./silverstripe.js";

let ui = new AdminiUi();
let init = () => {
    window.admini.ui.init();
    SilverStripe.init();
};

window.admini = window.admini || {};
window.admini = Object.assign(window.admini, {
    // Third party
    escapeHTML,
    // Our libs
    ui,
    //   forms,
    init,
});
