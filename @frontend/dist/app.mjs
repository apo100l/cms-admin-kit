import "./chunk-3GDQP6AS.mjs";

// src/shims/bootstrap.ts
import jQuery from "jquery";

// src/shims/pages.ts
var pageModules = import.meta.glob(
  "../views/pages/**/@page-script.{js,ts}",
  { eager: false }
  // важно — асинхронно
);
async function loadAllPages() {
  await Promise.all(
    Object.values(pageModules).map((loader) => loader())
  );
}

// src/shims/utils.ts
var utilsModules = import.meta.glob(
  "../@utils/**/*.{js,ts}",
  { eager: false }
  // важно — асинхронно
);
async function loadAllUtils() {
  await Promise.all(
    Object.values(utilsModules).map((loader) => loader())
  );
}

// src/shims/bootstrap.ts
(async () => {
  window.$ = jQuery;
  window.jQuery = jQuery;
  await import("popper.js");
  await import("bootstrap");
  await import("./ace-FWH5Z6O6.mjs");
  await loadAllUtils();
  await loadAllPages();
})();

// src/app.ts
import "@fortawesome/fontawesome-free/css/all.css";
import "bootstrap/dist/css/bootstrap.min.css";
//# sourceMappingURL=app.mjs.map