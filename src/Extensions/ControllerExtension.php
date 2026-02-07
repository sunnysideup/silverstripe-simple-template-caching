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
    public function onBeforeInit()
    {
        //make sure that caching is always https
        $controller = $this->getOwner();
        /** PageController|ControllerExtension $controller */
        if ($controller instanceof PageController) {
            $dataRecord = $controller->data();
            if (empty($dataRecord) || ! $dataRecord instanceof Page) {
                return $this->returnNoCache();
            }
            if ($dataRecord->PageCanBeCachedEntirely() !== true) {
                return $this->returnNoCache();
            }
            if (Security::getCurrentUser()) {
                return $this->returnNoCache();
            }
            if (Versioned::get_reading_mode() !== 'Stage.Live') {
                return $this->returnNoCache();
            }
            // avoid test sites being cached
            if (Director::isTest()) {
                return $this->returnNoCache();
            }

            // exclude special situations...
            $request = $controller->getRequest();
            $action = (string) $request->param('Action');
            if ($action !== '' && $action !== '0' && $controller->hasMethod('cacheControlExcludedActions')) {
                $excludeActions = (array) $controller->cacheControlExcludedActions();
                if ($excludeActions !== []) {
                    $action = strtolower($action);
                    if (in_array($action, $excludeActions)) {
                        return $this->returnNoCache();
                    }
                }
            }
            if ($request->isAjax()) {
                return $this->returnNoCache();
            }
            if ($request->getVar('flush')) {
                return $this->returnNoCache();
            }
            if ($request->postVars()) {
                return $this->returnNoCache();
            }
            if ($request->isGET() !== true) {
                return $this->returnNoCache();
            }
            if ($controller->hasMethod('canCachePage')) {
                $canCachePage = $controller->canCachePage();
                if ($canCachePage !== true) {
                    return $this->returnNoCache();
                }
            }
            $cacheTime = $dataRecord->PageCanBeCachedEntirelyDuration();
            if ($cacheTime > 0) {
                return HTTPCacheControlMiddleware::singleton()
                    ->enableCache()
                    ->setMaxAge($cacheTime)
                    ->setStateDirective(HTTPCacheControlMiddleware::STATE_PUBLIC, 'must-revalidate', false)
                    ->publicCache(true)
                ;
            }
        }

        return null;
    }

    protected function returnNoCache()
    {
        HTTPCacheControlMiddleware::singleton()
            ->disableCache()
        ;
        return null;
    }
}
