<?php

class Jp7_Model_TipoAbstract extends InterAdminTipo {
	/**
	 * $id_tipo n�o � inteiro
	 * @return 
	 */
	public function __construct() {
		$this->db_prefix = $GLOBALS['db_prefix'];
	}
	
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
		if (is_string($fields)) {
			return $this->attributes[$fields]; 
		} elseif (is_array($fields)) {
			return (object) array_intersect_key($this->attributes, array_flip($fields));
		}
	}
}