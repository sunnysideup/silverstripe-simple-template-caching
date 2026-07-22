<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use Page;
use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * Class \ControllerExtension.
 *
 * @property PageController|ControllerExtension $owner
 */
class ControllerExtension extends Extension
{
   private static array $hasMethodCache = [];

    public function onBeforeInit()
    {
        $controller = $this->getOwner();
        if (! $controller instanceof PageController) {
            return null;
        }
        // 1. static / env checks - cheapest
        if (Director::isTest()) {
            return $this->returnNoCache();
        }

        if (Versioned::get_reading_mode() !== 'Stage.Live') {
            return $this->returnNoCache();
        }

        // 2. security checks - logged-in users should not cache page
        if (Security::getCurrentUser()) {
            return $this->returnNoCache();
        }

        // 3. check the page
        if ($this->controllerHas($controller, 'canCachePage') && ! $controller->canCachePage()) {
            return $this->returnNoCache();
        }


        // . request checks - no DB, no session
        $request = $controller->getRequest();
        if (! $request->isGET()) {
            return $this->returnNoCache();
        }

        $getVars = $request->getVars();
        $hasGetVars = $getVars !== [];
        if ($hasGetVars && isset($getVars['flush'])) {
            return $this->returnNoCache();
        }

        $action = strtolower((string) $request->param('Action'));
        if ($action !== '' && $action !== '0' && $this->controllerHas($controller, 'cacheControlExcludedActions')) {
            $excludeActions = array_map('strtolower', (array) $controller->cacheControlExcludedActions());
            if (in_array($action, $excludeActions, true)) {
                return $this->returnNoCache();
            }
        }

        // AJAX requests are not cached by default unless the controller has cacheControlCanCacheAjax that returns true
        if ($request->isAjax()) {
            if (! $this->controllerHas($controller, 'cacheControlCanCacheAjax')) {
                return $this->returnNoCache();
            }
            if (! $controller->cacheControlCanCacheAjax()) {
                return $this->returnNoCache();
            }
        }

        // Opt-in for caching based on GET vars
        // If the controller has cacheControlCanCacheGetVars, let it make the decision
        if ($hasGetVars) {
            if ($this->controllerHas($controller, 'cacheControlCanCacheGetVars')) {
                if (!$controller->cacheControlCanCacheGetVars($getVars)) {
                    return $this->returnNoCache();
                }
            }
        }


        // 5. data record - potentially the most expensive
        $dataRecord = $controller->data();
        if (! $dataRecord instanceof Page) {
            return $this->returnNoCache();
        }
        if (! $dataRecord->PageCanBeCachedEntirely()) {
            return $this->returnNoCache();
        }

        $cacheTime = (int) $dataRecord->PageCanBeCachedEntirelyDuration();
        if ($cacheTime <= 0) {
            return $this->returnNoCache();
        }

        return HTTPCacheControlMiddleware::singleton()
            ->enableCache()
            ->setMaxAge($cacheTime)
            ->setStateDirective(HTTPCacheControlMiddleware::STATE_PUBLIC, 'must-revalidate', false)
            ->publicCache(true);
    }

    private function controllerHas(object $controller, string $method): bool
    {
        $key = $controller::class . '::' . $method;

        return self::$hasMethodCache[$key] ??= $controller->hasMethod($method);
    }

    protected function returnNoCache()
    {
        HTTPCacheControlMiddleware::singleton()
            ->disableCache()
        ;
        return null;
    }
}
