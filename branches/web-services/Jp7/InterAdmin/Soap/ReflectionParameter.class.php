<?php

/**
 * � usado para simular um par�metro de cada m�todo no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionParameter {

	/**
	 * @return string 
	 */
	public function getName() {
		return 'query';
	}
	
	public function getType() {
		return 'string';
	}
	
	public function isOptional() {
		return true;
	}
}