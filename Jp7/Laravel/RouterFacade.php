<?php

namespace Jp7\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Routing\Route|null getRouteByTypeId(int|string $type_id, string $action = 'index')
 * @method static \Jp7\InterAdmin\Type|null getTypeByRouteBasename(string $routeBasename)
 *
 * @see Router
 */
class RouterFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Router::class;
    }
}
