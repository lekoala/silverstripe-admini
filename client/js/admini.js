"use strict";

import Cookies from "js-cookie";
// import * as bootstrap from "bootstrap";
// import * as bootstrap from "../../node_modules/admini/src/js/custom-bootstrap.js";
import BSN from "../../node_modules/admini/src/js/custom-bsn.js";
import AdminiUi from "../../node_modules/admini/src/js/ui.js";
// import AdminiForms from "../../node_modules/admini/src/js/forms.js";
import escapeHTML from "../../node_modules/admini/src/js/bs-companion/escape-html";
import toaster from "../../node_modules/admini/src/js/bs-companion/toaster.js";
import modalizer, { modalizerConfirm } from "../../node_modules/admini/src/js/bs-companion/modalizer.js";
import SilverStripe from "./silverstripe.js";

// Make globally available
// window.bootstrap = bootstrap;
window.bootstrap = BSN; // Alias for scripts sharing the same api as Bootstrap 5
window.BSN = BSN;
window.Cookies = Cookies;

// let forms = new AdminiForms();
let ui = new AdminiUi();
let init = () => {
  BSN.init();
  window.admini.ui.init();
  SilverStripe.init();
//   window.admini.forms.init();
};

window.admini = window.admini || {};
window.admini = Object.assign(window.admini, {
  // Third party
  toaster,
  modalizer,
  modalizerConfirm,
  escapeHTML,
  // Our libs
  ui,
//   forms,
  init,
});
