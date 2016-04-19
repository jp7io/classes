<?php

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Class which represents records on the table interadmin_{client name}_tipos.
 *
 * @property string $interadminsOrderby SQL Order By for the records of this InterAdminTipo.
 * @property string $class Class to be instantiated for the records of this InterAdminTipo.
 * @property string $tabela Table of this Tipo, or of its Model, if it has no table.
 */
class InterAdminTipo extends InterAdminAbstract
{
    const ID_TIPO = 0;

    private static $inheritedFields = [
        'class', 'class_tipo', 'icone', 'layout', 'layout_registros', 'tabela',
        'template', 'children', 'campos', 'language', 'editar', 'unico', 'disparo',
        'visualizar', 'xtra_disabledfields', 'xtra_disabledchildren',
    ];
    private static $privateFields = ['children', 'campos'];

    /**
     * Stores metadata to be shared by instances with the same $id_tipo.
     *
     * @var array
     */
    protected static $_metadata;

    protected static $_defaultClass = 'InterAdminTipo';

    protected $_primary_key = 'id_tipo';
    /**
     * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
     *
     * @var string
     */
    public $db_prefix;
    /**
     * Caches the url retrieved by getUrl().
     *
     * @var string
     */
    protected $_url;
    /**
     * Contains the parent InterAdminTipo object, i.e. the record with an 'id_tipo' equal to this record's 'parent_id_tipo'.
     *
     * @var InterAdminTipo
     */
    protected $_parent;

    protected $_tiposUsingThisModel;

