<?php

use Apo100l\Sdk\Constants;
use Carbon\Carbon;

const METHOD_POST = 'POST';
const METHOD_GET = 'GET';
const METHOD_PUT = 'PUT';
const METHOD_DELETE = 'DELETE';

const CMS_SORT_ASC = 'asc';

const CMS_SORT_DESC = 'desc';

if (!function_exists('currentModule')) {
    function currentModule($file): ?string
    {
        $module = array_get(explode('@modules', $file), 1, '');
        $module = array_first(array_filter(explode('/', $module), fn ($value) => !empty($value)), fn ($value) => true);
        return $module;
    }
}


if (!function_exists('uuid')) {
    function uuid(): ?string
    {
        return implode('-', array_map(fn ($key) => mb_strtolower(str_random($key)), [8, 4, 4, 12]));
    }
}


if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return Cms::isAdmin();
    }
}

if (!function_exists('isDevelopment')) {
    function isDevelopment(): bool
    {
        return Cms::isDevelopment();
    }
}


if (!function_exists('assetsVite')) {
    function assetsVite(): string
    {
        return Cms::assetsVite();
    }
}

if (!function_exists('fetchUrlContent')) {
    function fetchUrlContent($url, $options = [])  {
        $ch = curl_init();
        $options = array_merge([
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ], $options);
        foreach ($options as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        $content = curl_exec($ch);
        curl_errno($ch) && $content = false;
        curl_close($ch);
        return $content;
    }
}

if (!function_exists('isErrors')) {
    function isErrors(): bool
    {
        return count(array_filter([
            Constants::MESSAGE_ERROR,
            Constants::MESSAGE_WARNING,
            Constants::MESSAGE_SUCCESS],
            fn ($key) => Session::has($key))) > 0;
    }
}

if (!function_exists('view_admin')) {
    function view_admin(string $key, $data = [], $prefix = 'common'): string
    {
        return view($key, $data, $prefix, 'admin');
    }
}

if (!function_exists('view_front')) {
    function view_front(string $key, $data = [], $prefix = 'common'): string
    {
        return view($key, $data, $prefix, 'front');
    }
}

if (!function_exists('view')) {
    function view(string $key, $data = [], $prefix = 'common', $mode = null): string
    {
        $mode = is_null($mode) ? isAdmin() ? 'admin' : 'front' : $mode;
        $key = array2str([$mode, $prefix, $key], '.');
        $view = View::make($key, $data);
        return $view->render();
    }
}

if (!function_exists('view_exists')) {
    function view_exists(string $key, $prefix = 'common', $mode = null): bool
    {
        $key = array2str([is_null($mode) ? isAdmin() ? 'admin' : 'front' : $mode, $prefix, $key], '.');
        return View::exists($key);
    }
}

if (!function_exists('has_view')) {
    function has_view(string $key): bool
    {
        return View::has($key);
    }
}

if (!function_exists('view_compile')) {
    function view_compile(string &$view, ?string $event = Constants::COMPILE_CONTENT, $round = 10): string
    {
        foreach (range(1, $round) as $ignored) {
            event($event, [&$view]);
        }
        return $view;
    }
}

if (!function_exists('view_compile_admin')) {
    function view_compile_admin(string &$view, ?string $event = Constants::COMPILE_CONTENT_ADMIN): string
    {
        return view_compile($view, $event, 10);
    }
}

if (!function_exists('event')) {
    function event(string $key, $data = [])
    {
        return Event::fire($key, $data);
    }
}

if (!function_exists('default_per_page')) {
    function default_per_page()
    {
        return array_first(Constants::PER_PAGE, fn () => true);
    }
}

if (!function_exists('get_per_page')) {
    function get_per_page($default = null)
    {
        $default = $default ? : default_per_page();
        $per_page = Input::get('per_page', $default);
        return is_numeric($per_page) ? $per_page : $default;
    }
}

if (!function_exists('event_key')) {
    function event_key(string $key, $prefix = ''): string
    {
        return  strtoupper(implode('.', array_filter([$prefix, $key], fn ($key) => !empty($key))));
    }
}

if (!function_exists('event_init_page_form_node')) {
    function event_init_page_form_node(array $types, callable $callback) {
        array_map(fn($type) => listen(event_key($type, Constants::INIT_PAGE_FORM), $callback), $types);
    }
}

if (!function_exists('event_init_page_form_page')) {
    function event_init_page_form_page(callable $callback) {
        listen(event_key(Constants::NODE, Constants::INIT_PAGE_FORM), $callback);
    }
}

if (!function_exists('event_init_page_form_page_type')) {
    function event_init_page_form_page_type(array $types, callable $callback) {
        array_map(fn($type) => listen(event_key(array2str([$type, Constants::NODE], '.'), Constants::INIT_PAGE_FORM), $callback), $types);
    }
}

if (!function_exists('event_init_block_form_type')) {
    function event_init_block_form_type(array $types, callable $callback) {
        array_map(fn($type) => listen(event_key($type, Constants::INIT_BLOCK_FORM), $callback), $types);
    }
}
if (!function_exists('event_init_block_form')) {
    function event_init_block_form(callable $callback) {
        listen(Constants::INIT_BLOCK_FORM, $callback);
    }
}
if (!function_exists('group_admin_menu_blocks')) {
    function group_admin_menu_blocks(array $groups): Closure
    {
        return function () use ($groups) {
            $keys = keys($groups);
            $coll = collator_create( 'ru_RU' );
            collator_asort( $coll, $keys );
            $types = Cms::getBlocks();
            $cnt = 9;
            $menu = array_reduce($keys, function ($acc, $title) use ($types, $groups, &$cnt) {
                $keys = keys(array_only($types, $groups[$title]));
                $uuid = uuid();
                $cnt = $cnt - 1;
                return array_merge($acc, [[
                    'title' => $title,
                    'url' => '#',
                    'weight' => $cnt,
                    'id' => $uuid,
                ]], array_map(fn($type, $index) => [
                    'title' => array_get($types, $type),
                    'url' => url_route(AdminBlockController::class.'@view', compact('type')),
                    'id' => uuid(),
                    'weight' => 100 - $index,
                    'parent' => $uuid
                ], $keys, range(0, count($keys) - 1)));
            }, []);
            asort($menu);
            return $menu;
        };
    }
}

if (!function_exists('encodeXML')) {
    function encodeXML(string $value): string
    {
        return  htmlentities($value, ENT_XML1);
    }
}


if (!function_exists('listen')) {
    function listen(string $key, callable $callback, $weight = 0)
    {
        return Event::listen($key, $callback, $weight);
    }
}

if (!function_exists('filter')) {
    function filter(string $key, callable $callback)
    {
        return Route::filter($key, $callback);
    }
}

if (!function_exists('url_full')) {
    function url_full(): string
    {
        return add_end_slash(URL::full());
    }
}

if (!function_exists('url_current')) {
    function url_current(): string
    {
        return add_end_slash(URL::current());
    }
}

if (!function_exists('url_route')) {
    function url_route(...$args): string
    {
        $url = call_user_func_array([URL::class, 'route'], $args);
        return add_end_slash($url);
    }
}

if (!function_exists('add_end_slash')) {
    function add_end_slash(string $url): string
    {
        $url = explode('?', $url);
        $url[0] = ends_with('/', $url[0]) ? $url[0] : $url[0].'/';
        $url = array_filter([$url[0], array_get($url, 1)], fn ($value) => !empty($value));
        return array2str($url, '?');
    }
}


if (!function_exists('get_config')) {
    function get_config($key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('array2str')) {
    function array2str($arr = [], $separator = ''): string
    {
        $arr = array_filter($arr, fn ($value) => !is_null($value));
        return implode($separator, $arr);
    }
}

if (!function_exists('set_config')) {
    function set_config($key, $value = null)
    {
        if (is_array($key)) {
            return array_map(fn ($key, $value) => Config::set($key, $value), array_keys($key), $key);
        } else {
            return Config::set($key, $value);
        }
    }
}

if (!function_exists('routes')) {
    function routes(callable $callback, $module, $isAdmin = false)
    {
        Cms::addRoutes($callback, $module, $isAdmin);
    }
}

if (!function_exists('keys')) {
    function keys(array $arr): array
    {
        return array_keys($arr);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url)
    {
        return Redirect::to($url);
    }
}

if (!function_exists('attrs')) {
    function attrs(array $attributes = []): string
    {
        $attributes = array_map(fn ($value) => is_array($value) ? implode(' ', $value) : $value, $attributes);
        return HTML::attributes($attributes);
    }
}

if (!function_exists('route_filter')) {
    function route_filter(string $name, Closure $callback): void
    {
        Route::filter($name, $callback);
    }
}

if (!function_exists('response')) {
    function response(...$args)
    {
        return call_user_func_array([Response::class, 'make'], $args);
    }
}

if (!function_exists('response_json')) {
    function response_json(...$args)
    {
        return call_user_func_array([Response::class, 'json'], $args);
    }
}


if (!function_exists('show_pagination')) {
    function show_pagination($items): bool
    {
        return $items->getTotal() >= $items->getCurrentPage() * $items->getPerPage();
    }
}

if (!function_exists('fString')) {
    function fString(?string $str, $filters = ['trim', 'rtrim', 'e']): ?string
    {
        return is_string($str) ? array_reduce($filters, fn ($acc, $fn) => call_user_func($fn, $acc), $str) : $str;
    }
}

if (!function_exists('toDateTimeString')) {
    function toDateTimeString($value, $default = null): ?string
    {
        $value = $value instanceof DateTime ? $value->format('Y-m-d H:i:s') : $value;
        return $value ? Carbon::parse($value)->toDateTimeString() : $default;
    }
}

if (!function_exists('toDateString')) {
    function toDateString(?string $value, $default = null, $format = 'd.m.Y'): ?string
    {
        return $value ? Carbon::parse($value)->format($format) : $default;
    }
}

if (!function_exists('toDateISOString')) {
    function toDateISOString(?string $value, $default = null): ?string
    {
        return $value ? Carbon::parse($value)->toIso8601String() : $default;
    }
}

if (!function_exists('toDateCarbon')) {
    function toDateCarbon(?string $value, $default = null): Carbon
    {
        return $value ? Carbon::parse($value) : $default;
    }
}

if (!function_exists('toDateValueOf')) {
    function toDateValueOf($value): int
    {
        return is_string($value) ? Carbon::parse($value)->getTimestamp() : $value->getTimestamp();
    }
}

if(!function_exists('cnc')) {
    function cnc(?string $str, $delimiter = '-'): string
    {
        $str = $str ?: '';
        $search = ['?','!','.',',',':',';','*','(',')','{','}','[',']','%','#','№','@','$','^','_','+','/','\\','=','|','"','\'','а','б','в','г','д','е','ё','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ъ','ы','э',' ','ж','ц','ч','ш','щ','ь','ю','я'];
        $replace = [$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,$delimiter,'a','b','v','g','d','e','e','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','j','i','e',$delimiter,'zh','ts','ch','sh','shch','','yu','ya'];
        $str = mb_strtolower($str, "utf-8");
        $str = str_replace($search, $replace, $str);
        $pattern = "/[^a-z0-9-_]/";
        $str = preg_replace($pattern, $delimiter, $str);
        $pattern = "/$delimiter{2,}/";
        $str = preg_replace($pattern, $delimiter, $str);
        $str = rtrim(trim($str, $delimiter), $delimiter);
        return $str ?: str_random();
    }
}

if(!function_exists('queryBuilder')) {
    function queryBuilder($query): Illuminate\Database\Query\Builder
    {
        return $query instanceof Illuminate\Database\Eloquent\Builder ? $query->getQuery() : $query;
    }
}


if(!function_exists('compile_content')) {
    function compile_content(string &$view, string $pattern, ?callable $fn, $isRegReplace = false): string
    {
        $matches = [];
        preg_match($pattern, $view, $matches);
        $key = array_get($matches, 0);
        if ($key) {
            $key = htmlentities($key);
            $keys = $fn ? call_user_func($fn, $key, $matches) : [];
            if (!$isRegReplace) {
                $search = [];
                $replace = [];
                foreach ($keys as $key => $value) {
                    foreach ([$key, strtolower($key), strtoupper($key)] as $key) {
                        $search[] = $key;
                        $replace[] = $value;
                    }
                }
                if ($search && $replace) {
                    $view = str_replace($search, $replace, $view);
                }
            } else {
                foreach ($keys as $pattern => $replace) {
                    $view = preg_replace($pattern, $replace, $view);
                }
            }
        }
        return $view;
    }
}

if(!function_exists('addAdminMenu')) {
    function addAdminMenu(callable $fn)
    {
        Cms::addAdminMenu($fn);
    }
}


if(!function_exists('boot_lang')) {
    function boot_lang($model)
    {
        if(!$model->lang and has_lang()) {
            $model->lang = detect_lang(true);
        }
    }
}

if(!function_exists('boot_creating_uid')) {
    function boot_creating_uid($model)
    {
        $model->created_uid = 'admin@admin';
        if (Auth::check()) {
            $model->created_uid = Auth::user()->email;
        }
    }
}

if(!function_exists('boot_save')) {
    function boot_save($model, $type = Constants::SAVING)
    {
        event(event_key($type, Constants::MODEL), [&$model]);
        event(event_key(class_basename($model), event_key($type, Constants::MODEL)), [&$model]);
    }
}

if(!function_exists('boot_updating_uid')) {
    function boot_updating_uid($model)
    {
        $model->updated_uid = $model->updated_uid ? : 'admin@admin';
        if (Auth::check()) {
            $model->updated_uid = Auth::user()->email;
        }
    }
}

if(!function_exists('encode_email_address')) {
    function encode_email_address( $email ) {
        $output = '';
        for ( $i = 0; $i < strlen( $email ); $i ++ ) {
            $output .= '&#' . ord( $email[ $i ] ) . ';';
        }

        return $output;
    }
}

if(!function_exists('cms_route')) {
    function cms_route( $name, $parameters = [], $lang = null ): string
    {
        $route = [$name];
        $lang = $lang ? : detect_lang(isAdmin());
        if (has_lang() && !has_one_lang()) {
            $route[] = $lang;
        }
        $url = route(implode('.', $route), $parameters);
        return add_end_slash($url);
    }
}

if(!function_exists('cms_route_page')) {
    function cms_route_page( $alias, $params = [], $lang = null ): string
    {
        return cms_route('page', array_merge(compact('alias'), $params), $lang);
    }
}

if ( ! function_exists('clean_text_ex_symbols')) {
    function clean_text_ex_symbols() {
        return [
            ',', '.', '!', '?', '"', '\'', '\\', '/', '@', '~', '`', '-', '_', '=', '+', '-', '*', '|', '|', '^', '&', '<', '>', '№', '«', '»',
        ];
    }
};
if ( ! function_exists('clean_text')) {
    function clean_text($text) {
        if($text) {
            $text = strip_tags($text);
            $text = preg_replace("/&#?[a-z0-9]{2,8};/i","",$text);
            $text = str_replace(clean_text_ex_symbols()," ", $text);
            $text = preg_replace('/\s{1,}/'," ", $text);
            $text = trim($text);
            $text = mb_strtolower($text, 'UTF-8');
            $text = explode(' ',$text);
            $text = array_map(function($val) {
                return trim(rtrim($val));
            }, array_filter($text, function($val) {
                return strlen($val) >= 3;
            }));
            $text = implode(' ', array_unique($text));
        }
        return $text;
    }
}

if(!function_exists('is_node_page')) {
    function is_node_page(Page $model): bool
    {
        return is_page_node($model);
    }
}
if(!function_exists('is_page_node')) {
    function is_page_node(Page $model, $node = Constants::NODE): bool
    {
        return $model->node === $node;
    }
}
if(!function_exists('is_page_type')) {
    function is_page_type(Page $model, string $type): bool
    {
        return $model->type === $type;
    }
}
if(!function_exists('is_page_children')) {
    function is_page_children(Page $model): bool
    {
        return $model->parent_page > 0;
    }
}
if(!function_exists('is_static_page')) {
    function is_static_page(Page $model): bool
    {
        return is_node_page($model) && $model->type === Constants::STATIC;
    }
}
if(!function_exists('is_main_page')) {
    function is_main_page(Page $model): bool
    {
        return is_node_page($model) && $model->type === Constants::PAGE_MAIN;
    }
}
if(!function_exists('is_404_page')) {
    function is_404_page(Page $model): bool
    {
        return is_node_page($model) && $model->type === Constants::PAGE_404;
    }
}
if(!function_exists('prepare_duplicate_alias')) {
    function prepare_duplicate_alias(Page $model) {
        if (!in_array(true, [is_404_page($model), is_main_page($model)]) && Page::lang($model->lang)
                ->type($model->type)
                ->node($model->node)
                ->notId($model->id)
                ->parentId($model->parent_id)->alias($model->alias)->count()) {
            $suffix = str_random(6);
            $model->alias = "{$model->alias}-$suffix";
        }
        if (in_array(true, [is_404_page($model), is_main_page($model)])) {
            $model->alias = '';
        }
        if ($model->parent_id) {
            $alias = Page::id($model->parent_id)->pluck('alias');
            $model->alias = array2str([$alias, $model->current_alias], '/');
        }
    }
}

if(!function_exists('prepare_page')) {
    function prepare_page(Page $model) {
        [$isStatic, $isNodePage] = [is_static_page($model), is_node_page($model)];
        $model->parent_id = $isNodePage && !$isStatic ? 0 : $model->parent_id;
        $model->type = $model->parent_id && $isNodePage ? Constants::STATIC : ($isNodePage ? $model->type : $model->node);
        $data = $model->data;
        array_set($data, 'title', array_get($data, 'title') ?: $model->page_title);
        $model->data = $data;
        $model->page_title = $model->page_title ?: array_get($data, 'title');
    }
}

if(!function_exists('add_page_to_index')) {
    function add_page_to_index(Page $model) {
        event(Constants::INDEX_PAGE, [$model]);
    }
}


if(!function_exists('updated_page')) {
    function updated_page(Page $model) {
        $alias = $model->alias;
        Page::parentId($model->id)->get()->map(fn ($page) => $page->save());
    }
}

if(!function_exists('deleted_page')) {
    function deleted_page(Page $model) {
        $alias = $model->alias;
        $parent_id = $model->parent_id ? : 0;
        Page::parentId($model->id)->get()->map(function ($page) use ($parent_id) {
            $page->parent_id = $parent_id;
            if (!$parent_id) {
                $page->alias = $page->current_alias;
            }
            return $page->save();
        });
    }
}


if(!function_exists('get_view_page')) {
    function get_view_page(Page $page, $mode = 'front') {
        $templates = [
            [$page->id, $page->node],
        ];
        is_node_page($page) && $templates = array_merge($templates, [[$page->type, Constants::NODE], [Constants::STATIC, Constants::NODE]]);
        !is_node_page($page) && $templates[] = ["@{$page->type}", Constants::NODE];
        $templates[] = [Constants::PAGE_404, Constants::NODE];
        $templates = array_map(fn($value) => array_merge($value, [$mode]), $templates);
        [$key, $prefix] = array_first($templates, fn ($ignored, $value) => call_user_func_array('view_exists', $value),
            [null, null]);
        return $key ? view($key, compact('page'), $prefix, $mode) : '';
    }
}

if(!function_exists('get_404_page')) {
    function get_404_page(): Page {
        $page = Page::lang(detect_lang())->type(Constants::PAGE_404)->node(Constants::NODE)->first()?: new Page(['type' => Constants::PAGE_404, 'node' => Constants::NODE]);
        $page->index = false;
        return $page;
    }
}

if(!function_exists('now')) {
    function now(): Carbon {
        return Carbon::now();
    }
}

if(!function_exists('get_model_data')) {
    function get_model_data($object, $key, $default = null) {
        if(is_array($object)) {
            return array_get($object, $key, $default);
        }
        if (is_object($object)) {
            return array_get($object->data, $key, $default);
        }
        return $default;
    }
}

if(!function_exists('get_model_img')) {
    function get_model_img($object, $key = 'img', $default = null) {
        $img = get_model_data($object, $key, $default);
        if ($img) {
            $img = array_map(fn ($value) => fString($value, ['trim', 'rtrim']), explode(',', $img));
            return array_get($img, 0, $default);
        }
        return $default;
    }
}

if(!function_exists('get_model_options')) {
    function get_model_options($object, $keys = ['id', 'data'], $optionValue = 'id', $optionLabel = 'data.title', $optionTags = 'data.tags', $prepend = []) {
        return array_reduce($object->order()->get($keys)->toArray(),
            fn ($acc, $value) => array_set($acc, array_get($value, $optionValue), [array_get($value, $optionLabel) ?: Constants::NO_NAME,
                ['data-tags' => array2str(array_get($value, $optionTags, []), ',')]]),
            $prepend);
    }
}

if(!function_exists('has_page_type')) {
    function has_page_type($type, $node = Constants::NODE) {
        return Page::lang(detect_lang())->type($type)->node($node)->first();
    }
}

if(!function_exists('get_block_by_id')) {
    function get_block_by_id($id, $type = null) {
        $block = null;
        if ($id) {
            $block = Block::active()->id($id)->lang(detect_lang())->order();
            $block = with($type ? $block->type($type) : $block)->first();
        }
        return $block;
    }
}

if(!function_exists('get_page_by_id')) {
    function get_page_by_id($id) {
        $page = null;
        if ($id) {
            $page = Page::publish()->id($id)->lang(detect_lang())->order()->first();
        }
        return $page;
    }
}

if(!function_exists('is_external_url')) {
    function is_external_url($url): array
    {
        $target = starts_with($url, 'http') && str_contains($url, domain_url()) < 0 ? '_blank' : '_self';
        return [$target === '_blank', $target];
    }
}


if(!function_exists('get_code_block')) {
    function get_code_block($name, $id = 'id'): string
    {
        return "@block[$name=$id]";
    }
}

if ( ! function_exists('cms_search_site')) {
    function cms_search_site($search) {
        $res = null;
        $q_short = [];
        $q_normal = [];
        $search = [];
        if($q and strlen($q) >= 3) {
            $search = [$q];
        }
        $q = array_map(function($val) {
            return trim(rtrim(e($val)));
        },explode(' ', $q));
        $q_short = array_unique(array_map(function($val) {
            return trim(rtrim($val));
        } ,array_filter($q, function($val) {
            return strlen($val) < 3 and $val;
        })));
        $q_normal = array_unique(array_map(function($val) {
            return trim(rtrim($val));
        } ,array_filter($q, function($val) {
            return strlen($val) >= 3 and $val;
        })));
        if($q_normal) {
            $search = $q_normal;
        }
        foreach ($q_short as $val) {
            $_s = [$val];
            foreach ($q_normal as $s) {
                $_s[] = $s;
                $search[] = implode(' ', $_s);
            }
        }
        $search = array_unique($search);
        if($search) {
            $ids = Search::whereLang($lang);
            $ids = $ids->where(function($query) use($search) {
                foreach ($search as $key => $q) {
                    $query->orWhere('title', '=', "{$q}")
                        ->orWhere('title', 'like', "{$q}%")
                        ->orWhere('title', 'like', "%{$q}")
                        ->orWhere('title', 'like', "%{$q}%")
                        ->orWhere('content', '=', "{$q}")
                        ->orWhere('content', 'like', "{$q}%")
                        ->orWhere('content', 'like', "%{$q}")
                        ->orWhere('content', 'like', "%{$q}%");
                }
                return $query;
            });
            $ids = array_pluck($ids->get()->toArray(), 'page_id');
            if($ids) {
                $res = Page::publish()->order()->whereIn('id', $ids);
                if($paginate) {
                    return $res->paginate($paginate);
                }
            }
        }
        return $res;
    }
};

