<?php

namespace Jp7\Laravel\Commands;

use Illuminate\Console\Command;
use Jp7\Interadmin\DynamicLoader;
use Jp7\Interadmin\RecordClassMap;

class GenerateIde extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:ide';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate IDE helper files so PHP Storm can understand dynamic classes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        spl_autoload_unregister([DynamicLoader::class, 'load']);

        $helperFile = storage_path('ide_helper/classes.php');
        file_exists($helperFile) && unlink($helperFile);

        $this->info('Starting to generate IDE helper files.');

        $contents = '<?php throw new Exception("dont include this file, IDE helper only"); ?>'.PHP_EOL;

        $classes = array_unique(RecordClassMap::getInstance()->getClasses());
        $missingClasses = array_filter($classes, function ($class) {
            return !class_exists($class);
        });

        DynamicLoader::register();
        foreach ($missingClasses as $class) {
            echo '.';
            $contents .= DynamicLoader::getCode($class, true) . PHP_EOL. '?>'.PHP_EOL;
        }
        echo PHP_EOL;
        file_put_contents($helperFile, $contents);
        $this->info('Done!');
    }
}
