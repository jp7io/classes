<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2009 JP7 (http://jp7.com.br)
 * @category Jp7
 * @package Jp7_Inflector
 */
 
/**
 * Inflector, used to generate names for classes, tables and others.
 *
 * @package Jp7_Inflector
 */
class Jp7_Inflector {
	
	/**
	 * Regular expressions for plurals, each item is composed of $pattern => $replacement.
	 * Default inflections are in pt-BR.
	 * @var
	 */	
	public static $plural_inflections = array(
		'/(m)$/' => 'ns',
		'/([r|z])$/' => '\1es', 
		'/([i])l$/' => '\1s',
		'/([a|e|o|u])l$/' => '\1is',
		'/^(m)[e|�]s$/i' => '\1eses',
		'/([^s])$/' => '\1s'
	);
	
	/**
	 * Returns the plural form of the word in the string.
	 * 
	 * @param string $word
	 * @param int|array $itens The word will only be pluralized $itens > 1 OR count($itens) > 1.
	 * @return string
	 */
	public static function plural ($word, $itens = 2) {
		if (is_numeric($itens) && $itens > 1 || is_array($itens) && count($itens) > 1) {
			foreach (self::$plural_inflections as $pattern => $replacement) {
				if (preg_match($pattern, $word)) {
					$word = preg_replace($pattern, $replacement, $word);
					break;
				}
			}
		}
		return $word;
	}
	
	/**
	 * Converts from CamelCase to underscore_case.
	 * 
	 * @param object $camelCasedWord
	 * @return string
	 */
	public static function underscore ($camelCasedWord) {
		return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2', $camelCasedWord));
	}
	
	/**
	 * Combination of plural and underscore, ex: 'BlueSuedShoe' => 'blue_sued_shoes'
	 * 
	 * @param object $camelCasedWord Such as BlueSuedShoe.
	 * @return string Underscored word in plural, such as blue_sued_shoes.
	 */
	public static function tableize($camelCasedWord) {
		return self::plural(self::underscore($camelCasedWord));
	}
	
} 
