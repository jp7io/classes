<?php
// Necess�rio para herdar m�todos padr�o
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_FilesController extends __Controller_Action {
	
	public function indexAction() {
		$filesTipo = self::getTipo();
		// Introdu��o
		if ($introductionTipo = $filesTipo->getFirstChildByModel('Introduction')) {
			$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
				'fields' => '*'
			));
		}
	}
}