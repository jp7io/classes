<?php
class Jp7_Collections {
 	/**
	 * Acts like a SELECT statement. Performs 'where', 'order', 'group' and 'limit'.
	 * 
	 * @param array $array
	 * @param array $options Available keys are 'where', 'order', 'group' and 'limit'.
	 * @return array Processed collection.
	 */
	public static function query($array, $options) {
		if ($array) {
			if ($options['where']) {
				$array = self::filter($array, $options['where']);
			}
			if ($options['group']) {
				$array = self::group($array, $options['group']);
			}
			if ($options['order']) {
				$array = self::sort($array, $options['order']);
			}
			if ($options['limit']) {
				$array = self::slice($array, $options['limit']);
			}
		}
		return $array;
	}
	/**
	 * Acts like a SQL GROUP BY  statement.
	 *
	 * @param array $array
	 * @param string $clause
	 * @return array
	 */
	public static function group($array, $clause) {
		// @todo
	}
	/**
	 * Filters the array using SQL Where.
	 * 
	 * @param array $array
	 * @param string $clause Similar to SQL WHERE Clause.
	 * @return array
	 */
	public static function filter($array, $clause) {
		$clause = preg_replace('/([^=])(=)([^=])/', '\1\2=\3', $clause);
		$clause = preg_replace('/(\b[a-zA-Z0-9_.]+\b)/', '$a->\1', $clause);
		$fnBody = 'return ' . $clause . ';';
		
		return array_filter($array, create_function('$a', $fnBody));
	}
	/**
	 * Flips an array of $itens->subitem into an array of $subitem->itens; 
	 * 
	 * @param object $compactedArray Such as array('newPropertyName' => $array).
	 * @param object $property Name of the property to be flipped.
	 * @return array
	 */
	public static function flip($compactedArray, $property) {
		$newPropertyName = key($compactedArray);
		$subitens = array();
		foreach ($compactedArray[$newPropertyName] as $item) {
			$subitem = $item->$property;
			unset($item->$property);
			$key = $subitem->__toString();
			if (!in_array($key, $subitens)) {
				$subitem->$newPropertyName = array();
				$subitens[$key] = $subitem;
			}
			$subitens[$key]->{$newPropertyName}[] = $item;
		}
		return $subitens;
	}
	/**
 	 * Acts like an order by on an SQL.
 	 * 
     * @param array $array The array we want to sort.
     * @param string $clause A string specifying how to sort the array similar to SQL ORDER BY clause.
     * @return array 
     */ 
    public static function sort($array, $clause) {
        $dirMap = array('desc' => 1, 'asc' => -1); 
		
		$clause = preg_replace('/\s+/', ' ', $clause);
        $keys = explode(',', $clause); 
       	
	    $retorno = 'return 0;';
		
        for ($i = count($keys) - 1; $i >= 0; $i--) {
            list($k, $dir) = explode(' ', trim($keys[$i]));
			
			if ($dir) {
				$t = $dirMap[strtolower($dir)];
			} else {
				$t = $dirMap['asc'];
			}
            $f = -1 * $t;
			
			if ($k == 'RAND()') {
            	$aStr = $bStr = 'rand()';
			} else {
				$k = str_replace('.', '->', $k);
				$aStr = '$a->' . $k;
            	$bStr = '$b->' . $k;
			}
			
			// Checagem de string para usar collate correto
			$attr = explode('->', $k);
			$valor = reset($array)->{$attr[0]};
			if ($k[1]) {
				$valor = $valor->{$attr[1]};
			}
			if (is_string($valor)) {
				 $fnBody = '$cmp = strcoll(' . $aStr . ', ' . $bStr . ');' .
				 	' if ($cmp == 0) {' .
						$retorno .
					' } ' .
					'return ($cmp < 0) ? ' . $t . ' : ' . $f . ';';
			} else {
	            $fnBody = 'if (' . $aStr . ' == ' . $bStr . ') {' .
						$retorno .
					' } ' .
					'return (' . $aStr . ' < ' . $bStr . ') ? ' . $t . ' : ' . $f . ';';
			}
			$retorno = &$fnBody;
        }
        if ($fnBody) {
            usort($array, create_function('$a,$b', $fnBody));        
        }
		return $array;
    }
	/**
	 * Acts like a LIMIT statement on SQL.
	 * 
	 * @param array &$array
	 * @param string $clause Similar to SQL LIMIT clause.
	 * @return array
	 */
	public static function slice($array, $clause) {
		if (strpos($clause, ',')) {
			$l = explode(',', $clause);
			$offset = trim($l[0]);
			$length = trim($l[1]);
		} else {
			$offset = 0;
			$length = trim($clause);
		}
		return array_slice($array, $offset, $length);
	}
}