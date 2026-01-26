import jQuery from 'jquery';
import {loadAllPages} from "./pages";
import {loadAllUtils} from "./utils";
(async () => {
(window as any).$ = jQuery;
(window as any).jQuery = jQuery;

    const PopperModule = await import(
        'popper.js/dist/umd/popper.js' as any);

    // 🔥 ключевая строка
    (window as any).Popper =
        (PopperModule as any).default ?? PopperModule;

    await import('bootstrap/dist/js/bootstrap.js' as any);

await import ('../js/ace.js' as any);
await loadAllUtils();
await loadAllPages();
})();
