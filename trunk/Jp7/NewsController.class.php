<?php
// Necess�rio para herdar m�todos padr�o
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_NewsController extends __Controller_Action {
	
	public function indexAction() {
		$id = $this->_getParam('id');
		// Ir� cachear uma p�gina diferente para cada registro
		Jp7_Cache_Output::getInstance()->start((string) $id);
		
		$newsTipo = self::getTipo();
		
		if ($id) {
			$this->record = $newsTipo->getInterAdminById($id,array(
				'fields' => array('*', 'date_publish')
			));
			if (!$this->record) {
				$this->_redirect($newsTipo->getUrl());
			}
			$this->record->subitens = $this->record->getSubitens(array(
				'fields' => array('*')
			));
			/*
			$this->record->files = $this->record->getArquivosParaDownload(array(
				'fields' => array('name', 'file')
			));
			*/
		} else {
			$pagination = new Pagination(array(
				'records' => $newsTipo->getInterAdminsCount(),
				'next_char' => 'Pr�xima',
				'back_char' => 'Anterior',
				'show_first_and_last' => true 
			));
			
			$this->view->introductionItens = array();
			if ($pagination->page == 1) {
				// Introdu��o na primeira p�gina
				if ($introductionTipo = $newsTipo->getFirstChildByModel('Introduction')) {
					$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
						'fields' => '*'
					));
				}
			}
			
			$this->view->news = $newsTipo->getInterAdmins(array(
				'fields' => array('*', 'date_publish'),
				'limit'=> $pagination
			));
			$this->view->pagination = $pagination;
		}
	}
}