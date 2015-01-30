<?php

use Jp7\Interadmin\Collection;
use Jp7\Interadmin\ClassMap;
use Jp7\Interadmin\Query;
use Jp7\Interadmin\Relation\HasMany;
 
/**
 * Class which represents records on the table interadmin_{client name}.
 *
 * @package InterAdmin
 */
class InterAdmin extends InterAdminAbstract {

	/**
	 * To be used temporarily with deprecated methods
	 */
	const DEPRECATED_METHOD = '54dac5afe1fcac2f65c059fc97b44a58';

	/**
	 * DEPRECATED: Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 * @deprecated It will only use this property if there is no id_tipo yet
	 */
	public $db_prefix;
	/**
	 * DEPRECATED: Table suffix of this record. e.g.: the table 'interadmin_client_registrations' would have 'registrations' as $table.
	 * @var string
	 * @deprecated It will only use this property if there is no id_tipo yet
	 */
	public $table;
	/**
	 * Contains the InterAdminTipo, i.e. the record with an 'id_tipo' equal to this record´s 'id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_tipo;
	/**
	 * Contains the parent InterAdmin object, i.e. the record with an 'id' equal to this record's 'parent_id'.
	 * @var InterAdmin
	 */
	protected $_parent;
	/**
	 * Contains an array of objects (InterAdmin and InterAdminTipo).
	 * @var array
	 */
	protected $_tags;
	
	protected $_eagerLoad;
	
	/**
	 * Username to be inserted in the log when saving this record.
	 * @var string
	 */
	protected static $log_user = null;
	/**
	 * If TRUE the records will be filtered using the method getPublishedFilters()
	 * @var bool
	 */
	protected static $publish_filters_enabled = true;
	/**
	 * Timestamp for testing filters with a different date.
	 * @var int
	 */
	protected static $timestamp;
	/**
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * @param string $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias.
	 */
	public function __construct($id = '0', $options = array()) {
		$id = (string) $id;
		$this->id = is_numeric($id) ? $id : '0';
		$this->db_prefix = isset($options['db_prefix']) ? $options['db_prefix'] : InterSite::config()->db->prefix;
		$this->table = isset($options['table']) ? '_' . $options['table'] : '';
		$this->_db = isset($options['db']) ? $options['db'] : null;
		
		if (!empty($options['fields'])) {
			throw new Exception('Deprecated __construct with $options[fields]');
		}
	}
	
	/**
	 * Magic get acessor.
	 * 
	 * @param string $attributeName
	 * @return mixed
	 */
	public function &__get($attributeName) {
		if (isset($this->attributes[$attributeName])) {
			return $this->attributes[$attributeName];
		} else {
			$attributes = array_merge(
				$this->getType()->getCamposAlias($this->getType()->getCamposNames()), 
				$this->getAdminAttributes()
			);
			if (in_array($attributeName, $attributes)) {
				if (class_exists('Debugbar')) {
					Debugbar::warning('N+1 query: Attribute "' . $attributeName . '" was not loaded for ' . get_class($this) . ' - ID: ' . $this->id);
				}
				$this->loadAttributes($attributes);
				return $this->attributes[$attributeName];
			}
			return $null; // Needs to be variable to be returned as reference
		}
	}
	
	public static function __callStatic($name, array $arguments) {
		if ($query = self::query()) {
			return call_user_func_array([$query, $name], $arguments);
		}
		throw new BadMethodCallException('Call to undefined method ' . get_called_class() . '::' . $name);
	}
	
	public static function query() {
		if ($type = self::type()) {
			return new Query($type);
		}
	}
	
	public static function type() {
		$cm = ClassMap::getInstance();
		if ($id_tipo = $cm->getClassIdTipo(get_called_class())) {
			return \InterAdminTipo::getInstance($id_tipo);
		}
	}

	public function hasMany($className, $foreign_key, $local_key = 'id') {
		return new HasMany($this, $className, $foreign_key, $local_key);
	}
	
