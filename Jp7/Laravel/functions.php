<?php

if (!function_exists('interadmin_data')) {
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
    /**
     * Converts "1 hour" to 3600
     *
     * @param string
     * @return int
     */
    function time_to_int($time)
    {
        return strtotime($time) - time();
    }

    function to_slug($string, $separator = '-')
    {
        $string = str_replace('/', '-', $string);
        $string = str_replace('®', '', $string);
        $string = str_replace('&', 'e', $string);

        return str_slug($string, $separator);
    }

    /**
     * Called by @ia($record) blade extension
     *
     * @param $record|null
     */
    function interadmin_data($record = null)
    {
        if ($record instanceof \Jp7\Interadmin\RecordAbstract) {
            echo ' data-ia="'.$record->id.':'.$record->id_tipo.'"';
        } elseif ($record && getenv('APP_DEBUG')) {
            throw new InvalidArgumentException('@ia expects a Record, instance of '.get_class($record).' given');
        }
    }

    /**
     * @deprecated
     */
    function error_controller($action)
    {
        $request = Request::create('/error/'.$action, 'GET', []);
        $session = Request::getSession();
        if ($session) {
            $request->setSession($session);
        }
        return Route::dispatch($request);
    }


    function link_open($url, $attributes = [])
    {
        return substr(link_to($url, '', $attributes), 0, -4);
    }

    function link_close()
    {
        return '</a>';
    }

    function img_tag($img, $template = null, $options = [])
    {
        return ImgResize::tag($img, $template, $options);
    }

    /**
     * @deprecated
     */
    function _try($object)
    {
        return $object ?: new \Jp7\NullObject();
    }

    /**
     * @deprecated
     */
    function memoize(Closure $closure)
    {
        static $memoized = [];

        list(, $caller) = debug_backtrace(false, 2);

        $key = $caller['class'].':'.$caller['function'];

        foreach ($caller['args'] as $arg) {
            $key .= ",\0".(is_array($arg) ? serialize($arg) : (string) $arg);
        }

        $cache = &$memoized[$key];

        if (!isset($cache)) {
            $cache = call_user_func_array($closure, $caller['args']);
        }

        return $cache;
    }

    /**
     * @param $object
     * @param string $search
     */
    function dm($object, $search = '.*', ...$other)
    {
        $methods = [];
        $docs = [];
        if (is_object($object)) {
            $methods = get_class_methods($object);
            if (is_string($search)) {
                $methods = array_filter($methods, function ($a) use ($search) {
                    return preg_match('/'.$search.'/i', $a);
                });
            }
            foreach ($methods as $key => $method) {
                $args = [];
                $reflection = new ReflectionMethod($object, $method);
                foreach ($reflection->getParameters() as $param) {
                    $default = '';
                    if ($param->isOptional()) {
                        try {
                        $default = str_replace("\n", '', var_export($param->getDefaultValue(), true));
                        $default = str_replace('array ()', '[]', $default);
                        } catch (Throwable $t) {
                            $default = '__ERROR__';
                            Log::warning($t);
                        }
                    }
                    $args[] = ltrim($param->getType().' $').$param->name.($default ? ' = '.$default : '');
                }
                $docs[$key] = ($reflection->isStatic() ? 'static ': '').$method.'('.implode(', ',$args).')';
            }
            sort($docs);
        }
        if ($docs) {
            if (php_sapi_name() === "cli") {
                foreach ($docs as $doc) {
                    echo $doc . PHP_EOL;
                }
            } else {
                $html = highlight_string('<?php' . PHP_EOL . implode(PHP_EOL, $docs), true);
                foreach ($methods as $key => $method) {
                    $reflection = new ReflectionMethod($object, $method);
                    $isInherited = $reflection->class !== get_class($object);
                    $url = 'subl://open?url=file://' . $reflection->getFileName() . '&line=' . $reflection->getStartLine();
                    $link = '<a href="' . $url . '" style="text-decoration: none;color:' . ($isInherited ? ' #666;' : '#000;') .
                        '" title="' . htmlspecialchars($reflection->getDocComment()) . '"' .
                        ' onmouseover="this.querySelector(\'.expand\').style.display = \'inline\'"' .
                        ' onmouseout="this.querySelector(\'.expand\').style.display = \'none\'">' .
                        '<span class="expand" style="display:none;">' . $reflection->class . '::</span>' .
                        '\1</a>';
                    $html = preg_replace('/>(' . $method . ')</', '/>' . $link . '<', $html);
                }
                if ($object instanceof \Jp7\Interadmin\Record) {
                    $html .= '<br /><br /><b>' . get_class($object) . ' Relationships: </b> ' . implode(', ', array_keys($object->getType()->getRelationships()));
                }
                echo $html;
            }
            if (!$other) {
                dd($object);
            }
        }
        dd(...array_merge([$object, $search], $other));
    }

    /**
     * Same as str_replace but only if the string starts with $search.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    function replace_prefix($search, $replace, $subject)
    {
        if (mb_strpos($subject, $search) === 0) {
            return $replace.mb_substr($subject, mb_strlen($search));
        } else {
            return $subject;
        }
    }
    // Laravel 5 functions
    /**
     * @deprecated Dont extend the base Collection
     */
    function jp7_collect($arr = null)
    {
        return new \Jp7\Interadmin\Collection($arr);
    }

    function trans_route($name, $parameters = [], $absolute = true)
    {
        $locale = LaravelLocalization::getCurrentLocale();
        $prefix = $locale === LaravelLocalization::getDefaultLocale() ? '' : $locale.'.';
        return route($prefix.$name, $parameters, $absolute);
    }

    /**
     * Like file_get_contents() but with some default settings for URLs
     */
    function url_get_contents($url, array $contextOptions = [])
    {
        $contextOptions += [
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.76 Safari/537.36 JP7'
            ]
        ];
        if (ends_with(parse_url($url)['host'], '.dev')) {
            // Local development does not have SSL certificates
            $contextOptions += [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];
        }
        $context = stream_context_create($contextOptions);
        return file_get_contents($url, false, $context);
    }
}
if (!function_exists('interadmin_tipos_campos_encode')) {
    /**
     * Transforma array de campos em string separada por ; e {,} no formato do InterAdmin.
     *
     * @param array $campos
     *
     * @return string
     */
    function interadmin_tipos_campos_encode($campos)
    {
        $s = '';
        foreach ($campos as $value) {
            unset($value['ordem']);
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
// Laravel 5.2
if (!function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return base_path('resources/'.$path);
    }
}
