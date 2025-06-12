<?php

namespace Jp7\Laravel\Seeder;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use SplFileObject;
use DB;

class InteradminSeeder extends Seeder
{
    public function run()
    {
        $this->importSql(database_path('interadmin_tipos.sql'));
        $this->importSql(database_path('interadmin_records.sql'));
    }

    protected function importSql($filepath)
    {
        $file = new SplFileObject($filepath);
        while (!$file->eof()) {
            $line = $file->fgets();
            if (!Str::startsWith($line, 'INSERT INTO')) {
                if (Str::startsWith($line, '--') ||
                    Str::startsWith($line, '/*') ||
                    Str::startsWith($line, 'LOCK TABLES') ||
                    Str::startsWith($line, 'UNLOCK TABLES') ||
                    !trim($line)) {
                    continue;
                }
                throw new Exception('Invalid SQL: '.$line);
            }
            DB::unprepared($line);
        }
        $file = null;
    }
}
