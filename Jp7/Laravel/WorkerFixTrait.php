<?php

namespace Jp7\Laravel;

use Throwable;
use PDOException;
use App;
use Log;
use Illuminate\Database\QueryException;

trait WorkerFixTrait
{
  protected function preventWorkerLooping(Throwable $e)
  {
    if ($e instanceof PDOException || $e instanceof QueryException) {
        if (App::runningConsoleCommand('queue:work')) {
            Log::notice('Preventing queue:work from looping without database');
            sleep(10);
        }
    }
  }
}
