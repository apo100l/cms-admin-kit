<?php
namespace Apo100l;
use Apo100l\Common\Module;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use function Cms\Utils\array2str;
use function Cms\Utils\get_config;

class Cms {

    private array $modules = [];

    private array $inits = ['helpers', 'config', 'filters', 'hooks', 'controllers', 'models', 'routes'];

    public function __construct($dir = '@modules')
    {
        $dirs = file_exists(app_path($dir)) ? File::directories(app_path($dir)) : [];
        $dirs = array_reduce($dirs, fn ($acc, $patch) => array_set($acc, basename($patch), $patch), []);
        ksort($dirs);
        $callback = fn ($name, $patch) => new Module($name, $patch);
        $this->modules = array_map($callback, array_keys($dirs), $dirs);
    }

    public function version(): string
    {
        $php = PHP_VERSION;
        return "SDK is alive for PHP $php";
    }



    static function setLocale ($locale = [LC_ALL => 'ru_RU.UTF-8', LC_TIME => 'ru_RU.UTF-8', 'timezone' => 'Europe/Moscow', 'locale' => 'ru'])
    {
        foreach ($locale as $k => $v) {
            switch ($k) {
                case LC_ALL:
                case LC_TIME:
                    setlocale($k, $v);
                    break;
                case 'locale':
                    Carbon::setLocale('ru');
                    break;
                case 'timezone':
                    date_default_timezone_set($v);
                    break;
            }
        }
    }

    public function init()
    {
        foreach ($this->inits as $init) {
            array_map(fn ($module) => $module->init($init), $this->modules);
        };
    }

    public function routes()
    {
        array_map(fn ($module) => $module->routes(), $this->modules);
    }


    public static function isAdmin(): bool {
        return Request::is('admin*');
    }

    public static function isDevelopment(): bool {
        return App::environment('development');
    }

    public static function assetsVite(): string {
        if (self::isDevelopment()) {
            $assets = [
                '<script type="module" src="http://localhost:3000/@vite/client"></script>',
                '<link rel="stylesheet" href="http://localhost:3000/src/styles/styles.scss">',
                '<script type="module" src="http://localhost:3000/src/app.tsx"></script>'
            ];
            return array_reduce($assets, fn ($acc, $item) => $acc."\n".$item, '');
        }
        $patch = 'assets/@frontend/index.html';
        $patch = public_path($patch);
        $assets = file_get_contents($patch);
        return $assets === false ? '' : $assets;
    }

    function addRoutes(callable $callable, $files = __FILE__, $isAdmin = false): Cms
    {
        $patch = implode('.', [($isAdmin ? 'admin' : 'front'), 'routes', currentModule($files)]);
        Event::listen($patch, $callable);
        return $this;
    }

    static function addAdminMenu(callable $fn): void
    {
        $listen = fn(&$menu = []) => array_map(function ($item) use (&$menu) {
            $id = array_get($item, 'id', uuid());
            $item = array_set($item, 'id', $id);
            $menu[$id] = $item;
        }, $fn());
        \Cms\listen('admin.hooks.menu', $listen);
    }

    static function getAdminMenu(): array
    {
        $menu = [];
        \Cms\event('admin.hooks.menu', [&$menu]);
        usort($menu, fn($a, $b) => array_get($b, 'weight', 0) - array_get($a, 'weight', 0));
        return $menu;
    }

    public function getBlocks(): array {
        return $this->getConfig('typeBlock');
    }

    public function getCodes(): array {
        return $this->getConfig('code');
    }

    public function getNodes(): array {
        return $this->getConfig('node');
    }

    public function getForms(): array {
        return $this->getConfig('forms');
    }


    public function getTypesPage(): array {
        return $this->getConfig('typePage');
    }

    private function getConfig($key = null): array {
        $types = [];
        foreach ($this->modules as $module) {
            $types = array_merge($types, get_config($this->getConfigKey($module->getName(), $key), []));
        }
        $coll = collator_create( 'ru_RU' );
        collator_asort( $coll, $types );
        return $types;
    }

    private function getConfigKey($key, $suffix = null): string
    {
        $arr = array_filter(['cms', $key, $suffix], fn($value) => !empty($value));
        return array2str($arr, '.');
    }
}