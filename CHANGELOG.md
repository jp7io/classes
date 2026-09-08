## 3.3
* Router's route-map cache is renamed into place, and clearCache() no longer writes.
  map() rewrites it on every request, so concurrent readers were getting a truncated
  or momentarily empty file. Measured over 24,000 reads while the app served: 476 bad
  reads before, 4 after, and those 4 only on a macOS Docker bind mount, where rename()
  is not atomic. 0 of 18,000 on a normal filesystem.
* PHP 8.5 floor, illuminate/support ^13.0
* Deprecate jp7_collect(), use collect() instead.
* Removed 23 classes, 5 global helpers and config/httpcache.php that no consumer calls. Checked
  against interadmin, interadmin-orm, interadmin-graphql, intermail, ci and ci-intranet, following
  transitive use and the two dynamic paths: Field\Factory resolving *Field from a column type, and
  Former's dispatcher repository 'Jp7\Former\Fields\'. Gone: Jp7\Cells\*, Jp7\CollectionUtil,
  Jp7\Flysystem\*, Jp7\Former\FormRequest, Jp7\Html\Table, Jp7\HttpCache\*, Jp7\Laravel\Controller
  and its two traits, Jp7\Laravel\FormRequest, Jp7\Laravel\InteradminControllerTrait,
  Jp7\Laravel\Middleware\*, Jp7\Laravel\Migrations\CreateInteradminTables,
  Jp7\Laravel\RedirectModelTrait, Jp7\Laravel\Seeder\InteradminSeeder,
  Jp7\Laravel\WhoopsHandlerTrait, Jp7\MethodLogger, Jp7\MobileDetect, Jp7\ReadOnlyArray, and
  time_to_int(), error_controller(), link_open(), link_close(), trans_route().
* Trimmed Jp7\Laravel down to what the apps invoke, 267 more lines. Jp7\Laravel\Cdn is gone:
  no app sets `cdn.url`, so its rewrite was the identity, and its one caller now says asset().
  Jp7\Laravel\BladeExtension is gone with it: no app sets `view.includes-with-underline`, so the
  @include rewrite never fired, and ci-intranet had already replaced the @ia() directive with its
  own. LogServiceProviderTrait keeps listenQueueEvents() and drops the other three, two of which
  called Log::getMonolog()/Log::useSyslog(), removed in Laravel 5.6. Routable keeps
  getChildrenMenu(); its slug/studly/controller-name cluster was reachable only from a commented
  method. Also dropped the interadmin_data() and memoize() helpers.
  ⚠ functions.php's guard named interadmin_data, so it now names human_size instead.
* Removed the dm() debug helper. It listed an object's method signatures as clickable subl://
  links before dd()ing it, and it could not run on this floor: it read $param->getType()->getName(),
  which only ReflectionNamedType has, so any union- or intersection-typed parameter fatalled with
  "Call to undefined method ReflectionUnionType::getName()" - 19 files in illuminate/support alone
  have such signatures. Tinker does the same job better: `ls -l --methods $obj`, plus `doc`.
* ⚠ Half of the above was reachable only from jp7io/abipe, which is retired. If a project outside
  the six listed ever comes back, it pinned classes at 498f1c31 (2025-06-02) and predates the
  InterAdmin namespace recase anyway, so it cannot follow this main regardless.
* Dropped the jp7io/classes-deprecated dev dependency. Nothing here used it at runtime; it only
  supported tests for Jp7_Interadmin_Upload, which no live project calls (interadmin replaced it
  with InterAdmin\Files\FileUrl). Those tests were replaced by ones for Jp7\Imgix\ImgResize.

