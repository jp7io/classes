<?php

namespace Jp7\Laravel;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Jp7\MethodLogger;

class Router extends \Illuminate\Routing\Router {
	
	protected $mapIdTipos = [];
	protected $logger;

	public function __construct(Dispatcher $events, Container $container = null) {
		$this->logger = new MethodLogger($this);
		return parent::__construct($events, $container);
	}

	public function createDynamicRoutes($section, $currentPath = [], $firstCall = true) {
		if ($firstCall && \Cache::has('Interadmin.routes')) {
			$this->logger->_replay(\Cache::get('Interadmin.routes'));
			return;
		}
		if ($subsections = $section->getChildrenMenu()) {
			if ($section->isRoot()) {
				foreach ($subsections as $subsection) {
					$this->createDynamicRoutes($subsection, $currentPath, false);
				}
			} else {
				$this->group(['namespace' => $section->getStudly(), 'prefix' => $section->getSlug()], function() use ($section, $subsections, $currentPath) {
					//$currentPath[] = $section->getSlug();
					foreach ($subsections as $subsection) {
						$this->createDynamicRoutes($subsection, $currentPath, false);
					}
				});
			}
		}
		if (!$section->isRoot()) {
			// Somente para debug na barra do laravel
			$this->logger->resource($section->getSlug(), $section->getControllerBasename(), [
				'only' => ['index', 'show'],
				'id_tipo' => $section->id_tipo,
				'dynamic' => $this->_checkTemplate($section) ? '|dynamic' : ''						
			]);
		}
		if ($firstCall) {
			// save cache after running all routes
			\Cache::put('Interadmin.routes', $this->logger->_getLog(), 60);
		}
	}
	
	public function resource($name, $controller, array $options = array()) {
		if (isset($options['id_tipo'])) {
			// Saving routes for each id_tipo
			$groupRoute = str_replace('/', '.', $this->getLastGroupPrefix());
			if (!array_key_exists($options['id_tipo'], $this->mapIdTipos)) {
				$this->mapIdTipos[$options['id_tipo']] = ($groupRoute ? $groupRoute . '.' : '') . $name;
			}
			$dynamic = isset($options['dynamic']) ? $options['dynamic'] : '';
			$before = 'setTipo:' . $options['id_tipo'] . $dynamic;
			
			$this->group(['before' => $before], function() use ($name, $controller, $options) {
				parent::resource($name, $controller, $options);
			});
		} else {
			parent::resource($name, $controller, $options);
		}
	}
	
	public function tipo($name, $id_tipo) {
		$controller = studly_case(str_replace('.', '\\ ', $name )) . 'Controller';
		$this->resource($name,  $controller, [
			'id_tipo' => $id_tipo,
			'only' => ['index', 'show']
		]);
	}
	
	public function getRouteByIdTipo($id_tipo, $action = 'index') {
		$mappedRoute = $this->mapIdTipos[$id_tipo];
		return $this->routes->getByName($mappedRoute . '.' . $action);
	}
	
	public function getIdTipoByRoute($route) {
		return array_search($route, $this->mapIdTipos);
	}

	public function getMapIdTipos() {
		return $this->mapIdTipos;
	}
	
	public function getVariablesFromRoute($route) {
		$matches = array();
		preg_match_all('/{(\w+)}/', $route->getUri(), $matches);
		return $matches[1] ?: array();
	}
	
	protected function _checkTemplate($section) {
		$dynamic = false;
		if (!class_exists($section->getControllerName())) {
			$dynamic = true;
			
			$templateController = '';
			if ($section->template) {
				$templateController = $this->_pathToNamespace($section->template) . 'Controller';
			}
			
			$namespace = $section->getNamespace();
			$namespaceCode = $namespace ? 'namespace ' . $namespace . ';' : '';
			
			if ($templateController && class_exists($templateController)) {
				eval($namespaceCode . "class {$section->getControllerBasename()} extends \\$templateController { }");
			} else {
				eval($namespaceCode . "class {$section->getControllerBasename()} extends \\BaseController { public function index() { }}");
			}
		}
		return $dynamic;
	}
	
	protected function _pathToNamespace($string) {
		if (starts_with($string, '/')) {
			// lasa está começando com /templates - Corrigir assim que possivel 
			$string = substr($string, 1);
		}
		
		$parts = explode('/', $string);
		$parts = array_map('studly_case', $parts);
		return implode('\\', $parts) . 'Controller';
	}
	
}