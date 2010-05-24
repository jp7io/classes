<?php

/**
 * � usado para simular um m�todo no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodGetFirst extends Jp7_InterAdmin_Soap_ReflectionMethodGet {
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'getFirst' . $this->secao->class;
	}
	
	/**
	 * @return string 
	 */
	public function getReturnType() {
		return $this->secao->class;
	}
	
	public function getDescription() {
		return utf8_encode('Retorna o primeiro registro da se��o ' . $this->secao->nome);
	}
}