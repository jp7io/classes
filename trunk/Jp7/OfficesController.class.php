<?php
// Necess�rio para herdar m�todos padr�o
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_OfficesController extends __Controller_Action {
	
	public function indexAction() {
		$id = $this->_getParam('id');
		// Ir� cachear uma p�gina diferente para cada registro
		Jp7_Cache_Output::getInstance()->start((string) $id);
		
		$officesTipo = self::getTipo();
		
		$this->view->headScript()->appendFile('http://maps.google.com/maps/api/js?sensor=true');
		$this->view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
		
		if ($id) {
			$record = $officesTipo->findById($id, array(
				'fields' => array('*', 'state' => array('sigla'))
			));
			if (!$record) {
				$this->_redirect($officesTipo->getUrl());
			}
			self::setRecord($record);
		} else {
			// Introdu��o
			if ($introductionTipo = $officesTipo->getFirstChildByModel('Introduction')) {
				$this->view->introductionItens = $introductionTipo->find(array(
					'fields' => '*'
				));
			}
			
			$this->view->records = $officesTipo->find(array(
				'fields' => array('*', 'state' => array('sigla'))
			));
		}
	}
}