## 3.2.2
* Small fixes for Laravel 5.7
* LogServiceProvider@renameSyslogApp is not needed anymore
* ~HttpCacheExtension abandoned~ (it's still being used, the steps below are a possible way to remove it if needed)
  * remove `blacklist` and `invalidate` from httpcache.php
  * use the ttl middleware from barryvdh on routes that were on blacklist: ->middleware('ttl:0')
  * use the DontCacheOldInput middleware if the return from old() forms should not be cached
  * Add the snippet below if AJAX should cache independently:
```php
// Cache AJAX requests independently
app()->singleton(\Symfony\Component\HttpKernel\HttpCache\Store::class, function ($app) {
    return new \Jp7\HttpCache\Store($app['http_cache.cache_dir']);
});
```

## 3.2.1
* Due to problems with Laravel 5.3, repeated routes will trigger an error
* ~Optional: Use stale cache if page could not be rendered (see Jp7/HttpCache/HttpCacheExtension.php)~ (Found out later that it's already part of the Symfony HttpCache behavior, it's just not as long, dropped because it was too magic)

## 3.2
* Split into 3 packages: classes, classes-deprecated and interadmin-orm
* Removed automatic namespace on Router::group()
* Custom 500 page by default on Laravel apps (see WhoopsHandlerTrait.php)

## 3.1
* Fixed bugs after the merge of the ORM
* Performance fixes for aliases
* Add type of password field using Laravel Hash
* Add commands to generate seeds from InterAdmin database
* Log that the Laravel queue is running
* Fixes for HTTPS
* Use .env values for e-mails, DB and storage
* Move getUrl() out of the ORM

## 3.0
* Merged both ORMs: InterAdmin (original branch) and Jp7/InterAdmin/Record (laravel branch)

### Changes to projects which used InterAdmin/InterAdminTipo:
 * Removed methods deprecated on 2.1.1 (like getInterAdmins)
 * `InterAdmin::__construct` receives an array now
 * Calling select_* without alias won't bring objects: ->relationFromColumn() can be used if the alias is not known
 * ->attributes is not public anymore - Use ->getAttributes()
 * ->getCampoTipo() can only be overwritten on a Type
 * Replace setFieldsValues() -> updateAttributes()
 * Fields are eager and lazy loaded, ->getFieldsValues() and getByAlias() are not needed anymore
 * Default aliases are generated in snake_case now (if empty). To use old aliases you must manually define them.
 * ORM depends on new configuration: /config/interadmin.php and /resources/lang/pt-BR/interadmin.php

### Changes to projects which used Jp7/InterAdmin/Record
 * Attributes are stored internally without alias / use getAliasedAttributes() if needed

## 2.7
* Branch laravel was reintegrated to master
* Dependencies removed from classes, each client must require them as needed:
 * "zendframework/zendframework1": "1.12.0"
 * "phpoffice/phpexcel": "~1.8.1"
 * "werkint/jsmin": "~1.0.0”
* Replace Jp7_InterAdmin by Jp7_Interadmin
* Replace InterAdmin_ by Interadmin_
* Replace startsWith($needle, $haystack) by Str::startsWith($haystack, $needle)
* Replace endsWith($needle, $haystack) by Str::endsWith($haystack, $needle)
* Replace jp7_replace_beginning() by replace_prefix()
* Main table is interadmin_CLIENT_registros, it was interadmin_CLIENT
 * To prevent problems with legacy projects a VIEW named interadmin_CLIENT_registros was created

### Changes to projects which used branch laravel:
 * InterSite -> Jp7\Intersite
 * InterAdmin -> Jp7\InterAdmin\Record
 * InterAdminAbstract -> Jp7\InterAdmin\RecordAbstract
 * InterAdminTipo -> Jp7\InterAdmin\Type
 * InterAdminArquivo -> Jp7\InterAdmin\FileRecord
 * InterAdminArquivoBanco -> Jp7\InterAdmin\FileDatabase
 * InterAdminLog -> Jp7\InterAdmin\Log
 * InterAdminField -> Jp7\InterAdmin\FieldUtil
 * InterAdminFieldFile -> Jp7\InterAdmin\FileField
 * Change config suffix in resources/lang/en/interadmin.php from \_en to en\_

## 2.6
* ...

## 2.1.1
### Deprecate the following methods, replaced by new names:
* getFirstInterAdmin -> findFirst
* getInterAdminById -> findById
* getInterAdminByIdString -> findByIdString
* getInterAdmins -> find
* getInterAdminsByTags -> findByTags
* getInterAdminsCount -> count
