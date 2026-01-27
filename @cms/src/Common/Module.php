<?php
namespace Apo100l\Common;
use Apo100l\Cms;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use function \Cms\Utils\cms_event;
class Module {

    protected string $src;

    protected string $name;

    protected array $controllers = [];

    protected ?string $helpers = null;

    protected ?string $filters = null;

    protected ?string $config = null;
    protected array $models = [];

    protected ?string $routes = null;

    protected ?string $hooks = null;

    public function __construct(string $name, string $src)
    {
        $this->name = $name;
        $this->src = $src;
        $this->controllers();
        $this->models();
        $this->files();
    }

    private function getFiles(?string $patch) {
        $reduce = fn ($acc, $ctr) => array_set($acc, basename($ctr, '.php'), $ctr);
        $patch = implode(DIRECTORY_SEPARATOR, array_filter([$this->src, $patch], fn ($value) => !empty($value)));
        $files = File::files($patch);
        return array_reduce($files, $reduce, []);
    }

    public function controllers() {
        $this->controllers = $this->getFiles('controllers');
    }

    public function models() {
        $this->models = $this->getFiles('models');
    }
    public function files() {
        foreach ($this->getFiles('') as $name => $patch) {
            if (property_exists($this, $name)) {
                $this->{"$name"} =  $patch;
            }
        }
    }

    public function init($name) {
        if (property_exists($this, $name)) {
            $files = !is_array($this->{"$name"}) ? [$this->{"$name"}] : $this->{"$name"};
            $files = array_filter($files, fn ($value) => !empty($value));
            foreach ($files as $file) {
                switch ($name) {
                    case 'config':
                        $config = require $file;
                        $patch = implode('.', ['cms', $this->getName()]);
                        Config::set($patch, $config);
                        break;
                        case 'filters':
                            require $file;
                            $events = array_merge(Cms::isAdmin() ? ['admin'] : [], ['front']);
                            array_map(fn ($prefix) => cms_event(event_key('filters', $prefix)), $events);
                            break;
                    default:
                        require $file;
                        break;
                }
            }
        }
    }

    public function routes()
    {
        $controls = array_keys(array_filter($this->getControllers(), fn ($_, $key) => starts_with($key, 'Admin'), ARRAY_FILTER_USE_BOTH));
        Event::fire('admin.routes.'.$this->getName(), $controls);
        if(has_lang()) {
            foreach (array_keys(conf_lang()) as $key => $lang) {
                Route::group(array('prefix' => $lang), function() use ($lang) {
                    $lang = '.'.$lang;
                    Event::fire('front.routes.'.$this->getName(),[$lang]);
                });
            }
            Event::fire('front.routes.'.$this->getName(), [null]);
        } else {
            Event::fire('front.routes.'.$this->getName(), [null]);
        }
    }

    public function getSrc(): string
    {
        return $this->src;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function getFilters(): ?string
    {
        return $this->filters;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function getRoutes(): ?string
    {
        return $this->routes;
    }

    public function getHooks(): ?string
    {
        return $this->hooks;
    }


}