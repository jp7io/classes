<?php

namespace Jp7\InterAdmin\Schema;

use Illuminate\Container\Container;
use InterAdmin\Models\Type;

/** Declares with eval() the class a type binds when no file declares it. */
final class DynamicLoader
{
    /**
     * An E_COMPILE_ERROR no `catch` sees, where every other reserved word is a catchable ParseError.
     * Re-derive on a PHP upgrade: eval("class X {}") per candidate, one process each, keep exit 255.
     */
    private const RESERVED_CLASS_NAMES = [
        'bool', 'false', 'float', 'int', 'iterable', 'mixed', 'never', 'null', 'object',
        'parent', 'self', 'string', 'true', 'void',
    ];

    /** Where a generated class extends when the tenant names no default class, as `defaultRecordClass()` falls back. */
    private const MODELS_NAMESPACE = 'InterAdmin\\Models\\';

    /** @var array<string, bool> memoized by SHORT name, the only part this decides on. */
    private static array $declarable = [];

    /**
     * Whether PHP can declare a class by this name at all. `types.class` becomes a class name by a
     * literal `_` to `\` swap, so a type named "Include" asks for `class Include {}`: callers treat
     * such a binding as no binding, since nothing can make the name exist.
     */
    public static function isDeclarable(string $class): bool
    {
        $short = last(explode('\\', $class));

        if (!isset(self::$declarable[$short])) {
            try {
                token_get_all('<?php class '.$short.' {}', TOKEN_PARSE);
                self::$declarable[$short] = !in_array(strtolower($short), self::RESERVED_CLASS_NAMES, true);
            } catch (\ParseError $e) {
                self::$declarable[$short] = false;
            }
        }

        return self::$declarable[$short];
    }

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): bool
    {
        // The container, not the App facade: between two test applications the facade still
        // points at the flushed one, where resolving 'app' builds the facade class and fatals.
        if (!Container::getInstance()->bound('config') || !self::isDeclarable($class)) {
            return false;
        }
        $code = self::getCode($class);
        if ($code === null) {
            return false;
        }
        try {
            eval('?>'.$code);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage().' - Code: '.$code, 0, $e);
        }
        return true;
    }

    /** The code declaring $class, or null when no type binds it. */
    private static function getCode(string $class): ?string
    {
        // ⚠ Declared under the name PHP ASKED for, in the caller's own spelling: the map answers
        // both (getClassTypeId()), and declaring the other one leaves this autoload failing.
        if (RecordClassMap::getInstance()->getClassTypeId($class) !== false) {
            return self::buildClass($class, self::parentNamespace().'Record', '');
        }

        $typeId = TypeClassMap::getInstance()->getClassTypeId($class);
        if ($typeId !== false) {
            return self::buildClass($class, self::parentNamespace().'Type', "const TYPE_ID = {$typeId};");
        }

        return null;
    }

    /** The tenant's default Type names the namespace to extend into (`Ci\Type` gives `Ci\Record`). */
    public static function parentNamespace(): string
    {
        $default = Type::defaultClass();

        return $default !== null && defined($default.'::DEFAULT_NAMESPACE')
            ? constant($default.'::DEFAULT_NAMESPACE')
            : self::MODELS_NAMESPACE;
    }

    private static function buildClass(string $className, string $parentClass, string $classBody): string
    {
        $namespace = explode('\\', $className);
        $className = array_pop($namespace);
        $namespace = $namespace ? 'namespace '.implode('\\', $namespace).';' : '';

        return <<<STR
<?php
{$namespace}

class {$className} extends \\{$parentClass}
{
    {$classBody}
}
STR;
    }
}
