<?php

namespace Jp7\Laravel\Commands;

use Illuminate\Console\Command;
use Jp7\InterAdmin\Schema\DynamicLoader;
use Jp7\InterAdmin\Schema\RecordClassMap;
use Jp7\InterAdmin\Schema\TypeClassMap;

/** ⚠ ci-intranet's AppServiceProvider registers this, so it must resolve; nothing reads the file it writes. */
class GenerateClasses extends Command
{
    /** @var string */
    protected $signature = 'generate:classes {outputPath?}';

    /** @var string */
    protected $description = 'Generate class files for InterAdmin dynamic classes';

    public function handle(): void
    {
        spl_autoload_unregister([DynamicLoader::class, 'load']);

        $classesFile = $this->argument('outputPath') ?? self::getFilePath();
        file_exists($classesFile) && unlink($classesFile);

        $this->info('Starting to generate dynamic class files.');

        $classes = array_merge(
            array_unique(RecordClassMap::getInstance()->getClasses()),
            array_unique(TypeClassMap::getInstance()->getClasses())
        );
        $missingClasses = array_filter($classes, fn ($class) => !class_exists($class));
        sort($missingClasses);

        DynamicLoader::register();

        $contents = '';
        foreach ($missingClasses as $class) {
            $this->line($class);
            $contents .= DynamicLoader::getCode($class).PHP_EOL.'?>';
        }

        file_put_contents($classesFile, trim($contents));
        $this->info('Generated '.count($missingClasses).' classes!');
    }

    public static function getFilePath(): string
    {
        return base_path('bootstrap/cache/classes.php');
    }
}
