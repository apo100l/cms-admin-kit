# CMS-Kit AI Coding Instructions

## Project Overview
This is a **dual-stack CMS framework** for Laravel 4:
- **@cms/**: PHP SDK library using Laravel 4.2, with modular architecture supporting dynamic module loading
- **@frontend/**: TypeScript/Bootstrap admin UI compiled via tsup, served through Vite during development

## Architecture Patterns

### Module System (@cms)
- **Dynamic Module Loading**: `Cms` class scans `app/@modules` directory and instantiates `Module` objects per directory
- **Module Structure**: Each module contains: `controllers/`, `models/`, `helpers.php`, `filters.php`, `hooks.php`, `config.php`, `routes.php`
- **Lifecycle**: `Module::init()` executes in this order: helpers → config → filters → hooks → controllers → models → routes
- **Config Scoping**: Module configs loaded via `Config::set("cms.{moduleName}", $config)` pattern

**Reference**: [Cms.php](src/Cms.php#L16-L22), [Module.php](src/Common/Module.php#L33-L79)

### Event System
- **Namespaced Events**: Routes prefixed with module context: `"admin.routes.{moduleName}"` or `"front.routes.{moduleName}"`
- **Admin vs Frontend**: `Cms::isAdmin()` checks `Request::is('admin*')` to switch context; filters/hooks can differ by environment
- **Direct Dispatch**: Use `Event::listen($patch, $callable)` from `addRoutes()` method for route registration

**Reference**: [Cms.php](src/Cms.php#L90-L104)

### Vite Asset Integration
- **Dev Mode**: Injects Vite client + local dev server scripts (`http://localhost:3000`)
- **Prod Mode**: Parses compiled `assets/@frontend/index.html` into app views
- **Helper**: `Cms::assetsVite()` or `cms_assets_vite()` handles switching

**Reference**: [Cms.php](src/Cms.php#L74-L87)

## Developer Workflows

### PHP Backend (@cms)
```bash
# No tests configured - patterns discovered through module inspection
# Extends Laravel 4.2 facades (Route, Config, Event, File, Request, App)
# Uses Carbon for locale/timezone: Cms::setLocale(['timezone' => 'Europe/Moscow'])
```

### Frontend Build (@frontend)
```bash
npm run build          # tsup: compiles src/app.ts to public/assets
npm run dev           # tsup --watch: live rebuild, browser dev at http://localhost:3000
npm run serve         # vite: preview dev assets (distinct from tsup watch)
npm run prepublishOnly # auto-runs build before npm publish
```

**Key Dependency**: tsup (not traditional webpack/vite for build), Vite as preview server

## Project-Specific Conventions

### PHP Naming
- **Namespaces**: `Apo100l\*` (SDK library), `Cms\Utils\*` (helpers namespace)
- **Config Keys**: Flattened dot-notation, e.g., `config('cms.moduleName.setting')`
- **Modules**: Must be `PascalCase` directories in `@modules/`; kebab-case helpers prefixed `cms_`

### Helper Functions (src/Utils/helpers.php)
- Wrapper functions (e.g., `cms_uuid()`, `cms_is_admin()`, `cms_assets_vite()`) provide clean API without façade syntax
- Constants defined in [Constants.php](src/Constants/Constants.php): `INIT_BLOCK_FORM`, `MESSAGE_ERROR/SUCCESS/WARNING`, `PER_PAGE`

### Frontend TypeScript
- **tsup Config**: Bundles `src/app.ts` → targets Bootstrap 4 + jQuery 3 + Popper + FontAwesome
- **No Testing Framework**: Test script not configured; changes require manual verification
- **Locale**: Russian defaults (`ru_RU.UTF-8`, `Europe/Moscow`)

## Key Integration Points

1. **Laravel Service Provider** ([SdkServiceProvider.php](src/Providers/SdkServiceProvider.php)): Registers `cms.sdk` service with `Cms` singleton
2. **OpenSSL Encryption** ([OpenSslEncrypter.php](src/Libraries/OpenSslEncrypter.php), [OpenSslEncryptionServiceProvider.php](src/Providers/OpenSslEncryptionServiceProvider.php)): Custom encryption provider for Laravel 4
3. **Route Event Dispatch**: All module routes must use event listeners, not direct `Route::` calls
4. **Bootstrap Assets**: Frontend expects jQuery + Bootstrap 4 in global scope

## Common Patterns to Avoid

- **Don't register routes directly** in module `routes.php` via `Route::get()`. Use `Cms::addRoutes(callable)` with events.
- **Don't hardcode asset paths**: Use `Cms::assetsVite()` helper for conditional dev/prod asset injection.
- **Don't assume test coverage**: No test framework configured; verify behavior manually.

## Important Files
- Core module system: [Cms.php](src/Cms.php), [Module.php](src/Common/Module.php)
- Utilities & constants: [helpers.php](src/Utils/helpers.php) (802 lines), [Constants.php](src/Constants/Constants.php)
- Events/routing: [EventHandler.php](src/Events/EventHandler.php), [RouterEventHandler.php](src/Events/RouterEventHandler.php)
- Frontend: [app.ts](../@frontend/src/app.ts), [tsup.config.ts](../@frontend/tsup.config.ts), [vite.config.mts](../@frontend/vite.config.mts)
