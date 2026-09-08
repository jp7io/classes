<?php

use Illuminate\Support\Str;

if (!function_exists('human_size')) {
    /**
     * Converts 1024 to 1kB
     *
     * @param int $bytes
     * @param int $decimals
     * @return string   Human readable size
     */
    function human_size($bytes, $decimals = 2)
    {
        $size = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).' '.@$size[$factor];
    }

    function to_slug($string, $separator = '-')
    {
        // Callers pass unset record/type fields straight in, and those are NULL rather than
        // ''. Since PHP 8.1 that is a deprecation on every str_* below, not a silent cast.
        $string = (string) $string;

        // ⚠ NOT Str::slug()'s $dictionary. That pads its replacement with the separator, so 'M&M'
        // becomes 'm-e-m' where this gives 'mem', and these values are stored in id_slug.
        $string = str_replace('/', '-', $string);
        $string = str_replace('®', '', $string);
        $string = str_replace('&', 'e', $string);

        return Str::slug($string, $separator);
    }

    function img_tag($img, $template = null, $options = [])
    {
        return ImgResize::tag($img, $template, $options);
    }

    /**
     * Like file_get_contents() but with some default settings for URLs
     */
    function url_get_contents($url, array $contextOptions = ['http' => []])
    {
        // Using Safari, not Chrome to avoid downloading WEBP
        $safariUserAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0 Safari/605.1.15';
        $contextOptions['http'] += [ // merge with passed http options
            'timeout' => 15,
            // JP7 in user agent for whitelisting in Firewall / Bot Blocker
            'user_agent' => $safariUserAgent.' JP7'
        ];
        $context = stream_context_create($contextOptions);
        return file_get_contents($url, false, $context);
    }
}
if (!function_exists('interadmin_type_fields_encode')) {
    /**
     * Packs a field-definition array back into the type's `fields` blob: '{,}' between a
     * field's attributes, '{;}' after each field.
     *
     * @param array $fieldDefinitions
     *
     * @return string
     */
    function interadmin_type_fields_encode($fieldDefinitions)
    {
        $s = '';
        foreach ($fieldDefinitions as $value) {
            unset($value['order']);
            $s .= implode('{,}', $value).'{;}';
        }
        return $s;
    }
}
if (!function_exists('toId')) {
    /**
     * Takes off diacritics and empty spaces from a string, if $tofile is <tt>FALSE</tt> (default) the case is changed to lowercase.
     *
     * @param string $S String to be formatted.
     * @param bool $tofile Sets whether it will be used for a filename or not, <tt>FALSE</tt> is the default value.
     * @param string $separador Separator used to replace empty spaces.
     *
     * @return string Formatted string.
     *
     * @version (2006/01/18)
     */
    function toId($string, $tofile = false, $separador = '')
    {
        // Same as to_slug(): an unset field arrives as NULL, which every preg_* below
        // deprecates rather than casting.
        $string = (string) $string;

        // Check if there are diacritics before replacing them
        if (preg_match('/[^a-zA-Z0-9-\/ _.,]/', $string)) {
            $string = preg_replace('/[áàãâäÁÀÃÂÄª]/u', 'a', $string);
            $string = preg_replace('/[éèêëÉÈÊË&]/u', 'e', $string);
            $string = preg_replace('/[íìîïÍÌÎÏ]/u', 'i', $string);
            $string = preg_replace('/[óòõôöÓÒÕÔÖº]/u', 'o', $string);
            $string = preg_replace('/[úùûüÚÙÛÜ]/u', 'u', $string);
            $string = preg_replace('/[çÇ]/u', 'c', $string);
            $string = preg_replace('/[ñÑ]/u', 'n', $string);
        }
        if ($tofile) {
            $string = preg_replace('/[^a-zA-Z0-9_]/u', '_', $string);
        } else {
            $string = preg_replace('/[^a-zA-Z0-9_]+/u', $separador, $string);
            $string = trim(mb_strtolower($string), $separador);
        }
        if ($separador) {
            $string = str_replace('_', $separador, $string);
        } else {
            $string = preg_replace('/[\/-]/u', '_', $string);
        }
        return $string;
    }
}