	/**
	 * Returns an InterAdmin instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class, default_class.
	 * @param InterAdminTipo Set the record´s Tipo.
	 * @return InterAdmin Returns an InterAdmin or a child class in case it's defined on the 'class' property of its InterAdminTipo.
	 */
	public static function getInstance($id, $options = array(), InterAdminTipo $tipo) {
		// Classe foi forçada
		if (isset($options['class'])) {
			$class_name = $options['class'];
		} else {
			$cm = ClassMap::getInstance();
			$class_name = $cm->getClass($tipo->id_tipo);
			if (!$class_name) {
				$class_name = isset($options['default_class']) ? $options['default_class'] : 'InterAdmin';
			}
		}

		$instance = new $class_name($id, $options);
		$instance->setType($tipo);
		$instance->db_prefix = $tipo->db_prefix;
		$instance->setDb($tipo->getDb());
		
		return $instance;
	}
	/**
	 * Finds a Child Tipo by a camelcase keyword. 
	 * 
	 * @param 	string 	$nome_id 	CamelCase
	 * @return 	array 
	 */
	protected function _findChild($nome_id) {
		$children = $this->getType()->getInterAdminsChildren();
		
		if (isset($children[$nome_id])) {
			return $children[$nome_id];
		}
	}
	
	public function getChildrenTipoByNome($nome_id) {
		$child = $this->_findChild($nome_id);
		if ($child) {
			return $this->getChildrenTipo($child['id_tipo']);
		}
	}
	
