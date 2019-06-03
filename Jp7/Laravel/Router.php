<?php

namespace Jp7\Laravel;

use Jp7\MethodForwarder;
use Jp7\Interadmin\RecordClassMap;
use Jp7\Interadmin\Type;
use LaravelLocalization;
use Route;
use Closure;
use App;

/**
 * Maps Interadmin sections to Laravel routes
 */
class Router extends MethodForwarder
{
    /**
     * @var array [id_tipo => route basename]
     */
    protected $map = [];
    protected $cachefile = 'bootstrap/cache/routemap.cache';
    protected $locale;

////
//// Cache functions: Type map will work even when Laravel routes are cached
////

    public function __construct($target)
    {
        $this->cachefile = base_path($this->cachefile);

        if (is_file($this->cachefile)) {
            $this->loadCache();
        }

        parent::__construct($target);
    }

    public function clearCache()
    {
        $this->map = [];
        $this->saveCache();
    }

    public function saveCache()
    {
        file_put_contents($this->cachefile, serialize($this->map));
    }

    public function loadCache()
    {
        $this->map = unserialize(file_get_contents($this->cachefile));
    }

////
//// Map functions: Read/write to the type map
////

    private function hasType($id_tipo)
    {
        $map = &$this->map[$this->getLocale()];
        $map = $map ?: [];
        return array_key_exists($id_tipo, $map);
    }

    private function addType($id_tipo, $controllerName)
    {
        $map = &$this->map[$this->getLocale()];
        $map = $map ?: [];
        // Saving routes for each id_tipo
        $lastRoute = array_last(Route::getRoutes()->getRoutes());

        if (!str_contains($lastRoute->getActionName(), $controllerName)) {
            throw new \UnexpectedValueException(
                'Check if your routes are duplicated.'.
                'Expected '.$lastRoute->getActionName().' to contain '.$controllerName
            );
        }

        $routeParts = explode('.', $lastRoute->getName());
        array_pop($routeParts);
        $map[$id_tipo] = implode('.', $routeParts);
    }

    /**
     * @param  int $id_tipo
     * @param  string $action
     * @return Route
     */
    public function getRouteByTypeId($id_tipo, $action = 'index')
    {
        $map = &$this->map[$this->getLocale()];
        $map = $map ?: [];
        if (!isset($map[$id_tipo])) {
            throw new RouteException('There is no route registered for id_tipo: ' . $id_tipo);
        }
        $mappedRoute = $map[$id_tipo];
        $routePrefix = ($mappedRoute && $mappedRoute != '/') ? $mappedRoute . '.' : '';

        return $this->target->getRoutes()->getByName($routePrefix . $action);
    }
    /**
     * @param  string $routeBasename
     *
     * @return Type
     */
    public function getTypeByRouteBasename($routeBasename)
    {
        $map = &$this->map[$this->getLocale()];
        $map = $map ?: [];
        $id_tipo = array_search($routeBasename, $map);
        if ($id_tipo) {
            return Type::getInstance($id_tipo);
        }
    }
    /**
     * @param  Route $route
     * @return Type
     */
    public function getTypeByRoute($route)
    {
        $basename = $this->getRouteBasename($route);
        return $this->getTypeByRouteBasename($basename);
    }

    /**
     * @return array [id_tipo => route basename]
     */
    public function getTypeMap()
    {
        return $this->map;
    }

    public function tempTypeRoutes(...$id_tipo_array)
    {
        $map = &$this->map[$this->getLocale()]; // reference
        foreach ($id_tipo_array as $id_tipo) {
            if (isset($map[$id_tipo])) {
                echo 'WARNING: Please check tempTypeRoutes for id_tipo: '.$id_tipo.PHP_EOL;
                continue;
            }
            // Create temporary controller
            $tempRouteName = 'temporarilyIgnored'.$id_tipo;
            eval('namespace App\Http\Controllers {
                class '.$tempRouteName.'Controller extends \Illuminate\Routing\Controller {
                }
            }');
            parent::resource($tempRouteName, $tempRouteName.'Controller');
            $map[$id_tipo] = $tempRouteName;
        }
    }

////
//// Route override: Adds default values for methods
////

    /**
     * Adds conventions for controllers and 'only' option:
     *     r::resource('places')
     * Will behave like:
     *     r::resource('places', 'PlacesController', ['only' => ['index', 'show']])
     *
     * @param  string $name         Resource name
     * @param  string $controller   Controller class
     * @param  array  $options      Array of options
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function resource($name, $controller = null, array $options = [])
    {
        if (!is_string($controller) && empty($options)) {
            // Called like resource($name, $options)
            $options = $controller;
            $controller = null;
        }
        if (is_null($controller)) {
            $controller = $this->getControllerClass($name);
        }
        if (empty($options['only'])) {
            $options['only'] = $this->getControllerActions($controller);
        }

        $pendingResourceRegistration = parent::resource($name, $controller, $options);
        if ($pendingResourceRegistration) {
            $pendingResourceRegistration->__destruct(); // Laravel 5.5+
            try {
                $reflection = new \ReflectionProperty($pendingResourceRegistration, 'registered');
                $reflection->setAccessible(true);
                $reflection->setValue($pendingResourceRegistration, false);
            } catch (\ReflectionException $e) {
                // do nothing, Laravel 5.5 does not have ->registered
            }
        }
        if (isset($options['id_tipo'])) {
            if (!is_numeric($options['id_tipo'])) {
                // Get id_tipo from class
                $options['id_tipo'] = RecordClassMap::getInstance()->getClassIdTipo($options['id_tipo']);
            }
            $this->addType($options['id_tipo'], $controller); // Maps [id_tipo => route basename]
        }
        return $pendingResourceRegistration;
    }

    /**
     * @param  string $name Resource name such as 'places'
     * @return string       Controller name such as 'PlacesController'
     */
    protected function getControllerClass($name)
    {
        if ($name === '/') {
            $controller = 'Index';
        } else {
            $parts = explode('.', $name);
            $parts = array_map('studly_case', $parts);
            $controller = implode('\\', $parts);
        }
        $controller .= 'Controller';
        return $controller;
    }

