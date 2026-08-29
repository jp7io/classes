<?php

namespace Jp7\Laravel;

use Illuminate\Routing\Router;
use Jp7\InterAdmin\RecordClassMap;

trait RouteConnectorTrait
{
    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        if (env('SKIP_ROUTES') || !RecordClassMap::getInstance()->getClasses()) {
            return;
        }
        // Clear InterAdmin route map - allows route:cache to work
        RouterFacade::clearCache();

        // Normal Laravel routing
        $this->mapApiRoutes();
        $this->mapWebRoutes();

        // Save new InterAdmin route map
        RouterFacade::saveCache();
    }
}
