// найдёт ВСЕ файлы @page-script.js рекурсивно
const utilsModules = import.meta.glob(
    '../@utils/**/*.{js,ts}',
    { eager: false } // важно — асинхронно
);

// загрузить всё
export async function loadAllUtils() {
    await Promise.all(
        Object.values(utilsModules).map(loader => loader())
    );
}
