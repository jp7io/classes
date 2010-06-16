<?php

/**
 * TODO
 * 
 * @category Jp7
 * @package Jp7_Locale
 */
class Jp7_Locale
{
	/**
	 * Identificador do idioma, exemplo: 'en', 'pt-br', 'de', etc.
	 * @var string 
	 */
	public $lang = '';
	/**
	 * String que � adicionada ao prefixo das tabelas no banco de dados. Fica vazio na l�ngua padr�o.
	 * @var string  
	 */
	public $prefix = '';
	/**
	 * Path a ser adicionado � URL do site para se abrir com esse idioma. Fica vazio na l�ngua padr�o.
	 * @var string  
	 */
	public $path = '';
	
	public function __toString() {
		return $this->lang;
	}
	
	/**
	 * Construtor p�blico.
	 * 
	 * @param string $language 	pt-br, en, etc...
	 * @return 
	 */
	public function __construct($language)
	{
		$config = Zend_Registry::get('config');
		
		$this->lang = $language;
		
		if ($language != $config->lang_default) {
			$this->path = $language . '/';
			$this->prefix = '_' . $language;
		}
	}
}