<?php

class Jp7_Controller_Dispatcher extends Zend_Controller_Dispatcher_Standard {
	
	protected static $default_parent_class = 'Jp7_Controller_Action';
	
    /**
     * Dispatch to a controller/action
     *
     * By default, if a controller is not dispatchable, dispatch() will throw
     * an exception. If you wish to use the default controller instead, set the
     * param 'useDefaultControllerAlways' via {@link setParam()}.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @return void
     * @throws Zend_Controller_Dispatcher_Exception
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
    	if (!$this->isDispatchable($request)) {
            $controller = $request->getControllerName();
            if (!$this->getParam('useDefaultControllerAlways') && !empty($controller)) {
            	// Abrir template dentro do mesmo cliente
				$className = $this->_getControllerClassWithModelPrefix($request);
				eval('class ' . $className . ' extends ' . self::getDefaultParentClass() . ' {};');
            }
        }
		return parent::dispatch($request, $response);
    }
	
	/**
     * Returns TRUE if the Zend_Controller_Request_Abstract object can be
     * dispatched to a controller.
     *
     * Use this method wisely. By default, the dispatcher will fall back to the
     * default controller (either in the module specified or the global default)
     * if a given controller does not exist. This method returning false does
     * not necessarily indicate the dispatcher will not still dispatch the call.
     *
     * @param Zend_Controller_Request_Abstract $action
     * @return boolean
     */
    public function isDispatchable(Zend_Controller_Request_Abstract $request)
    {
        $retornoOriginal = parent::isDispatchable($request);
		// Necess�rio porque ZF n�o verifica se uma classe com o prefixo do m�dulo existe
		if (!$retornoOriginal) {			
			$className = $this->_getControllerClassWithModelPrefix($request);
	        if (class_exists($className, false)) {
	            return true;
	        }
		}
        return $retornoOriginal;
    }
	
	/**
	 * Returns the name of the controller class prefixed with the model prefix.
	 * 
	 * @param Zend_Controller_Request_Abstract $request
	 * @return string
	 */
	protected function _getControllerClassWithModelPrefix($request) {
		$className = $this->getControllerClass($request);
		if (($this->_defaultModule != $this->_curModule) || $this->getParam('prefixDefaultModule')) {
            $className = $this->formatClassName($this->_curModule, $className);
        }
		return $className;
	}
	
	/**
	 * Evals a file as a child class of the current default parent class.
	 * Ok, I know it's evil, but it's needed.
	 * 
	 * @param string $filename
	 * @return void
	 */
	public static function evalAsAController($filename) {
		if (strpos($filename, 'eval') === false) {
			$class_contents = file_get_contents($filename);
			$class_contents = str_replace('__Controller_Action', self::getDefaultParentClass(), $class_contents);
			eval('?>' . $class_contents);
		}
	}
    
    /**
     * Returns $default_parent_class.
     *
     * @see Jp7_Controller_Dispatcher::$default_parent_class
     */
    public static function getDefaultParentClass() {
        return self::$default_parent_class;
    }
    
    /**
     * Sets $default_parent_class.
     *
     * @param object $default_parent_class
     * @see Jp7_Controller_Dispatcher::$default_parent_class
     */
    public static function setDefaultParentClass($default_parent_class) {
        self::$default_parent_class = $default_parent_class;
    }
}