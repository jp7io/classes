<?php

namespace Jp7\Laravel\Commands;

use Illuminate\Console\Command;
use Jp7\Interadmin\DynamicLoader;
use Jp7\Interadmin\RecordClassMap;
use Jp7\Interadmin\TypeClassMap;

class GenerateClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:classes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate class files for InterAdmin dynamic classes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (DynamicLoader::isRegistered()) {
            spl_autoload_unregister([DynamicLoader::class, 'load']);
        }

        $classesFile = $this->getFilePath();
        file_exists($classesFile) && unlink($classesFile);

        $this->info('Starting to generate dynamic class files.');

        $contents = '';

        $classes = array_unique(RecordClassMap::getInstance()->getClasses());
        $classesTipos = array_unique(TypeClassMap::getInstance()->getClasses());

        $missingClasses = array_filter(array_merge($classes, $classesTipos), function ($class) {
            return !class_exists($class);
        });
        sort($missingClasses);

        DynamicLoader::register();

        foreach ($missingClasses as $class) {
            echo $class.PHP_EOL;
            $contents .= DynamicLoader::getCode($class, true) . PHP_EOL. '?>';
        }

        file_put_contents($classesFile, trim($contents));
        $this->info('Generated '.count($missingClasses).' classes!');
    }

    public static function getFilePath()
    {
        return base_path('bootstrap/cache/classes.php');
    }
}