    protected function getControllerActions($classBasename)
    {
        $stack = $this->getGroupStack();
        $namespace = end($stack)['namespace'];
        $class = $namespace.'\\'.$classBasename;
        if (!class_exists($class)) {
            echo 'Controller not found: '.$class.PHP_EOL;
            // Create all controllers:
            if (env('CREATE_CONTROLLERS')) {
                \Artisan::call('make:controller', [
                    'name' => str_replace('App\Http\Controllers\\', '', $class),
                    '--resource' => true
                ]);
            }
            return [];
        }
        $validActions = ['index', 'show', 'create', 'store', 'update', 'destroy', 'edit'];
        $actions = array_intersect(get_class_methods($class), $validActions);
        if (!$actions) {
            echo 'Controller has no actions: '.$class.PHP_EOL;
        }
        return $actions;
    }

////
//// Localization: allows caching routes with localization
////

    protected function getLocale()
    {
        // route creation: $this->locale
        // route resolution: App::getLocale()
        return is_null($this->locale) ? App::getLocale() : $this->locale;
    }

    // Works with Laravel 5.2
    public function languages(Closure $callback)
    {
        foreach (LaravelLocalization::getSupportedLanguagesKeys() as $locale) {
            $this->locale = $locale; // Used as map key
            if ($locale === LaravelLocalization::getDefaultLocale()) {
                $prefix = '';
            } else {
                $prefix = $locale;
            }
            $this->group(['prefix' => $prefix, 'namespace' => null], $callback);
        }
        $this->locale = null;
    }

    // Works with Laravel 5.3
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function localizeRoute($routeName)
    {
        if ($this->locale === LaravelLocalization::getDefaultLocale()) {
            return $routeName;
        }
        return $this->locale.'.'.$routeName;
    }
////
//// Dynamic routes: Creates routes automatically from InterAdmin's sections
////

    /**
     * Creates routes automatically from InterAdmin's sections.
     * Only creates routes if Type has 'menu' checked.
     *
     * @param  Type     $section        Should use trait Jp7\Laravel\Routable
     * @param  array    $currentPath    Used for recursivity
     * @return void
     */
    public function createDynamicRoutes($section, $currentPath = [])
    {
        $isRoot = $section->isRoot();

        if ($subsections = $section->getChildrenMenu()) {
            $closure = function () use ($subsections, $currentPath) {
                foreach ($subsections as $subsection) {
                    $this->createDynamicRoutes($subsection, $currentPath, false);
                }
            };

            if ($isRoot) {
                $closure();
            } else {
                Route::group([
                    'namespace' => $section->getStudly(),
                    'prefix' => $section->getSlug()
                ], $closure);
            }
        }
        if (!$isRoot) {
            if (!$this->hasType($section->id_tipo)) {
                // won't enter here if there is already a route for this type
                $controllerClass = $section->getControllerBasename();
                Route::resource($section->getSlug(), $controllerClass, [
                    'only' => $this->getControllerActions($controllerClass)
                ]);
                $this->addType($section->id_tipo, $controllerClass);
            }
        }
    }

////
//// Helpers: Get extra information from Laravel routes
////

    /**
     * Returns a list of variable placeholders from routes.
     * Route: schools/{schools}/courses/{courses}
     * Variables: ['schools', 'courses']
     *
     * @param  Route $route
     * @return array
     */
    public function getVariablesFromRoute($route)
    {
        $matches = [];
        preg_match_all('/{(\w+)}/', $route->uri(), $matches);

        return $matches[1] ?: [];
    }

    /**
     * Parses route basename
     *
     * Route: 'services.contact.index'
     * Basename: 'services.contact'
     *
     * @param Route $route
     * @return string
     */
    public function getRouteBasename($route)
    {
        $parts = explode('.', $route->getName());
        array_pop($parts);
        return implode('.', $parts);
    }

    /**
     * Map URI to breadcrumb of objects
     * Allows custom resolution of {placeholder} to Objects.
     *
     * @param string $uri
     * @param Closure $resolveParameter     Closure will be called each time a {placeholder} is found
     */
    public function uriToBreadcrumb($uri, $resolveParameter)
    {
        $breadcrumb = [];
        $uri = trim($uri, '/');
        if ($uri == '') {
            return $breadcrumb;
        }
        $parameter = null;
        $type = null;

        $segments = explode('/', $uri);
        $routeParts = [];

        foreach ($segments as $segment) {
            if (starts_with($segment, '{')) {
                $parameter = $resolveParameter($type, $segment);
                $breadcrumb[] = $parameter;
            } else {
                $routeParts[] = $segment;
                $routeName = implode('.', $routeParts);

                $type = $this->getTypeByRouteBasename($routeName);
                if ($type && $parameter) {
                    $type->setParent($parameter);
                }
                $breadcrumb[] = $type;
            }
        }

        return $breadcrumb;
    }
}
