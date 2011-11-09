<?php

class Jp7_Cache_Data {
	
	protected $id;
	protected $options;
	protected static $_cachedir = './cache/';
	protected static $_logdir = './interadmin/';
	
	/**
	 * 
	 * @param string 	$id
	 * @param int 	$lifetime 	Lifetime in seconds. Cached files with $lifetime == 0 expire using getLogTime().
	 * @return 
	 */
	public function __construct($id, $options = array()) {
		$this->id = $id; 
		$this->options = $options + array(
			'lifetime' => 0		
		);
	}
	
	public function load() {
		if (is_file($this->getFilename())) {
			return unserialize(file_get_contents($this->getFilename()));
		}
	}
	
	public function save($data) {
		file_put_contents($this->getFilename(), serialize($data));
	}
	
	public function getFilename() {
		return self::$_cachedir . '_' . $this->id . '.cache';
	}
	
	/**
	 * Verifica se o log do InterAdmin foi alterado. E limpa o cache se necess�rio.
	 * 
	 * @return bool 
	 */
	protected function _checkLog()
	{
		// Verifica��o do log
	 	$logTime = @filemtime(self::$_logdir . 'interadmin.log');
		$lastLogTime = @filemtime($this->getFilename());
		
	 	// Grava �ltimo log, se necess�rio
	 	if ($logTime != $lastLogTime && $logTime < time()) {
	 		return false; // Est� inv�lido
	 	}
	 	
	 	return true; // Est� v�lido
	}
}