    /**
     * Magic method calls(On Development).
     *
     * Available magic methods:
     * - getInterAdminBy{Field}(mixed $value, array $options = array())
     * - getInterAdminsBy{Field}(mixed $value, array $options = array())
     *
     * @param string $method
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (strpos($method, 'find') === 0) {
            if (preg_match('/find(First)?By(?<args>.*)/', $method, $match)) {
                $termos = explode('And', $match['args']);
                $options = $args[count($termos)];
                foreach ($termos as $key => $termo) {
                    $options['where'][] = Jp7_Inflector::underscore($termo)." = '".addslashes($args[$key])."'";
                }
                if ($match[1]) {
                    $options['limit'] = 1;
                }
                $retorno = $this->find($options);

                return ($match[1]) ? reset($retorno) : $retorno;
            }
        }
        // Default error when method doesn´t exist
        die(jp7_debug('Call to undefined method '.get_class($this).'->'.$method.'()'));
    }

    /**
     * Public Constructor. If $options['fields'] is passed the method $this->getFieldsValues() is called.
     * This method has 4 possible calls:.
     *
     * __construct()
     * __construct(string $id_tipo)
     * __construct(array $options)
     * __construct(string $id_tipo, array $options)
     *
     * @param string $id_tipo [optional] This record's 'id_tipo'.
     * @param array  $options [optional] Default array of options. Available keys: db_prefix, fields.
     */
    public function __construct($id_tipo = null, $options = [])
    {
        if (is_null($id_tipo) || is_array($id_tipo)) {
            $options = (array) $id_tipo;
            $id_tipo = static::ID_TIPO;
        }
        // id_tipo must be a string, because in_array will not work with integers and an array of objects
        $id_tipo = (string) $id_tipo;
        $this->id_tipo = is_numeric($id_tipo) ? $id_tipo : '0';
        $this->db_prefix = empty($options['db_prefix']) ? $GLOBALS['db_prefix'] : $options['db_prefix'];
        $this->_db = @$options['db'];

        if (!empty($options['fields'])) {
            $this->getFieldsValues($options['fields']);
        }
    }
    public function &__get($attributeName)
    {
        if (isset($this->attributes[$attributeName])) {
            return $this->attributes[$attributeName];
        }
        if (in_array($attributeName, $this->getColumns())) {
            $this->getFieldsValues($attributeName);

            return $this->attributes[$attributeName];
        }
        return $null; // Needs to be variable to be returned as reference
    }
    /**
     * Returns an InterAdminTipo instance. If $options['class'] is passed,
     * it will be returned an object of the given class, otherwise it will search
     * on the database which class to instantiate.
     *
     * @param int   $id_tipo This record's 'id_tipo'.
     * @param array $options Default array of options. Available keys: db_prefix, fields, class, default_class.
     *
     * @return InterAdminTipo Returns an InterAdminTipo or a child class in case it's defined on its 'class_tipo' property.
     */
    public static function getInstance($id_tipo, $options = [])
    {
        if (empty($options['default_class'])) {
            $options['default_class'] = self::$_defaultClass;
        }
        if (empty($options['class'])) {
            // Classe não foi forçada, cria uma instância temporária para acessar o DB e verificar a classe correta
            $instance = new $options['default_class']($id_tipo, array_merge($options, [
                'fields' => ['model_id_tipo', 'class_tipo'],
            ]));
            $class_name = $instance->class_tipo;
            // Classe não é customizada, retornar a própria classe temporária
            if (!class_exists($class_name)) {
                if (!empty($options['fields'])) {
                    $instance->getFieldsValues($options['fields']);
                }

                return $instance;
            }
        } else {
            // Classe foi forçada
            $class_name = (class_exists($options['class'])) ? $options['class'] : $options['default_class'];
        }
        // Classe foi encontrada, instanciar o objeto
        return new $class_name($id_tipo, $options);
    }
    public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false)
    {
        if (!isset($this->attributes['model_id_tipo'])) {
            $eagerload = ['nome', 'language', 'parent_id_tipo', 'campos', 'model_id_tipo', 'tabela', 'class', 'class_tipo', 'template', 'children'];
            $neededFields = array_unique(array_merge((array) $fields, $eagerload));
            $values = parent::getFieldsValues($neededFields);
            if (is_array($fields)) {
                return $values;
            } else {
                return @$values->$fields;
            }
        }

        return parent::getFieldsValues($fields);
    }
    /**
     * Gets the parent InterAdminTipo object for this record, which is then cached on the $_parent property.
     *
     * @param array $options Default array of options. Available keys: db_prefix, fields, class.
     *
     * @return InterAdminTipo|InterAdminAbstract
     */
    public function getParent($options = [])
    {
        if ($this->_parent) {
            return $this->_parent;
        }
        if ($this->parent_id_tipo || $this->getFieldsValues('parent_id_tipo')) {
            $options['default_class'] = static::DEFAULT_NAMESPACE.'InterAdminTipo';

            return $this->_parent = self::getInstance($this->parent_id_tipo, $options);
        }
    }
    public function getBreadcrumb()
    {
        $parents = [];
        $parent = $this;
        do {
            $parents[] = $parent;
        } while (($parent = $parent->getParent()) && $parent->id_tipo);

        return array_reverse($parents);
    }

    /**
     * Sets the parent InterAdminTipo or InterAdmin object for this record, changing the $_parent property.
     *
     * @param InterAdminAbstract $parent
     */
    public function setParent(InterAdminAbstract $parent = null)
    {
        $this->_parent = $parent;
    }
    /**
     * Retrieves the children of this InterAdminTipo.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return array Array of InterAdminTipo objects.
     */
    public function getChildren($options = [])
    {
        $this->_whereArrayFix($options['where']); // FIXME

        $options['fields'] = array_merge(['id_tipo'], (array) $options['fields']);
        $options['from'] = $this->getTableName().' AS main';
        $options['where'][] = 'parent_id_tipo = '.$this->id_tipo;
        if (!$options['order']) {
            $options['order'] = 'ordem, nome';
        }
        // Internal use
        $options['aliases'] = $this->getAttributesAliases();
        $options['campos'] = $this->getAttributesCampos();

        $rs = $this->_executeQuery($options);

        $tipos = [];
        while ($row = $rs->FetchNextObj()) {
            $tipo = self::getInstance($row->id_tipo, [
                'db_prefix' => $this->db_prefix,
                'db' => $this->_db,
                'class' => $options['class'],
                'default_class' => static::DEFAULT_NAMESPACE.'InterAdminTipo',
            ]);
            $tipo->setParent($this);
            $this->_getAttributesFromRow($row, $tipo, $options);
            $tipos[] = $tipo;
        }
        $rs->Close();

        return $tipos;
    }
    /**
     * Gets the first child.
     *
     * @param array $options [optional]
     *
     * @return InterAdminTipo
     */
    public function getFirstChild($options = [])
    {
        $retorno = $this->getChildren(['limit' => 1] + $options);

        return $retorno[0];
    }
    /**
     * Retrieves the first child of this InterAdminTipo with the given "model_id_tipo".
     *
     * @param string|int $model_id_tipo
     * @param array      $options       Default array of options. Available keys: fields, where, order, class.
     *
     * @return InterAdminTipo
     */
    public function getFirstChildByModel($model_id_tipo, $options = [])
    {
        $retorno = $this->getChildrenByModel($model_id_tipo, ['limit' => 1] + $options);

        return $retorno[0];
    }
    /**
     * Retrieves the first child of this InterAdminTipo with the given "nome".
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return InterAdminTipo
     */
    public function getFirstChildByNome($nome, $options = [])
    {
        $options['where'][] = "nome = '".$nome."'";

        return $this->getFirstChild($options);
    }
    /**
     * Retrieves the children of this InterAdminTipo which have the given model_id_tipo.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return Array of InterAdminTipo objects.
     */
    public function getChildrenByModel($model_id_tipo, $options = [])
    {
        $options['where'][] = "model_id_tipo = '".$model_id_tipo."'";
        // Necessário enquanto algumas tabelas ainda tem esse campo numérico
        $options['where'][] = "model_id_tipo != '0'";

        return $this->getChildren($options);
    }

    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
     *
     * @return InterAdmin[] Array of InterAdmin objects.
     */
    public function find($options = [])
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);

        $options['where'][] = 'id_tipo = '.$this->id_tipo;
        if ($this->_parent instanceof InterAdmin) {
            $options['where'][] = 'parent_id = '.intval($this->_parent->id);
        }

        $rs = $this->_executeQuery($options, $select_multi_fields);
        $options['select_multi_fields'] = $select_multi_fields;

        $records = [];
        while ($row = $rs->FetchNextObj()) {
            $record = InterAdmin::getInstance($row->id, $optionsInstance, $this);
            if ($this->_parent instanceof InterAdmin) {
                $record->setParent($this->_parent);
            }
            $this->_getAttributesFromRow($row, $record, $options);
            $records[] = $record;
        }
        $rs->Close();

        return $records;
    }

    public function distinct($column, $options = [])
    {
        return $this->_aggregate('DISTINCT', $column, $options);
    }

    public function max($column, $options = [])
    {
        $retorno = $this->_aggregate('MAX', $column, $options);

        return $retorno[0];
    }

    public function min($column, $options = [])
    {
        $retorno = $this->_aggregate('MIN', $column, $options);

        return $retorno[0];
    }

    public function sum($column, $options = [])
    {
        $retorno = $this->_aggregate('SUM', $column, $options);

        return $retorno[0];
    }

    public function avg($column, $options = [])
    {
        $retorno = $this->_aggregate('AVG', $column, $options);

        return $retorno[0];
    }

    protected function _aggregate($function, $column, $options)
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);

        $options['fields'] = $function.'('.$column.') AS values';
        $options['where'][] = 'id_tipo = '.$this->id_tipo;

        if (isset($options['group'])) {
            throw new Exception('This method cannot be used with GROUP BY.');
        }

        if ($this->_parent instanceof InterAdmin) {
            $options['where'][] = 'parent_id = '.intval($this->_parent->id);
        }

        $rs = $this->_executeQuery($options);
        $array = [];
        while ($row = $rs->FetchNextObj()) {
            $array[] = $row->{'main.values'};
        }

        return $array;
    }

    /**
     * @deprecated Use find() instead.
     *
     * @param array $options
     */
    public function getInterAdmins($options = [])
    {
        return $this->find($options);
    }

    /**
     * Returns the number of InterAdmins using COUNT(id).
     *
     * @param array $options Default array of options. Available keys: where.
     *
     * @return int Count of InterAdmins found.
     */
    public function count($options = [])
    {
        if ($options['group'] == 'id') {
            // O COUNT() precisa trazer a contagem total em 1 linha
            // Caso exista GROUP BY id, ele traria em várias linhas
            // Esse é um tratamento especial apenas para o ID
            $options['fields'] = ['COUNT(DISTINCT id) AS count_id'];
            unset($options['group']);
        } elseif ($options['group']) {
            // Se houver GROUP BY com outro campo, retornará a contagem errada
            throw new Exception('GROUP BY is not supported when using count().');
        } else {
            $options['fields'] = ['COUNT(id) AS count_id'];
        }
        $retorno = $this->findFirst($options);

        return intval($retorno->count_id);
    }
    /**
     * @deprecated Use count() instead
     *
     * @param unknown $options
     */
    public function getInterAdminsCount($options = [])
    {
        return $this->count($options);
    }

    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, class.
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findFirst($options = [])
    {
        $result = $this->find(['limit' => 1] + $options);
        return reset($result);
    }

    /**
     * @deprecated use findFirst() instead.
     *
     * @param array $options
     *
     * @return InterAdmin
     */
    public function getFirstInterAdmin($options = [])
    {
        return $this->findFirst($options);
    }
    /**
     * Retrieves the unique record which have this id.
     *
     * @param int   $id      Search value.
     * @param array $options
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findById($id, $options = [])
    {
        $options['where'][] = 'id = '.intval((string) $id);

        return $this->findFirst($options);
    }
    /**
     * @deprecated use findById() instead.
     *
     * @param int   $id
     * @param array $options
     *
     * @return InterAdmin
     */
    public function getInterAdminById($id, $options = [])
    {
        return $this->findById($id, $options);
    }
    /**
     * Retrieves the first record which have this id_string.
     *
     * @param string $id_string Search value.
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findByIdString($id_string, $options = [])
    {
        $options['where'][] = "id_string = '".$id_string."'";

        return $this->findFirst($options);
    }
    /**
     * @deprecated use findByIdString() instead.
     *
     * @param string $id_string
     * @param array  $options
     *
     * @return InterAdmin
     */
    public function getInterAdminByIdString($id_string, $options = [])
    {
        return $this->findByIdString($id_string, $options);
    }
    /**
     * Returns the model identified by model_id_tipo, or the object itself if it has no model.
     *
     * @param array $options Default array of options. Available keys: db_prefix, fields.
     *
     * @return InterAdminTipo Model used by this InterAdminTipo.
     */
    public function getModel($options = [])
    {
        if ($this->model_id_tipo || $this->getFieldsValues('model_id_tipo')) {
            if (is_numeric($this->model_id_tipo)) {
                $model = new self($this->model_id_tipo, $options);
            } else {
                $className = 'Jp7_Model_'.$this->model_id_tipo.'Tipo';
                $model = new $className();
            }

            return $model->getModel($options);
        } else {
            return $this;
        }
    }
    /**
     * Returns an array with data about the fields on this type, which is then cached on the $_campos property.
     *
     * @return array
     */
    public function getCampos()
    {
        if (!$A = $this->_getMetadata('campos')) {
            $campos = $this->getFieldsValues('campos');
            //unset($model->campos);
            $campos_parameters = [
                'tipo', 'nome', 'ajuda', 'tamanho', 'obrigatorio', 'separador', 'xtra',
                'lista', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissoes',
                'default', 'nome_id',
            ];
            $campos = explode('{;}', $campos);
            $A = [];
            for ($i = 0; $i < count($campos); $i++) {
                $parameters = explode('{,}', $campos[$i]);
                if ($parameters[0]) {
                    $A[$parameters[0]]['ordem'] = ($i + 1);
                    $isSelect = strpos($parameters[0], 'select_') === 0;
                    for ($j = 0; $j < count($parameters); $j++) {
                        $A[$parameters[0]][$campos_parameters[$j]] = $parameters[$j];
                    }
                    if ($isSelect && $A[$parameters[0]]['nome'] != 'all') {
                        $id_tipo = $A[$parameters[0]]['nome'];
                        $A[$parameters[0]]['nome'] = self::getInstance($id_tipo, [
                            'db_prefix' => $this->db_prefix,
                            'db' => $this->_db,
                            'default_class' => static::DEFAULT_NAMESPACE.'InterAdminTipo',
                        ]);
                    }
                }
            }
            // Alias
            foreach ($A as $campo => $array) {
                if (!empty($array['nome_id'])) {
                    continue;
                }
                $alias = $A[$campo]['nome'];
                if (is_object($alias)) {
                    if ($A[$campo]['label']) {
                        $alias = $A[$campo]['label'];
                    } else {
                        $alias = $alias->getFieldsValues('nome');
                    }
                }
                $A[$campo]['nome_id'] = $alias ? toId($alias) : $campo;
            }
            $this->_setMetadata('campos', $A);
        }

        return $A;
    }
    /**
     * Returns an array with the names of all the fields available.
     *
     * @return array
     */
    public function getCamposNames()
    {
        $fields = array_keys($this->getCampos());
        foreach ($fields as $key => $field) {
            if (strpos($field, 'tit_') === 0 || strpos($field, 'func_') === 0) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }
    
    public function getCamposCombo()
    {
        return array_keys(array_filter($this->getCampos(), function ($campo) {
            return (bool) $campo['combo'] || $campo['tipo'] === 'varchar_key';
        }));
    }
    
    /**
     * Gets the alias for a given field name.
     *
     * @param array|string $fields Fields names, defaults to all fields.
     *
     * @return array|string Resulting alias(es).
     */
    public function getCamposAlias($fields = null)
    {
        $campos = $this->getCampos();
        if (is_null($fields)) {
            $fields = array_keys($campos);
        }
        $aliases = [];
        foreach ((array) $fields as $field) {
            $aliases[$field] = $campos[$field]['nome_id'];
        }
        if (is_array($fields)) {
            return $aliases;
        } else {
            return reset($aliases);
        }
    }
    /**
     * Returns the InterAdminTipo for a field.
     *
     * @param object $campo
     *
     * @return InterAdminTipo
     */
    public function getCampoTipo($campo)
    {
        if (is_object($campo['nome'])) {
            return $campo['nome'];
        } elseif ($campo['nome'] == 'all') {
            return new self();
        }
    }
    /**
     * Returns this object´s nome and all the fields marked as 'combo', if the field
     * is an InterAdminTipo such as a select_key, its getStringValue() method is used.
     *
     * @return string For the tipo 'City' with the field 'state' marked as 'combo' it would return: 'City - State'.
     */
    public function getStringValue(/*$simple = FALSE*/)
    {
        $campos = $this->getCampos();
        $return[] = $this->getFieldsValues('nome');
        //if (!$simple) {
            foreach ($campos as $key => $row) {
                if (($row['combo'] || $key == 'varchar_key' || $key == 'select_key') && $key !== 'char_key') {
                    if (is_object($row['nome'])) {
                        $return[] = $row['nome']->getStringValue();
                    } else {
                        $return[] = $row['nome'];
                    }
                }
            }
        //}
        return implode(' - ', $return);
    }
    /**
     * Returns the nome according to the $lang.
     *
     * @return string
     */
    public function getNome()
    {
        global $lang;
        if ($lang->prefix) {
            $this->getFieldsValues(['nome', 'nome'.$lang->prefix]);

            return $this->{'nome'.$lang->prefix} ? $this->{'nome'.$lang->prefix} : $this->nome;
        } else {
            return $this->getFieldsValues('nome');
        }
    }
    /**
     * Returns the full url for this InterAdminTipo.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->_url) {
            return $this->_url;
        }
        global $config, $implicit_parents_names, $seo, $lang;
        $url = '';
        $url_arr = '';
        $parent = $this;
        while ($parent) {
            if (!isset($parent->nome)) {
                $parent->getFieldsValues('nome');
            }
            if ($seo) {
                if (!in_array($parent->nome, (array) $implicit_parents_names)) {
                    $url_arr[] = toSeo($parent->nome);
                }
            } else {
                if (toId($parent->nome)) {
                    $url_arr[] = toId($parent->nome);
                }
            }
            $parent = $parent->getParent();
            if ($parent instanceof InterAdmin) {
                $parent = $parent->getTipo();
            }
        }
        $url_arr = array_reverse((array) $url_arr);

        if ($seo) {
            $url = $config->url.$lang->path.jp7_implode('/', $url_arr);
        } else {
            $url = $config->url.$lang->path_url.implode('_', $url_arr);
            $pos = strpos($url, '_');
            if ($pos) {
                $url = substr_replace($url, '/', $pos, 1);
            }
            $url .= (count($url_arr) > 1) ? '.php' : '/';
        }

        return $this->_url = $url;
    }
    /**
     * Returns the names of the parents separated by '/', e.g. 'countries/south-america/brazil'.
     *
     * @return string
     */
    public function getTreePath()
    {
        global $config, $implicit_parents_names, $seo, $lang;
        $url = '';
        $url_arr = '';
        $parent = $this;
        while ($parent) {
            if ($seo) {
                if (!in_array($parent->getFieldsValues('nome'), (array) $implicit_parents_names)) {
                    $url_arr[] = toSeo($parent->getFieldsValues('nome'));
                }
            } else {
                $url_arr[] = $parent->getFieldsValues('nome');
            }
            $parent = $parent->getParent();
        }
        $url_arr = array_reverse((array) $url_arr);

        $url = $config->url.implode('/', $url_arr);

        return $url;
    }
    /**
     * Saves this InterAdminTipo.
     */
    public function save()
    {
        // id_tipo_string
        if (isset($this->nome)) {
            $this->id_tipo_string = toId($this->nome);
        }

        $this->id_slug = toSlug($this->nome);

        // log
        if ($this->id_tipo && !isset($this->log)) {
            // Evita bug em que um tipo despublicado tem seu log zerado
            $old_value = InterAdmin::setPublishedFiltersEnabled(false);
            $this->getFieldsValues('log');
            InterAdmin::setPublishedFiltersEnabled($old_value);
        }
        $this->date_modify = date('c');
        $this->log = date('d/m/Y H:i').' - '.InterAdmin::getLogUser().' - '.$_SERVER['REMOTE_ADDR'].chr(13).$this->log;

        // Inheritance
        $this->syncInheritance();
        $retorno = parent::save();

        // Inheritance - Tipos inheriting from this Tipo
        if ($this->id_tipo) {
            $inheritingTipos = self::findTiposByModel($this->id_tipo, [
                'class' => 'InterAdminTipo',
            ]);
            foreach ($inheritingTipos as $tipo) {
                $tipo->syncInheritance();
                $tipo->updateAttributes($tipo->attributes);
            }
        }

        return $retorno;
    }

    public function syncInheritance()
    {
        // cache dos atributos herdados
        $this->getFieldsValues(array_merge(['model_id_tipo', 'inherited'], self::$inheritedFields));

        // Retornando ao valor real
        foreach (jp7_explode(',', $this->inherited) as $inherited_var) {
            $this->attributes[$inherited_var] = '';
        }
        $this->inherited = [];
        // Atualizando cache com dados do modelo
        if ($this->model_id_tipo) {
            if (is_numeric($this->model_id_tipo)) {
                $modelo = new self($this->model_id_tipo);
                $modelo->getFieldsValues(self::$inheritedFields);
            } else {
                $className = 'Jp7_Model_'.$this->model_id_tipo.'Tipo';
                if (class_exists($className)) {
                    $modelo = new $className();
                } else {
                    echo 'Erro: Class '.$className.' not found';
                }
            }
            if ($modelo) {
                foreach (self::$inheritedFields as $field) {
                    if ($modelo->$field) {
                        if (!$this->$field || in_array($field, self::$privateFields)) {
                            $this->inherited[] = $field;
                            $this->$field = $modelo->$field;
                        }
                    }
                }
            }
        }
        $this->inherited = implode(',', $this->inherited);
    }
    /**
     * Sets this row as deleted as saves it.
     *
     * @return
     */
    public function delete()
    {
        $this->deleted_tipo = 'S';
        $this->save();
    }
    /**
     * Deletes all the InterAdmins.
     *
     * @param array $options [optional]
     *
     * @return int Count of deleted InterAdmins.
     */
    public function deleteInterAdmins($options = [])
    {
        $records = $this->find($options);
        foreach ($records as $record) {
            $record->delete();
        }

        return count($records);
    }

    /**
     * Deletes all the InterAdmins forever.
     *
     * @param array $options [optional]
     *
     * @return int Count of deleted InterAdmins.
     */
    public function deleteInterAdminsForever($options = [])
    {
        $records = $this->find($options);
        foreach ($records as $record) {
            $record->deleteForever();
        }

        return count($records);
    }

    /**
     * Updates all the InterAdmins.
     *
     * @param array $attributes Attributes to be updated
     * @param array $options    [optional]
     *
     * @return int Count of updated InterAdmins.
     */
    public function updateInterAdmins($attributes, $options = [])
    {
        $records = $this->find($options);
        foreach ($records as $record) {
            $record->updateAttributes($attributes);
        }

        return count($records);
    }

    public function getAttributesNames()
    {
        return $this->getColumns();
    }
    public function getAttributesCampos()
    {
        return [];
    }
    public function getAttributesAliases()
    {
        return [];
    }
    public function getTableName()
    {
        return $this->db_prefix.'_tipos';
    }
    public function getInterAdminsOrder($order = '')
    {
        if (!$interadminsOrderBy = $this->_getMetadata('interadmins_order')) {
            $interadminsOrderBy = [];
            $campos = $this->getCampos();
            if ($campos) {
                foreach ($campos as $key => $row) {
                    if ($row['orderby'] && strpos($key, 'func_') === false) {
                        if ($row['orderby'] < 0) {
                            $key .= ' DESC';
                        }
                        $interadminsOrderBy[$row['orderby']] = $key;
                    }
                }
                if ($interadminsOrderBy) {
                    ksort($interadminsOrderBy);
                }
            }
            $interadminsOrderBy[] = 'date_publish DESC';
            $this->_setMetadata('interadmins_order', $interadminsOrderBy);
        }
        if ($order) {
            $order = explode(',', $order);
            $interadminsOrderBy = array_unique(array_merge($order, $interadminsOrderBy));
        }

        return implode(',', $interadminsOrderBy);
    }
    /**
     * Returns the table name for the InterAdmins.
     *
     * @return string
     */
    public function getInterAdminsTableName()
    {
        return $this->_getTableLang().($this->tabela ?: 'registros');
    }
    /**
     * Returns the table name for the files.
     *
     * @return string
     */
    public function getArquivosTableName()
    {
        return $this->_getTableLang().'arquivos';
    }
    /**
     * Returns $db_prefix OR $db_prefix + $lang->prefix.
     *
     * @return string
     */
    protected function _getTableLang()
    {
        global $lang;
        $table = $this->db_prefix;
        if (!isset($this->language)) {
            $this->getFieldsValues('language');
        }
        if ($this->language) {
            $table .= $lang->prefix;
        }

        return $table.'_';
    }
    protected function _setMetadata($varname, $value)
    {
        $db = $this->getDb();

        self::$_metadata[$db->host.'/'.$db->database.'/'.$this->db_prefix][$this->id_tipo][$varname] = $value;
    }
    protected function _getMetadata($varname)
    {
        $db = $this->getDb();

        return @self::$_metadata[$db->host.'/'.$db->database.'/'.$this->db_prefix][$this->id_tipo][$varname];
    }
    /**
     * Returns metadata about the children tipos that the InterAdmins have.
     *
     * @return array
     */
    public function getInterAdminsChildren()
    {
        if (!$children = $this->_getMetadata('children')) {
            //$model = $this->getModel();

            $children = [];
            $childrenArr = explode('{;}', $this->getFieldsValues('children'));
            for ($i = 0; $i < count($childrenArr) - 1; $i++) {
                $childrenArrParts = explode('{,}', $childrenArr[$i]);
                if (count($childrenArrParts) < 4) { // 4 = 'id_tipo', 'nome', 'ajuda', 'netos'
                    // Fix para tipos com estrutura antiga e desatualizada
                    $childrenArrParts = array_pad($childrenArrParts, 4, '');
                }
                $child = array_combine(['id_tipo', 'nome', 'ajuda', 'netos'], $childrenArrParts);
                $nome_id = Jp7_Inflector::camelize($child['nome']);
                $children[$nome_id] = $child;
            }
            $this->_setMetadata('children', $children);
        }

        return $children;
    }

    /**
     * Returns a InterAdminTipo if the $nome_id is found in getInterAdminsChildren().
     *
     * @param string $nome_id Camel Case name, e.g.: DadosPessoais
     *
     * @return InterAdminTipo
     */
    public function getInterAdminsChildrenTipo($nome_id)
    {
        $childrenTipos = $this->getInterAdminsChildren();
        $id_tipo = $childrenTipos[$nome_id]['id_tipo'];
        if ($id_tipo) {
            return self::getInstance($id_tipo, [
                'db_prefix' => $this->db_prefix,
                'db' => $this->_db,
                'default_class' => static::DEFAULT_NAMESPACE.'InterAdminTipo',
            ]);
        }
    }

    /**
     * Creates a record with id_tipo, mostrar, date_insert and date_publish filled.
     *
     * @param array $attributes Attributes to be merged into the new record.
     *
     * @return InterAdmin
     */
    public function createInterAdmin(array $attributes = [])
    {
        $options = ['default_class' => static::DEFAULT_NAMESPACE.'InterAdmin'];
        $record = InterAdmin::getInstance(0, $options, $this);
        $mostrar = $this->getCamposAlias('char_key');
        $record->$mostrar = 'S';
        $record->date_publish = date('c');
        $record->date_insert = date('c');
        $record->log = '';
        if ($this->_parent instanceof InterAdmin) {
            $record->setParent($this->_parent);
            // Childs are published by default on InterAdmin.
            $record->publish = 'S';
        }
        $record->setAttributes($attributes);

        return $record;
    }

    public function createChild($model_id_tipo = 0)
    {
        $child = new self();
        $child->db_prefix = $this->db_prefix;
        $child->model_id_tipo = $model_id_tipo;
        $child->parent_id_tipo = $this->id_tipo;
        $child->mostrar = 'S';

        return $child;
    }

    /**
     * Returns the InterAdmins having the given tags.
     *
     * @param InterAdmin[] $tags
     * @param array        $options [optional]
     *
     * @return InterAdmin[]
     */
    public function findByTags($tags, $options = [])
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        $tagsWhere = [];
        foreach ($tags as $tag) {
            if ($tag instanceof InterAdminAbstract) {
                $tagsWhere[] = $tag->getTagFilters();
            } elseif (is_numeric($tag)) {
                $tagsWhere[] = '(tags.id_tipo = '.$tag.' AND tags.id > 0)';
            }
        }
        if (!$tagsWhere) {
            return [];
        }
        $options['where'][] = '('.implode(' OR ', $tagsWhere).')';

        return $this->find($options);
    }
    /**
     * @deprecated Use findByTags() instead
     *
     * @param InterAdmin[] $tags
     * @param array        $options
     *
     * @return InterAdmin[]
     */
    public function getInterAdminsByTags($tags, $options = [])
    {
        return $this->findByTags($tags, $options);
    }

    /**
     * Returns all InterAdminTipo's using this InterAdminTipo as a model (model_id_tipo).
     *
     * @param array $options [optional]
     *
     * @return InterAdminTipo[] Array of Tipos indexed by their id_tipo.
     */
    public function getTiposUsingThisModel($options = [])
    {
        if (!isset($this->_tiposUsingThisModel)) {
            $options2 = [
                'fields' => 'id_tipo',
                'from' => $this->getTableName().' AS main',
                'where' => [
                    "model_id_tipo = '".$this->id_tipo."'",
                ],
            ];
            $rs = $this->_executeQuery($options2);

            $options['default_class'] = static::DEFAULT_NAMESPACE.'InterAdminTipo';
            $this->_tiposUsingThisModel = [];
            while ($row = $rs->FetchNextObj()) {
                $this->_tiposUsingThisModel[$row->id_tipo] = self::getInstance($row->id_tipo, $options);
            }
            $this->_tiposUsingThisModel[$this->id_tipo] = $this;
        }

        return $this->_tiposUsingThisModel;
    }
    /**
     * Retrieves the first InterAdminTipo from the database.
     *
     * @param array $options [optional]
     *
     * @return InterAdminTipo
     */
    public static function findFirstTipo($options = [])
    {
        $result = self::findTipos(['limit' => 1] + $options);
        return reset($result);
    }
    /**
     * Retrieves the first InterAdminTipo with the given "model_id_tipo".
     *
     * @param string|int $model_id_tipo
     * @param array      $options       [optional]
     *
     * @return InterAdminTipo
     */
    public static function findFirstTipoByModel($model_id_tipo, $options = [])
    {
        $result = self::findTiposByModel($model_id_tipo, ['limit' => 1] + $options);
        return reset($result);
    }
    /**
     * Retrieves all the InterAdminTipo with the given "model_id_tipo".
     *
     * @param string|int $model_id_tipo
     * @param array      $options       [optional]
     *
     * @return array
     */
    public static function findTiposByModel($model_id_tipo, $options = [])
    {
        $options['where'][] = "model_id_tipo = '".$model_id_tipo."'";
        if ($model_id_tipo != '0') {
            // Devido à mudança de int para string do campo model_id_tipo, essa linha é necessária
            $options['where'][] = "model_id_tipo != '0'";
        }

        return self::findTipos($options);
    }
    /**
     * Retrieves multiple InterAdminTipo's from the database.
     *
     * @param array $options [optional]
     *
     * @return InterAdminTipo[]
     */
    public static function findTipos($options = [])
    {
        $instance = new self();
        if ($options['db']) {
            $instance->setDb($options['db']);
        }
        if ($options['db_prefix']) {
            $instance->db_prefix = $options['db_prefix'];
        }

        $options['fields'] = array_merge(['id_tipo'], (array) $options['fields']);
        $options['from'] = $instance->getTableName().' AS main';
        if (!$options['where']) {
            $options['where'][] = '1 = 1';
        }
        if (!$options['order']) {
            $options['order'] = 'ordem, nome';
        }
        // Internal use
        $options['aliases'] = $instance->getAttributesAliases();
        $options['campos'] = $instance->getAttributesCampos();

        $rs = $instance->_executeQuery($options);
        $tipos = [];

        while ($row = $rs->FetchNextObj()) {
            $tipo = self::getInstance($row->id_tipo, [
                'db_prefix' => $instance->db_prefix,
                'db' => $instance->getDb(),
                'class' => $options['class'],
            ]);
            $instance->_getAttributesFromRow($row, $tipo, $options);
            $tipos[] = $tipo;
        }

        return $tipos;
    }

    protected function _prepareInterAdminsOptions(&$options, &$optionsInstance)
    {
        $this->_whereArrayFix($options['where']); // FIXME

        $optionsInstance = [
            'class' => @$options['class'],
            'default_class' => static::DEFAULT_NAMESPACE.'InterAdmin',
        ];

        $recordModel = InterAdmin::getInstance(0, $optionsInstance, $this);
        $defaultFields = static::DEFAULT_FIELDS;
        if ($defaultFields && strpos($defaultFields, ',') !== false) {
            $defaultFields = explode(',', $defaultFields);
        }
        $options = $options + ['fields' => $defaultFields, 'fields_alias' => static::DEFAULT_FIELDS_ALIAS];

        $this->_resolveWildcard($options['fields'], $recordModel);
        if (count($options['fields']) != 1 || strpos($options['fields'][0], 'COUNT(') === false) {
            $options['fields'] = array_merge(['id', 'id_tipo'], (array) $options['fields']);
        }
        $options['from'] = $recordModel->getTableName().' AS main';
        $options['order'] = $this->getInterAdminsOrder(@$options['order']);
        // Internal use
        $options['aliases'] = $recordModel->getAttributesAliases();
        $options['campos'] = $recordModel->getAttributesCampos();
    }

    /**
     * Returns all records having an InterAdminTipo that uses this as a model (model_id_tipo).
     *
     * @param array $options [optional]
     *
     * @return InterAdmin[]
     */
    public function getInterAdminsUsingThisModel($options = [])
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);

        $tipos = $this->getTiposUsingThisModel();
        $options['where'][] = 'id_tipo IN ('.implode(',', $tipos).')';

        $rs = $this->_executeQuery($options);
        $records = [];
        while ($row = $rs->FetchNextObj()) {
            $record = InterAdmin::getInstance($row->id, $optionsInstance, $tipos[$row->id_tipo]);
            $this->_getAttributesFromRow($row, $record, $options);
            $records[] = $record;
        }

        return $records;
    }

    public function getTagFilters()
    {
        return '(tags.id_tipo = '.$this->id_tipo.' AND tags.id = 0)';
    }

    /**
     * Returns $_defaultClass.
     *
     * @see InterAdminTipo::$_defaultClass
     */
    public static function getDefaultClass()
    {
        return self::$_defaultClass;
    }

    /**
     * Sets $_defaultClass.
     *
     * @param object $_defaultClass
     *
     * @see InterAdminTipo::$_defaultClass
     */
    public static function setDefaultClass($defaultClass)
    {
        self::$_defaultClass = $defaultClass;
    }

    /**
     * @see InterAdminAbstract::getAdminAttributes()
     */
    public function getAdminAttributes()
    {
        return [];
    }

    public function where($_)
    {
        $options = new InterAdminOptions($this);

        return call_user_func_array([$options, 'where'], func_get_args());
    }
    
    public function whereRaw($where)
    {
        return (new InterAdminOptions($this))->whereRaw($where);
    }

    public function fields($_)
    {
        $options = new InterAdminOptions($this);

        return call_user_func_array([$options, 'fields'], func_get_args());
    }

    public function join($alias, $tipo, $on)
    {
        return (new InterAdminOptions($this))->join($alias, $tipo, $on);
    }

    public function leftJoin($alias, $tipo, $on)
    {
        return (new InterAdminOptions($this))->leftJoin($alias, $tipo, $on);
    }

    public function rightJoin($alias, $tipo, $on)
    {
        return (new InterAdminOptions($this))->rightJoin($alias, $tipo, $on);
    }

    public function limit($offset, $rows = null)
    {
        return (new InterAdminOptions($this))->limit($offset, $rows);
    }

    public function group($group)
    {
        return (new InterAdminOptions($this))->group($group);
    }

    public function order($_)
    {
        $options = new InterAdminOptions($this);

        return call_user_func_array([$options, 'order'], func_get_args());
    }
    
    public function debug($debug = true)
    {
        return (new InterAdminOptions($this))->debug($debug);
    }
    
    public function published($filters = true)
    {
        return (new InterAdminOptions($this))->published($filters);
    }
    
    public function all()
    {
        return (new InterAdminOptions($this))->all();
    }
    
    /**
     * Retrieves the first records which have this InterAdminTipo's id_tipo.
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function first()
    {
        $result = $this->limit(1)->all();
        return reset($result);
    }
}
