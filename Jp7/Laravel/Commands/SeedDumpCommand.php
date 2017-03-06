<?php

namespace Jp7\Laravel\Commands;

use Illuminate\Console\Command;
use Jp7\Interadmin\Type;
use Jp7\Interadmin\Query;
use DB;

class SeedDumpCommand extends Command
{
    protected $signature = 'seed:dump';
    protected $description = 'Dumps a seed file.';
    /**
     * Database config
     *
     * @var array
     */
    protected $config;
    /**
     * IDs of types to be dumped
     *
     * @var array
     */
    protected $typeIds = [];

    public function __construct()
    {
        parent::__construct();
        $this->config = config('database.connections.mysql');
    }

    public function handle()
    {
        $this->dumpSchema();
        $this->dumpTipos();
        $this->dumpRecords();
    }

    protected function dumpSchema()
    {
        $tables = $this->getTables();
        # Export schema
        $options = " --tables ".implode(' ', $tables).
            " --no-data".
            " --skip-add-drop-table";
        $this->mysqldump($options, 'database/interadmin_schema.sql');
    }

    protected function dumpTipos()
    {
        $options = " ".$this->config['prefix']."tipos".
            " --skip-extended-insert".
            " --no-create-info";
        $this->mysqldump($options, 'database/interadmin_tipos.sql');
    }

    protected function dumpRecords()
    {
        $tables = $this->getRecordsTables();

        $options = " --tables ".implode(' ', $tables).
            " --where=\"id_tipo IN (".implode(',', $this->typeIds).")\"".
            " --skip-extended-insert".
            " --no-create-info";

        $this->mysqldump($options, 'database/interadmin_records.sql');
    }

    protected function getRecordsTables()
    {
        $tables = [];
        foreach ($this->typeIds as $typeId) {
            $type = Type::getInstance($typeId);
            foreach ($type->getRelationships() as $relation => $data) {
                $query = $data['query'];
                if ($query instanceof Query && !in_array($query->type()->id_tipo, $this->typeIds)) {
                    $this->warn('Type '.$typeId.' might require '.$query->type()->id_tipo.' - '.$relation);
                }
            }
            $tables[] = $type->getInterAdminsTableName();
        }
        return array_unique($tables);
    }

    /**
     * All existing tables
     *
     * @return array
     */
    protected function getTables()
    {
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        return array_filter($tables, function ($table) {
            return !in_array($table, $this->getIgnoredTables()) && starts_with($table, $this->config['prefix']);
        });
    }

    /**
     * Ignored tables. e.g: tables that are created by Laravel migrations
     *
     * @return array
     */
    protected function getIgnoredTables()
    {
        return [
            $this->config['prefix'].'migrations',
            $this->config['prefix'].'jobs',
            $this->config['prefix'].'failed_jobs',
            $this->config['prefix'].'password_resets',
        ];
    }

    protected function mysqldump($options, $output)
    {
        $command = "mysqldump -h ".$this->config['host'].
            " -u ".$this->config['username'].
            " -p".$this->config['password'].
            " ".$this->config['database'].
            $options." > ".$output;
        if ($this->option('verbose')) {
            $this->comment($command);
        }
        exec($command, $_, $error_code);

        if ($error_code) {
            $this->error('Mysqldump failed');
        } else {
            $this->info('Dumped: '.$output);
        }
    }
}
