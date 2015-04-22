<?php

use \Illuminate\Support\Str;

/**
 * Configurations for a site.
 *
 * @version (2008/07/30)
 * @package InterSite
 */
class InterSite {
	const PRODUCAO = 'Produção';
	const QA = 'QA';
	const DESENVOLVIMENTO = 'Desenvolvimento';
	
	const PRODUCTION = 'Produção';
	const DEVELOPMENT = 'Desenvolvimento';
	
	const HOST_MAIN = 'main';
	const HOST_ALIAS = 'alias';
	const HOST_REMOTE = 'remote';
	
	/**
	 * Array of servers for this site.
	 * @var array
	 */
	public $servers = array();
	/**
	 * Array of languages for this site.
	 * @var array
	 */
	public $langs = array();
	/**
	 * Current server.
	 * @var object
	 */
	public $server;
	/**
	 * Current server type: 'main', 'alias' or 'remote'.
	 * @var string
	 */
	public $hostType;
	/**
	 * Current Database.
	 * @var object
	 */
	public $db;
	/**
	 * Current Url.
	 * @var object
	 */
	public $url;
	/**
	 * Default language.
	 * @var string
	 */
	public $lang_default = 'pt-br';
	
	protected static $instance = null;
	
	/**
	 * Checks if it´s at a localhost or at the IPS 127.0.0.1 or 192.168.0.*. 
	 * If the HTTP_HOST has a . (dot) like something.com, it will return false.
	 *
	 * @return bool
	 */
	public static function isAtLocalhost() {
		$host = explode(':', self::getHost())[0];
		if ($host == 'localhost') {
			return true;
		} elseif ($_SERVER['SERVER_ADDR'] == '127.0.0.1' || strpos($_SERVER['SERVER_ADDR'], '192.168.0.') === 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * @deprecated
	 * @return bool
	 */
	public function isProducao() {
		return $this->server->type === self::PRODUCAO;
	}
	/**
	 * Checks if the server type is PRODUCAO.
	 * @return bool
	 */
	public function isProduction() {
		return $this->server->type === self::PRODUCTION;
	}
	/**
	 * Checks if the server type is QA.
	 * @return bool
	 */
	public function isQa() {
		return $this->server->type === self::QA;
	}
	/**
	 * @deprecated
	 * @return bool
	 */
	public function isDesenvolvimento() {
		return $this->server->type === self::DESENVOLVIMENTO;
	}
	/**
	 * Checks if the server type is PRODUCAO.
	 * @return bool
	 */
	public function isDevelopment() {
		return $this->server->type === self::DEVELOPMENT;
	}
	
	/**
	 * Returns the first server which has a given type.
	 * 
	 * @param string $type Type of the server, such as InterSite::PRODUCAO, InterSite::QA or InterSite::DESENVOLVIMENTO.
	 * @return InterAdmin
	 */
	public function getFirstServerByType($type) {
		foreach ($this->servers as $server) {
			if ($server->type == $type) {
				return $server;
			}
		}
	}
	
	public static function config() {
		return self::$instance;
	}
	
	public static function setConfig(InterSite $instance) {
		self::$instance = $instance;
	}
	
	
	/**
	 * Initializes the variables for the given host.
	 * 
	 * @param string $host
	 * @return void
	 */
	public function init($host) {
		global $jp7_app;
		
		// No caso do artisan, o $host estará vazio, mas entra em self::isAtLocalhost()
		$host = explode(':', $host)[0];		

		// Browsers não fazem isso, mas alguns User Agents estranhos podem vir em maiúscula
		$host = strtolower($host);
		
		// This server is a main host
		if (isset($this->servers[$host])) {
			$this->server = $this->servers[$host];
		}
		$this->hostType = self::HOST_MAIN;
		
		// Not Found, searching aliases
		while (!$this->server) {
			foreach ($this->servers as $serverHost => $server) {
				// InterAdmin Remote
				if ($jp7_app) {
					$remotes = $server->interadmin_remote;
					if (in_array($host, $remotes) || in_array('www.' . $host, $remotes)) {
						if ($server->vars['check_dns'] && !self::hasDnsRecord($server->host) && $server->alias_domains) {
							$server->host = $server->alias_domains[0];
						}
						$this->server = $this->servers[$host] = $server;
						$this->interadmin_remote = $host;
						$this->hostType = self::HOST_REMOTE;
						break 2;  // Exit foreach and while.
					}
				}
				// Domínios Alternativos - Não redirecionam
				if (is_array($server->alias_domains) && in_array($host, $server->alias_domains)) {
					$this->server = $this->servers[$host] = $server;
					$this->server->host = $host;
					break 2;  // Exit foreach and while.
				}
				// Aliases - Redirecionam
				if (in_array($host, $server->aliases)) {
					$this->server = $this->servers[$host] = $server;
					$this->hostType = self::HOST_ALIAS;
					break 2;  // Exit foreach and while.
				}
			}
			// Dev Local
			if (self::isAtLocalhost()) {
				if ($this->servers['localhost']) {
					$this->server = $this->servers['localhost'];
					$this->servers[$host] = $this->server;
					$this->server->host = $host;
				}
			}
			break;
		}
		
		if ($this->server) {
			$this->db = clone $this->server->db;
			// Exceção para funcionamento do InterAdmin Remote nos sites Express
			/*
			if ($this->db->host == 'mysql.jp7.com.br' && $this->hostType == self::HOST_REMOTE) {
				$this->db->host = 'localhost';
			}
			*/
			if ($this->db->host_internal && $this->hostType != self::HOST_REMOTE) {
				$this->db->host = $this->db->host_internal;
			}

			$this->db->prefix = 'interadmin_' . $this->name_id;

			foreach ((array) $this->server->vars as $var => $value) {
				$this->$var = $value;
			}
			
			$this->url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $this->server->host . '/' . Str::finish($this->server->path, '/');
			
			foreach ($this->langs as $sigla => $lang) {
				if ($lang->default) {
					$this->lang_default = $sigla;
					break;
				}
			}
		}
	}
	
	public function start() {
		$host = self::getHost(); 
		$this->init($host);
		
		switch ($this->hostType) {
			case self::HOST_ALIAS:
				header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
				header('Location: http://' . $this->server->host . $_SERVER['REQUEST_URI']);
				exit;
			case !$this->server: {
				throw new Exception('No settings for host: ' . $host);
			}
		}
		
		self::setConfig($this);
	}
	
	public static function getHost() {
		if (!isset($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
		} else {
			global $app;
			return file_get_contents($app['path.base'] . '/interadmin/host');
		}
	}
	
	public static function __set_state($array) {
		$instance = new self;
		foreach ($array as $key => $value) {
			$instance->$key = $value;
		}
		return $instance;
	}
	
	public function export() {
		$code = var_export($this, true);
		
		$code = preg_replace("/' => \n\s+/", "' => ", $code);
		$code = str_replace('stdClass::__set_state', '(object)', $code);
		
		return $code;
	}
	
	/* @todo TEMP - Creating old globals */
	/*
	$oldtypes = array(
		self::PRODUCAO => 'Principal',
		self::QA => 'QA',
		self::DESENVOLVIMENTO => 'Local'
	);
	$GLOBALS['c_url'] = $this->url;
	$GLOBALS['c_server_type'] = $oldtypes[$this->server->type];
	$GLOBALS['c_site'] = $this->name_id;
	$GLOBALS['c_menu'] = $this->menu;
	$GLOBALS['c_cache'] = $this->cache;
	$GLOBALS['c_cache_delay'] = $this->cache_delay;
	$GLOBALS['db_prefix'] = 'interadmin_' . $this->name_id;
	$GLOBALS['c_cliente_url_path'] = $GLOBALS['c_path'] = jp7_path($this->server->path);
	$GLOBALS['c_analytics'] = $this->google_analytics;
	$GLOBALS['googlemaps_key'] = $this->google_maps;
	$GLOBALS['c_w3c'] = true;
	$GLOBALS['c_doc_root'] = jp7_doc_root();
	// DB
	$GLOBALS['db_type'] = $this->db->type;
	$GLOBALS['db_host'] = $this->db->host;
	$GLOBALS['db_name'] = $this->db->name;
	$GLOBALS['db_user'] = $this->db->user;
	$GLOBALS['db_pass'] = $this->db->pass;
	// FTP
	$GLOBALS['ftp']['host'] = $this->server->ftp;
	$GLOBALS['ftp']['user'] = $this->server->user;
	$GLOBALS['ftp']['pass'] = $this->server->pass;
	// InterAdmin
	$GLOBALS['c_publish'] = $this->interadmin_preview;
	$GLOBALS['c_remote'] = $this->interadmin_remote;
	$GLOBALS['c_cliente_title'] = $this->name;
	$GLOBALS['c_nobackup'] = $this->nobackup;
	foreach ($this->servers as $host => $server) {
		$GLOBALS['c_cliente_domains'][] = $host;
		$GLOBALS['c_cliente_domains'] = array_merge($GLOBALS['c_cliente_domains'], (array) $server->aliases);
	}
	foreach($this->langs as $sigla => $lang) {
		$GLOBALS['c_lang'][] = array($sigla, $lang->name, (bool) $lang->multibyte);
	}
	$GLOBALS['c_lang_default'] = $this->lang_default;
	*/
	/* TEMP - Creating old globals */

		
	/**
	 * Cacheando verificação, porque chega a demorar 1 segundo
	 */
	public static function hasDnsRecord($domain) {
		$cacheFile = sys_get_temp_dir() . '__dns_' . $domain;
		if (is_file($cacheFile) && filemtime($cacheFile) > strtotime('-2 minute')) {
			return file_get_contents($cacheFile);
		} else {
			$dns = dns_get_record($domain);
			@file_put_contents($cacheFile, (bool) $dns);
		}
	}
}
