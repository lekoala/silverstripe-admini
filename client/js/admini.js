"use strict";

import admini from "../../node_modules/admini/src/js/index.js";
import SilverStripe from "./silverstripe.js";

admini.ui.addInitCallback(() => {
    SilverStripe.init();
});
