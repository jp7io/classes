<?php

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2009 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Inflector, used to generate names for classes, tables and others.
 */
class Jp7_Inflector
{
    private static $plural_cache = [];

    /**
     * Regular expressions for plurals, each item is composed of $pattern => $replacement.
     * Default inflections are in pt-BR.
     *
     * @var
     */
    public static $plural_inflections = [
        '/m$/i' => 'ns', // Bem -> Bens
        '/([r|z])$/i' => '\1es', // Paz -> Pazes, Bar -> Bares
        '/([i])l$/i' => '\1s', // Barril -> Barris
        '/([a|e|o|u])l$/i' => '\1is', // Sal -> Sais
        '/^(m)[e|ê]s$/iu' => '\1eses', // Mês -> Meses
        '/^(c|p|escriv|alem|capel|capit)ão$/iu' => '\1ães', // Cão -> Cães
        '/^(m|irm|pag|gr|ch|benç|orf|sót|órg)ão$/iu' => '\1ãos', // Mão -> Mãos
        '/ão$/iu' => '\1ões', // Reunião -> Reuniões
        '/([^s])$/' => '\1s', // Plural padrão
    ];

    /**
     * Returns the plural form of the word in the string.
     *
     * @param string    $word
     * @param int|array $itens The word will only be pluralized $itens > 1 OR count($itens) > 1.
     *
     * @return string
     */
    public static function plural($word, $itens = null)
    {
        $prefix = '';
        if (is_null($itens)) {
            $itens = 0;
        } else {
            if (is_array($itens)) {
                $itens = count($itens);
            }
            $prefix = $itens.' ';
        }

        if (is_numeric($itens) && $itens != 1) {
            if (!isset(self::$plural_cache[$word])) {
                while (true) {
                    foreach (self::$plural_inflections as $pattern => $replacement) {
                        if (preg_match($pattern, $word)) {
                            self::$plural_cache[$word] = preg_replace($pattern, $replacement, $word);
                            break 2;
                        }
                    }
                    self::$plural_cache[$word] = $word;
                    break;
                }
            }
            $word = self::$plural_cache[$word];
        }

        return $prefix.$word;
    }

    /**
     * Converts from CamelCase to underscore_case.
     *
     * @param object $camelCasedWord
     *
     * @return string
     */
    public static function underscore($camelCasedWord)
    {
        return mb_strtolower(trim(preg_replace('/([A-Z])/', '_\1', $camelCasedWord), '_'));
    }

    /**
     * Replaces underscores with dashes in the string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function dasherize($string)
    {
        return str_replace('_', '-', $string);
    }

    /**
     * Combination of plural and underscore, ex: 'BlueSuedShoe' => 'blue_sued_shoes'.
     *
     * @param object $camelCasedWord Such as BlueSuedShoe.
     *
     * @return string Underscored word in plural, such as blue_sued_shoes.
     */
    public static function tableize($camelCasedWord)
    {
        return self::plural(self::underscore($camelCasedWord));
    }

    /**
     * Convert a phrase from the lower case and underscored form to the camel case form.
     *
     * @param string $lower_case_and_underscored_word Phrase to convert
     *
     * @return string Camel case form of the phrase: LowerCaseAndUnderscoredWord
     */
    public static function camelize($lower_case_and_underscored_word, $uppercase_first_letter = true)
    {
        $lower_case_and_underscored_word = toId($lower_case_and_underscored_word, false, '_');
        $result = str_replace(' ', '', ucwords(str_replace('_', ' ', $lower_case_and_underscored_word)));
        if (!$uppercase_first_letter) {
            $result = lcfirst($result);
        }

        return $result;
    }
}