	/**
	 * Magic method calls
	 * 
	 * Available magic methods:
	 * - create{Child}(array $attributes = array())
	 * - get{Children}(array $options = array())
	 * - getFirst{Child}(array $options = array())
	 * - get{Child}ById(int $id, array $options = array())
	 * - get{Child}ByIdString(int $id, array $options = array())
	 * - delete{Children}(array $options = array())
	 * 
	 * @param string $methodName
	 * @return mixed
	 */
	public function __call($methodName, $args) {
		// childName() - relacionamento
		if ($child = $this->_findChild(ucfirst($methodName))) {
			$childrenTipo = $this->getChildrenTipo($child['id_tipo']);
			if (isset($this->_eagerLoad[$methodName])) {
				return new \Jp7\Interadmin\EagerLoaded($childrenTipo, $this->_eagerLoad[$methodName]);
			}
			return new Query($childrenTipo);
		} elseif ($methodName === 'arquivos' && $this->getType()->arquivos) {
			return new \Jp7\Interadmin\Query\File($this);
		}
		// Default error when method doesn´t exist
		$message = 'Call to undefined method ' . get_class($this) . '->' . $methodName . '(). Available magic methods: ' . "\n";
		$children = $this->getType()->getInterAdminsChildren();
		
		foreach (array_keys($children) as $childName) {
			$message .= "\t\t- " . lcfirst($childName) . "()\n";
		}
		if ($this->getType()->arquivos) {
			$message .= "\t\t- arquivos()\n";
		}

		die(jp7_debug($message));
	}
	/**
	 * Gets fields values by their alias.
	 *  
	 * @param array|string $fields
	 * @see InterAdmin::getFieldsValues()
	 * @deprecated
	 * @return
	 */
	public function getByAlias($fields) {
		throw new Exception('getByAlias() was removed, load fields previously.');
	}
	/**
	 * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
	 * 
	 * @param array $options Default array of options. Available keys: class.
	 * @return InterAdminTipo
	 */
	public function getType($options = array()) {
		if (!$this->_tipo) {
			if (!$id_tipo = $this->id_tipo) {
				global $db;
				$sql = "SELECT id_tipo FROM " . $this->getTableName() . " WHERE id = " . intval($this->id);
				$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
				if ($row = $rs->FetchNextObj()) {
					$id_tipo = $row->id_tipo;
				}				
			}
			$this->setType(InterAdminTipo::getInstance($id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'db' => $this->_db,
				'class' => $options['class']
			)));
		}
		return $this->_tipo;
	}

	/**
	 * Sets the InterAdminTipo object for this record, changing the $_tipo property.
	 *
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function setType(InterAdminTipo $tipo = null) {
		$this->id_tipo = $tipo->id_tipo;
		$this->_tipo = $tipo;
	}
	/**
	 * Gets the parent InterAdmin object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class.
	 * @return InterAdmin
	 */
	public function getParent($options = array()) {
		if (!$this->_parent) {
			$this->loadAttributes(array('parent_id', 'parent_id_tipo'), false);
			
			if ($this->parent_id) {
				if (!$this->parent_id_tipo) {
					throw new Exception('Field parent_id_tipo is required. Id: ' . $this->id);
				}
				$parentTipo = InterAdminTipo::getInstance($this->parent_id_tipo);
				$this->_parent = $parentTipo->records()->find($this->parent_id);
				if ($this->_parent) {
					$this->getType()->setParent($this->_parent);
				}
			}
		}
		return $this->_parent;
	}
	/**
	 * Sets the parent InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdmin $parent
	 * @return void
	 */
	public function setParent(InterAdmin $parent = null) {
		if (isset($parent)) {
			if (!isset($parent->id)) {
				$parent->id = 0; // Necessário para que a referência funcione
			}
			if (!isset($parent->id_tipo)) {
				$parent->id_tipo = 0; // Necessário para que a referência funcione
			}
		}
		$this->attributes['parent_id'] = &$parent->id;
		$this->attributes['parent_id_tipo'] = &$parent->id_tipo;
		$this->_parent = $parent;
	}

	/**
	 * Instantiates an InterAdminTipo object and sets this record as its parent.
	 * 
	 * @param int $id_tipo
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo
	 */
	public function getChildrenTipo($id_tipo, $options = array()) {
		if (empty($options['db_prefix'])) {
			$options['db_prefix'] = $this->getType()->db_prefix;
		}
		$options['default_class'] = static::DEFAULT_NAMESPACE . 'InterAdminTipo';
		$childrenTipo = InterAdminTipo::getInstance($id_tipo, $options);
		$childrenTipo->setParent($this);
		return $childrenTipo;
	}
	
	public function hasChildrenTipo($id_tipo) {
		foreach ($this->getType()->getInterAdminsChildren() as $childrenArr) {
			if ($childrenArr['id_tipo'] == $id_tipo) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Returns siblings records
	 * 
	 * @return InterAdminOptions
	 */
	public function siblings() {
		return $this->getType()->records()->where('id', '<>', $this->id);
	}

	/**
	 * Creates a new InterAdminArquivo with id_tipo, id and mostrar set.
	 * 
	 * @param array $attributes [optional]
	 * @return InterAdminArquivo
	 */
	public function deprecated_createArquivo(array $attributes = array()) {
		$className = static::DEFAULT_NAMESPACE . 'InterAdminArquivo';
		if (!class_exists($className)) {
			$className = 'InterAdminArquivo';
		}
		$arquivo = new $className();
		$arquivo->setParent($this);
		$arquivo->setType($this->getType());
		$arquivo->mostrar = 'S';
		$arquivo->setAttributes($attributes);
		return $arquivo;
	}
	/**
	 * Retrieves the uploaded files of this record.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, limit.
	 * @return array Array of InterAdminArquivo objects.
	 * @deprecated 
	 */
	public function getArquivos($deprecated, $options = array()) {
		if ($deprecated != self::DEPRECATED_METHOD) {
			throw new Exception("Use arquivos()->all() instead.");
		}

		$arquivos = array();
		if (isset($options['class'])) {
			$className = $options['class'];
		} else {
		 	$className = static::DEFAULT_NAMESPACE . 'InterAdminArquivo';
		}
		$arquivoModel = new $className(0);
		$arquivoModel->setType($this->getType());
		
		if (empty($options['fields'])) {
			$options['fields'] = '*';
		}
		
		$this->_resolveWildcard($options['fields'], $arquivoModel);
		$this->_whereArrayFix($options['where']); // FIXME
		
		$options['fields'] = array_merge(array('id_arquivo'), (array) $options['fields']);
		$options['from'] = $arquivoModel->getTableName() . " AS main";
		$options['where'][] = "id_tipo = " . intval($this->id_tipo);
		$options['where'][] = "id = " . intval($this->id);
		$options['order'] = (isset($options['order']) ? $options['order'] . ',' : '') . ' ordem';
		// Internal use
		$options['aliases'] = $arquivoModel->getAttributesAliases();
		$options['campos'] = $arquivoModel->getAttributesCampos();
		
		$rs = $this->_executeQuery($options);
		
		$records = array();
		foreach ($rs as $row) {
			$arquivo = new $className($row->id_arquivo, array(
				'db_prefix' => $this->getType()->db_prefix,
				'db' => $this->_db
			));
			$arquivo->setType($this->getType());
			$arquivo->setParent($this);
			$this->_getAttributesFromRow($row, $arquivo, $options);
			$arquivos[] = $arquivo;
		}
		return new Collection($arquivos);
	}
	
	/**
	 * Deletes all the InterAdminArquivo records related with this record.
	 * 
	 * @param array $options [optional]
	 * @return int Number of deleted arquivos.
	 */
	public function deprecated_deleteArquivos($options = array()) {
		$arquivos = $this->getArquivos($options);
		foreach ($arquivos as $arquivo) {
			$arquivo->delete();
		}
		return count($arquivos);
	}
	
	public function deprecated_createLog(array $attributes = array()) {
		$log = InterAdminLog::create($attributes);
		$log->setParent($this);
		$log->setType($this->getType());
		return $log;
	}
		
	/**
	 * Return URL from the route associated with this record.
	 * 
	 * @param string $action	Defaults to 'show'
	 * @return string
	 * @throws BadMethodCallException
	 */
	public function getUrl($action = 'show') {
		$route = $this->getRoute($action);
		if (!$route) {
			throw new BadMethodCallException('There is no route for id_tipo: ' . $this->id_tipo .
				 ', action: ' . $action . '. Called on ' . get_class($this));
		}
		
		$variables = Route::getVariablesFromRoute($route);
		$hasSlug = in_array($action, array('show', 'edit', 'update', 'destroy'));
		
		if ($hasSlug) {
			$removedVar = array_pop($variables);
		}
		
		$parameters = $this->getUrlParameters($variables);
		
		if ($hasSlug) {
			$parameters[] = $this;
			array_push($variables, $removedVar);
		}
		
		$parameters = array_map(function($p) {
			return $p->id_slug;
		}, $parameters);
		
		if (count($parameters) != count($variables)) {
			throw new BadMethodCallException('Route "' . $route->getUri() . '" has ' . count($variables) . 
					' parameters, but received ' . count($parameters) . '. Called on ' . get_class($this));
		}
		
		return URL::route(null, $parameters, true, $route);		
	}
	
	public function getRoute($action = 'index') {
		return $this->getType()->getRoute($action);
	}
	
	/**
	 * Parameters to be used with URL::route().
	 * 
	 * @param array $variables
	 * @return array
	 */
	public function getUrlParameters(array $variables) {
		$parameters = [];
		$parent = $this;
		foreach ($variables as $variable) {
			if (!$parent = $parent->getParent()) {
				break;
			}
			$parameters[] = $parent;
		}
		return $parameters;
	}
	
	/**
	 * Sets only the editable attributes, prevents the user from setting $id_tipo, for example.
	 * 
	 * @param array $attributes
	 * @return void
	 */
	public function deprecated_setAttributesSafely(array $attributes) {
		$editableFields = array_flip($this->getAttributesAliases());
		$filteredAttributes = array_intersect_key($attributes, $editableFields);
		return $this->setAttributes($filteredAttributes);
	}
	
	/**
	 * Sets the tags for this record. It DELETES the previous records.
	 * 
	 * @param array $tags Array of object to be saved as tags.
	 * @return void
	 */
	public function setTags(array $tags) {
		$db = $this->getDb();
		$sql = "DELETE FROM " . $this->db_prefix . "_tags WHERE parent_id = " .  $this->id;
		$db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		
		foreach ($tags as $tag) {
			$sql = "INSERT INTO " . $this->db_prefix . "_tags (parent_id, id, id_tipo) VALUES 
				(" . $this->id . "," .
				(($tag instanceof InterAdmin) ? $tag->id : 0) . "," .
				(($tag instanceof InterAdmin) ? $tag->getFieldsValues('id_tipo') : $tag->id_tipo) . ")";
			$db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		}
	}
	/**
	 * Returns the tags.
	 * 
	 * @param array $options Available keys: where, group, limit.
	 * @return array
	 */
	public function getTags($options = array()) {
		if (!$this->_tags || $options) {
			$db = $this->getDb();
			
			$options['where'][] = "parent_id = " . $this->id;	
			$sql = "SELECT * FROM " . $this->db_prefix . "_tags " .
				"WHERE " . implode(' AND ', $options['where']) .
				(($options['group']) ? " GROUP BY " . $options['group'] : '') .
				(($options['limit']) ? " LIMIT " . $options['limit'] : '');
			$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
			
			$this->_tags = array();
			while ($row = $rs->FetchNextObj()) {
				if ($tag_tipo = InterAdminTipo::getInstance($row->id_tipo)) {
					$tag_text = $tag_tipo->getFieldsValues('nome');
					if ($row->id) {
						$options = array(
							'fields' => array('varchar_key'),
							'where' => array('id = ' . $row->id)
						);
						if ($tag_registro = $tag_tipo->findFirst($options)) {
							$tag_text = $tag_registro->varchar_key . ' (' . $tag_tipo->nome . ')';
							$tag_registro->interadmin = $this;
							$retorno[] = $tag_registro;
						}
					} else {
						$tag_tipo->interadmin = $this;
						$retorno[] = $tag_tipo;
					}
				}
			}
			$rs->Close();
		} else {
			$retorno = $this->_tags;
		}
		if (!$options) {
			$this->_tags = $retorno; // cache somente para getTags sem $options
		}
		return (array) $retorno;
	}
	/**
	 * Checks if this object is published using the same rules used on interadmin_query().
	 * 
	 * @return bool
	 */
	public function isPublished() {
		global $s_session;
		$config = InterSite::config();
		
		$this->getFieldsValues(array('date_publish', 'date_expire', 'char_key', 'publish', 'deleted'));
		return (
			strtotime($this->date_publish) <= InterAdmin::getTimestamp() &&
			(strtotime($this->date_expire) >= InterAdmin::getTimestamp() || $this->date_expire == '0000-00-00 00:00:00') &&
			$this->char_key &&
			($this->publish || $s_session['preview'] || !$config->interadmin_preview) &&
			!$this->deleted
		);
	}
	/**
	 * DEPRECATED: Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * 
	 * @param array $sqlRow
	 * @param string $tipoLanguage
	 * @deprecated Kept for backwards compatibility
	 * @return mixed
	 */
	protected function _getFieldsValuesAsString($sqlRow, $fields_alias) {
		global $lang;
		$campos = $this->getType()->getCampos();
		
		foreach((array) $sqlRow as $key => $value) {
			if (strpos($key, 'select_') === 0) {
				$tipoObj = $this->getCampoTipo($campos[$key]);
				$value_arr = explode(',', $value);
				$str_arr = array();
				foreach($value_arr as $value_id) {
					$str_arr[] = jp7_fields_values($tipoObj->getInterAdminsTableName(), 'id', $value_id, 'varchar_key');
				}
				$value = implode(', ', $str_arr);
			}
			if ($fields_alias) {
				$alias = $this->_tipo->getCamposAlias($key);
				unset($sqlRow->$key);
			} else {
				$alias = $key;
			}
			$this->$alias = $sqlRow->$alias = $value;
		}
	}
	/**
	 * Returns this object´s varchar_key and all the fields marked as 'combo', if the field 
	 * is an InterAdmin such as a select_key, its getStringValue() method is used.
	 *
	 * @return string For the city 'Curitiba' with the field 'state' marked as 'combo' it would return: 'Curitiba - Paraná'.
	 */
	public function getStringValue() {
		$campos = $this->getType()->getCampos();
		$camposCombo = array();
		if (key_exists('varchar_key', $campos)) {
			$campos['varchar_key']['combo'] = 'S';
		} elseif (key_exists('select_key', $campos)) {
			$campos['select_key']['combo'] = 'S';
		}
		foreach ($campos as $key => $campo) {
			if ($campo['combo']) {
				$camposCombo[] = $campo['tipo'];
			}
		}
		if ($camposCombo) {
			$valoresCombo = $this->getFieldsValues($camposCombo);
			$stringValue = array();
			foreach ($valoresCombo as $key => $value) {
				if ($value instanceof InterAdminFieldFile) {
					continue;
				} elseif ($value instanceof InterAdminAbstract) {
					 $value = $value->getStringValue();
				}
				$stringValue[] = $value;
			}
			return implode(' - ', $stringValue);
		}
	}
	/**
	 * Saves this record and updates date_modify.
	 * 
	 * @return void
	 */
	public function save() {
		// id_slug
		if (isset($this->varchar_key)) {
			$this->id_slug = $this->generateSlug($this->varchar_key);
		} else {
			$alias_varchar_key = $this->getType()->getCamposAlias('varchar_key');
			if (isset($this->$alias_varchar_key)) {
				$this->id_slug = $this->generateSlug($this->$alias_varchar_key);
			}
		}
		// log
		$this->log = date('d/m/Y H:i') . ' - ' . self::getLogUser() . ' - ' . array_get($_SERVER, 'REMOTE_ADDR') . chr(13) . $this->log;
		// date_modify
		$this->date_modify = date('c');
		
		return parent::save();
	}

	public function generateSlug($string) {
		$this->loadAttributes(['id_slug']);
		$newSlug = to_slug($string);
		if (is_numeric($newSlug)) {
			$newSlug = '--' . $newSlug;
		}
		if ($this->id_slug === $newSlug) {
			// Está igual, evitar query
			return $newSlug; 
		}
		$siblingSlugs = $this->siblings()
			->where('id_slug', 'LIKE', "$newSlug%")
			->lists('id_slug');
		
		$i = 2;
		$newSlugCopy = $newSlug;
		while (in_array($newSlug, $siblingSlugs)) {
			$newSlug = $newSlugCopy . $i;
			$i++;
		}
		return $newSlug;
	}
		
	public function getAttributesNames() {
		return $this->getType()->getCamposNames();
	}
	public function getAttributesCampos() {
		return $this->getType()->getCampos();
	}
	public function getCampoTipo($campo) {
		return $this->getType()->getCampoTipo($campo);
	}
	public function getAttributesAliases() {
		return $this->getType()->getCamposAlias();
	}
	public function getTableName() {
		if ($this->id_tipo) {
			return $this->getType()->getInterAdminsTableName();
		} else {
			// Compatibilidade, tenta encontrar na tabela global
			return $this->db_prefix . $this->table;
		}
	}
    /**
     * Returns $log_user. If $log_user is NULL, returns $s_user['login'] on 
     * applications and 'site' otherwise.
     * 
     * @see InterAdmin::$log_user
     * @return string
     */
    public static function getLogUser() {
    	global $jp7_app, $s_user;
    	if (is_null(self::$log_user)) {
    		return ($jp7_app) ? $s_user['login'] : 'site';	
		}
		return self::$log_user;
    }
  	/**
     * Sets $log_user and returns the old value.
     *
     * @see 	InterAdmin::$log_user
     * @param 	object 	$log_user
     * @return 	string	Old value.
     */
    public static function setLogUser($log_user) {
        $old_user = self::$log_user;
		self::$log_user = $log_user;
		return $old_user;
    }
	/**
	 * Enables or disables published filters.
	 * 
	 * @param bool $bool
	 * @return bool Returns the previous value.
	 */
	public static function setPublishedFiltersEnabled($bool) {
		$oldValue = self::$publish_filters_enabled;
		self::$publish_filters_enabled = (bool) $bool;
		return $oldValue;
	}
	/**
	 * Returns TRUE if published filters are enabled.
	 * 
	 * @return bool $bool
	 */
	public static function isPublishedFiltersEnabled() {
		return self::$publish_filters_enabled;
	}
	public static function getTimestamp() {
		return isset(self::$timestamp) ? self::$timestamp : time();
	}
	public static function setTimestamp($time) {
		self::$timestamp = $time;
	}	
	/**
	 * Merges two option arrays.
	 * 
	 * Values of 'where' will be merged
	 * Values of 'fields' will be merged
	 * Other values (such as 'limit') can be overwritten by the $extended array of options.
	 * 
	 * @param array $initial 	Initial array of options.
	 * @param array $extended 	Array of options that will extend the initial array.
	 * @return array 			Array of $options properly merged.
	 */
	public static function mergeOptions($initial, $extended) {
		if (!$extended) {
			return $initial;
		}
		if (isset($initial['fields']) && isset($extended['fields'])) {
			$extended['fields'] = array_merge($extended['fields'], $initial['fields']);
		}
		if (isset($initial['where']) && isset($extended['where'])) {
			if (!is_array($extended['where'])) {
				$extended['where'] = array($extended['where']);
			}
			$extended['where'] = array_merge($extended['where'], $initial['where']);
		}
		return $extended + $initial;
	}
	
	public function getTagFilters() {
		return [
			'tags.id' => $this->id,
			'tags.id_tipo' => intval($this->getType()->id_tipo)
		];
	}
    
    /**
     * @see InterAdminAbstract::getAdminAttributes()
     */
    public function getAdminAttributes() {
		return $this->getType()->getInterAdminsAdminAttributes();
    }
	
    /**
     * Searches $value on the relationship and sets the $attribute  
     * @param string $attribute
     * @param string $searchValue
     * @param string $searchColumn
     * @throws Exception
     */
    public function setAttributeBySearch($attribute, $searchValue, $searchColumn = 'varchar_key') {
		$campos = $this->getType()->getCampos();
		$aliases = array_flip($this->getType()->getCamposAlias());
		$nomeCampo = $aliases[$attribute] ? $aliases[$attribute] : $attribute;
		
		if (!startsWith('select_', $nomeCampo)) {
			throw new Exception('The field ' . $attribute . ' is not a select. It was expected a select field on setAttributeBySearch.');
		}
		
		$campoTipo = $this->getCampoTipo($campos[$nomeCampo]);
		$record = $campoTipo->findFirst(array(
			'where' => array($searchColumn . " = '" . $searchValue . "'")
		));
		if (startsWith('select_multi_', $nomeCampo)) {
			$this->$attribute = array($record);
		} else {
			$this->$attribute = $record;
		}
    }
    
	/**
	 * @deprecated use setAttributeBySearch
	 */
	public function setFieldBySearch($attribute, $searchValue, $searchColumn = 'varchar_key') {
		return $this->setAttributeBySearch($attribute, $searchValue, $searchColumn);
	}
	
	public function setEagerLoad($key, $data) {
		$this->_eagerLoad[$key] = $data;
	}

	/**
	 * Returns varchar_key using its alias, without loading it from DB again
	 */
	public function getName() {
		$varchar_key_alias = $this->getType()->getCamposAlias('varchar_key');
		return $this->$varchar_key_alias;
	}
}