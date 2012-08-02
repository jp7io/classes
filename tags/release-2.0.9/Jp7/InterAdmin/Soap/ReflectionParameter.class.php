<?php

/**
 * � usado para simular um par�metro de cada m�todo no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionParameter {
	
	protected $name;
	protected $type;
	
	function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
	}
	
	/**
	 * @return string 
	 */
	public function getName() {
		return $this->name;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function isOptional() {
		return true;
	}
}