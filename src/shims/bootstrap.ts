import jQuery from 'jquery';
import {loadAllPages} from "./pages";
import {loadAllUtils} from "./utils";
(async () => {
(window as any).$ = jQuery;
(window as any).jQuery = jQuery;

await import('popper.js');

await import('bootstrap');

await import ('../js/ace.js' as any);
await loadAllUtils();
await loadAllPages();
})();
