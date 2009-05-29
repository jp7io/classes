<?php

/**
 * Extende o Zend_Cache_Frontend_Output com Zend_Cache_Backend_File.
 * 
 * @category Jp7
 * @package Jp7_Cache
 */
 
 class Jp7_Cache_Output extends Zend_Cache_Frontend_Output
 {
	 protected static $_instance = null;
	 protected static $_started = false;
	 protected static $_enabled = false;
	 protected static $_cachedir = './cache/';
	 protected static $_logdir = './interadmin/';
	 
	 /**
	  * Retorna uma inst�ncia configurada do Jp7_Cache_Page.
	  * 
	  * @todo Ver como ir� funcionar com o Preview do InterAdmin 
	  * @param array $frontOptions
	  * @param array $backOptions
	  * @return Jp7_Cache_Output
	  */
	public static function getInstance(array $frontOptions = array(), array $backOptions = array())
	{
		if (!self::$_instance) {
			global $debugger;
			$config = Zend_Registry::get('config');
			
			if ($config->cache && !$debugger->debugFilename && !$debugger->debugSql) {
				self::$_enabled = true;
			}
			
			$frontDefault = array(
				'lifetime' => 86400 // 1 dia
			);
			$backDefault = array(
				'cache_dir' => self::$_cachedir,
				'file_name_prefix' => 'zf'
			);

			self::$_cachedir = $backDefault['cache_dir'];
			
			$frontend = new Jp7_Cache_Output($frontOptions + $frontDefault);

			if (is_dir(self::$_cachedir)) {
				$backend = new Zend_Cache_Backend_File($backOptions + $backDefault);
			} else {
				$backend = new Zend_Cache_Backend_Test();
				self::$_enabled = false;
			}

			$frontend->setBackend($backend);		
						 
			self::$_instance = $frontend;
		}
		return self::$_instance;
	 }
	 
	 /**
	  * Inicia o cache.
	  * 
	  * @param mixed $_ Valores que se alteram na p�gina e que portanto geram outra vers�o de cache.
	  * @see Zend/Cache/Frontend/Zend_Cache_Frontend_Page#start()
	  */	 
	 public function start()
	 {
	 	global $c_jp7;

	 	if (!self::$_enabled) {
	 		return false;
	 	}

	 	// Gera o id do cache
	 	$id = $this->_makeId(func_get_args());

	 	// Verifica se o log foi alterado
	 	$this->_checkLog();

	 	$retorno = parent::start($id);

	 	if ($retorno) {
	 		if ($c_jp7) {
	 			echo '<div style="position:absolute;left:0px;top:0px;padding:5px;background:#FFCC00;filter:alpha(opacity=50);opacity: .5;z-index:1000">CACHE</div>';
	 		} 
	 		exit;
	 	}

	 	self::$_started = true;

	 	return $retorno;
	 }

	 /**
	  * Retorna true se o cache tiver iniciado.
	  * 
	  * @return bool
	  */
	 public static function hasStarted()
	 {
	 	return self::$_started;
	 }
	 
	 
	 /**
	  * Cria um ID na forma: controller_action_lang_module_SUFIXO
	  * 
	  * @param mixed $data Gera um hash e adiciona como sufixo ao ID.
	  * @return string ID gerado.
	  */
	 protected function _makeId($data)
	 {
	 	$params = Zend_Controller_Front::getInstance()->getRequest()->getParams();

	 	$id = toId(implode('_', array(
	 		$params['controller'],
	 		$params['action'], 
	 		$params['lang'], 
	 		$params['module'])
	 	));

	 	if ($data) {
			if (count($data) == 1 && is_string($data[0])) {
		 		$id .= '_' . toId($data[0]);
		 	} else {
		 		$id .= '_' . md5(serialize($data));
		 	}
	 	}

	 	return $id;
	}

	/**
	 * Verifica se o log do InterAdmin foi alterado. E limpa o cache se necess�rio.
	 * 
	 * @return void
	 */
	protected function _checkLog()
	{
		$lastLogFilename = 'logcheck.log';
		$lastLogTime = intval(file_get_contents(self::$_cachedir . $lastLogFilename));

		// Verifica��o do log
	 	$logTime = filemtime(self::$_logdir . 'interadmin.log');
		
	 	// Grava �ltimo log, se necess�rio
	 	if ($logTime != $lastLogTime) {
	 		$this->clean();
	 		file_put_contents(self::$_cachedir . $lastLogFilename, $logTime);
	 	}
	}
}