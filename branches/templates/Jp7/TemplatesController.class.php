<?php
// Necess�rio para herdar m�todos padr�o
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_TemplatesController extends __Controller_Action {
	
	public function newsAction() {
		$newsTipo = self::getTipo();
		
		if ($id = $this->_getParam('id')) {
			$this->record = $newsTipo->getInterAdminById($id,array(
				'fields' => array('*', 'date_publish')
			));
			$this->view->record = $this->record;
		} else {
			$news = $newsTipo->getInterAdmins(array(
				'fields' => array('titulo', 'date_publish')
			));
				
			$this->view->news = $news;
		}
	}
}