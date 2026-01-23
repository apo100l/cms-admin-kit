// найдёт ВСЕ файлы @page-script.js рекурсивно
const pageModules = import.meta.glob(
    '../views/pages/**/@page-script.{js,ts}',
    { eager: false } // важно — асинхронно
);

// загрузить всё
export async function loadAllPages() {
    await Promise.all(
        Object.values(pageModules).map(loader => loader())
    );
}
