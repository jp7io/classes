<?php

/**
 * Adiciona configura��es comuns da JP7 e __call de m�todos inexistentes para 
 * templates ao Controller da Zend.
 * 
 * @category Jp7
 * @package Jp7_Controller
 */
class Jp7_Controller_Action extends Zend_Controller_Action
{
	/**
	 * @var InterAdminTipo
	 */
	protected static $tipo;
	
	public function init() {
		if (!Zend_Registry::isRegistered('originalRequest')) {
			Zend_Registry::set('originalRequest', clone $this->getRequest());
		}
	}
	
    public function preDispatch() {
    	if (!$this->actionExists()) {
			$this->forwardToTemplate();
		}
	}
	public function postDispatch() {
		/**
		 * @var InterSite $config Configura��o geral do site, gerada pelo InterSite
		 */
		$config = Zend_Registry::get('config');
		/**
		 * @var Jp7_Locale $lang Idioma sendo utilizada no site
		 */
		$lang = Zend_Registry::get('lang');
		/**
		 * @var array $metas Metatags no formato $nome => $valor
		 */
		$metas = Zend_Registry::get('metas');
		/**
		 * @var array $scripts Arquivos de Javascript
		 */
		$scripts = Zend_Registry::get('scripts');
		/**
		 * @var array $links Arquivos de CSS
		 */
    	$links = Zend_Registry::get('links');
		
		// View
		$this->view->config = $config;
		$this->view->lang = $lang;
		$this->view->tipo = self::getTipo();
		
		// Layout
		// - Title
		$this->view->headTitle($config->lang->title);
		// - Metas
		$this->view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=ISO-8859-1');
		foreach ($metas as $key => $value) {
			$this->view->headMeta()->appendName($key, $value);
		}
		// - Javascripts
		foreach ($scripts as $file) {
			$this->view->headScript()->appendFile($file);
		}
		// - CSS
		foreach ($links as $file) {
			$this->view->headLink()->appendStylesheet($file);
		}
	}
	/**
	 * Trata as actions que n�o tem a fun��o definida e passa para o template
	 * se existir.
	 * 
	 * @param string $method
	 * @param array $args
	 * @return void
	 */
	public function __call($method, $args)
	{
		if ($this->forwardToTemplate()) {
			return;	
		} else {
			return parent::__call($method, $args);
		}
	}
	/** 
	 * Forwards the request to the template of this InterAdminTipo.
	 * 
	 * @return bool TRUE if it has a template, FALSE otherwise.
	 */
	public function forwardToTemplate() {
		if ($tipo = self::getTipo()) {
			if ($template = $tipo->getModel()->template) {
				list($controller, $action) = explode('/', $template);
				$this->_forward($action, $controller);
				return true;
			}
		}
		return false;
	}
	/**
	 * Returns the InterAdminTipo pointed by the current Controller and Action.
	 * 
	 * @return InterAdminTipo
	 */
	public static function getTipo() {
		if (!isset(self::$tipo)) {
			$config = Zend_Registry::get('config');
			
			$customTipo = ucfirst($config->name_id) . '_InterAdminTipo';
			if (class_exists($customTipo)) {
				$rootTipo = new $customTipo();
			} else {
				$rootTipo = new InterAdminTipo();
			}
			
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$options = array(
				'fields' => array('template')
			);
			
			$controllerName = $request->getControllerName();
			if ($controllerName == 'index') {
				$controllerName = 'home';
			}
			$options['where'] = array("id_tipo_string = '" . toId($controllerName) . "'");
			$controllerTipo = $rootTipo->getFirstChild($options);
			
			if ($controllerTipo) {
				if ($request->getActionName() == 'index') {
					self::$tipo = $controllerTipo;
				} else {
					$options['where'] = array("id_tipo_string = '" . toId($request->getActionName()) . "'");
					self::$tipo = $controllerTipo->getFirstChild($options);
				}
			}
		}
		return self::$tipo;
	}
	/**
	 * Sets the InterAdminTipo for this controller.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public static function setTipo(InterAdminTipo $tipo) {
		self::$tipo = $tipo;
	}
	/**
	 * Checks if the request Action exists.
	 * @return bool
	 */	
	public function actionExists() {
		$request = $this->getRequest();
		$actionName = toId($request->getActionName());
		// Case insensitive
		return method_exists($this,  $actionName . $request->getActionKey());
	}
}
