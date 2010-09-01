<?php

/**
 * � usado para simular um m�todo no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodGetAll extends Jp7_InterAdmin_Soap_ReflectionMethodGet {
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'getAll' . $this->_getClassName();
	}
	
	public function getDescription() {
		return utf8_encode('Retorna todos os registros da se��o ' . $this->secao->nome . ', incluindo os registros deletados e os n�o publicados.');
	}
}