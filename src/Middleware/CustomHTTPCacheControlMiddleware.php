<?php

namespace Sunnysideup\SimpleTemplateCaching\Middleware;

use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Injector\Injectable;

class CustomHTTPCacheControlMiddleware extends HTTPCacheControlMiddleware
{
    use Injectable;
}
