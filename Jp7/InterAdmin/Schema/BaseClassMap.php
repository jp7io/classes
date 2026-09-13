<?php

namespace Jp7\InterAdmin\Schema;

use Cache;
use DB;

/** Which class `types.class` or `types.class_type` binds each type to, cached under TypeCache's tag. */
abstract class BaseClassMap
{
    // ⚠ $instance, CACHE_KEY and CLASS_ATTRIBUTE are deliberately NOT declared on the base: undefined
    // here makes a subclass that forgets one a fatal, rather than a map cached under '' and a
    // singleton shared with its sibling.
    protected $classes;

    final protected function __construct()
    {
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        static::$instance = static::$instance ?: new static;
        return static::$instance;
    }

    protected static function prepareMap($attr): array
    {
        $arr = [];
        $roots = []; // keep track of duplicated classes
        try {
            $types = DB::table('types')
                ->select($attr, 'type_id', 'inherited')
                ->where($attr, '<>', '')
                ->whereNull('deleted_at')
                ->where('visible', true)
                ->orderByRaw("inherited LIKE '%".$attr."%'")
                ->get();

            foreach ($types as $type) {
                $class = $type->$attr;
                if (config('interadmin.psr-4')) {
                    $class = str_replace('_', '\\', $class);
                }
                if (!$type->inherited || !in_array($attr, explode(',', $type->inherited))) {
                    if (array_key_exists($class, $roots) && config('interadmin.namespace')) {
                        throw new \UnexpectedValueException('Duplicate entry for class: '.$class.' in type_id: '.$type->type_id);
                    }
                    $roots[$class] = true;
                }
                $arr[$type->type_id] = $class;
            }
        } catch (\PDOException $e) {
            $message = "InterAdmin database not connected";
            if (!\App::runningInConsole()) {
                throw new DatabaseNotConnectedException($message, 0, $e);
            }
            // Exception is not thrown because artisan commands would stop working
            \Log::error($e);
            echo '[Skipped ClassMap] '.$message.PHP_EOL;
        }
        return $arr;
    }

    public function clearCache(): void
    {
        Cache::tag(TypeCache::TAG)->forget(static::CACHE_KEY);
        static::getInstance()->classes = null;
    }

    public function getClasses()
    {
        if ($this->classes === null) {
            $cache = Cache::tag(TypeCache::TAG);
            $this->classes = $cache->get(static::CACHE_KEY);
            if (!$this->classes) {
                $this->classes = static::prepareMap(static::CLASS_ATTRIBUTE);
                // An empty map is not cached, being also what an unreadable database answers.
                if ($this->classes) {
                    $cache->put(static::CACHE_KEY, $this->classes, TypeCache::TTL);
                }
            }
        }
        return $this->classes;
    }

    /**
     * @param  string $class
     * @return int   type_id
     */
    public function getClassTypeId($class): int|string|false
    {
        $type_id = array_search($class, $this->getClasses());
        if ($type_id === false && strpos($class, '\\') !== false) {
            // A psr-4=false tenant binds underscore names while its class_alias() bridge makes
            // static::class report the namespaced one (Ci\Loja for Ci_Loja), so ask both ways.
            $type_id = array_search(str_replace('\\', '_', $class), $this->getClasses());
        }
        return $type_id;
    }

    /**
     * @param  int $type_id
     * @return string Class
     */
    public function getClass($type_id)
    {
        $classes = $this->getClasses();
        return isset($classes[$type_id]) ? $classes[$type_id] : null;
    }
}
