<?php

namespace Jp7\Interadmin;
use InterAdminTipo, InterAdmin, BadMethodCallException;

class Query extends Query\Base {
	
	protected function _isChar($field) {
		$aliases = array_flip($this->provider->getCamposAlias());
		if (isset($aliases[$field])) {
			return strpos($aliases[$field], 'char_') === 0;
		} else {
			return strpos($field, 'char_') === 0;
		}
	}
	
	public function joinThrough(InterAdminTipo $tipo, $relationshipPath) {
		$path = explode('.', $relationshipPath);
		$tableLeft = array_shift($path);
		
		$joins = array();
		
		while ($relationship = array_shift($path)) {
			$relationshipData = $tipo->getRelationshipData($relationship);
			$tableRight = (empty($path)) ? '' : $relationship . '.';
			
			if ($relationshipData['type'] == 'children') {
				$joins[] = [$tableLeft, $tipo, "{$tableLeft}.id = {$tableRight}parent_id"];
			} else {
				$joins[] = [$tableLeft, $tipo, "{$tableLeft}.{$relationship} = {$tableRight}id"];
			}
			
			$tableLeft = $relationship;
			$tipo = $relationshipData['tipo'];
		}
		
		foreach (array_reverse($joins) as $join) {
			$this->join($join[0], $join[1], $join[2]);
		}
		
		return $this;
	}

	public function taggedWith() {
		foreach (func_get_args() as $tag) {
			$this->where($tag->getTagFilters());
		}
		return $this;
	}

	/**
	 * @return InterAdmin[]
	 */
	public function all() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->provider->deprecatedFind($this->options);
	}
	
	public function first() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->provider->findFirst(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	public function count() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->provider->count(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	public function find($id) {
		if (func_num_args() != 1) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 1.');
		
		if (is_numeric($id)) {
			$this->options['where'][] = "id = " . $this->_escapeParam($id);
		} elseif (is_string($id)) {
			$this->options['where'][] = "id_slug = " . $this->_escapeParam($id);
		} else {
			throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use all() instead of find().');
		}

		return $this->provider->findFirst(InterAdmin::DEPRECATED_METHOD, $this->options);
	}
	
	public function findFirst() {
		throw new BadMethodCallException('Use first() instead of findFirst().');
	}
	
	public function __call($method_name, $params) {
		/*
		$last = count($params) - 1;
		if (is_array($params[$last])) {
			$params[$last] = InterAdmin::mergeOptions($this->options, $params[$last]);
		} else {
			$params[] = $this->options;
		}
		*/		
		$retorno = call_user_func_array([$this->provider, $method_name], $params);
		if ($retorno instanceof self) {
			$this->options = InterAdmin::mergeOptions($this->options, $retorno->getOptionsArray());
			return $this;
		}
		throw new BadMethodCallException('Unsupported method ' . $method_name);
		//return $retorno;
	}
	
}