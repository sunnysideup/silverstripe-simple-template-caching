<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use Page;
use PageController;
use SilverStripe\CMS\Controllers\ContentController;
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
        $controller = $this->getOwner();
        /** PageController|ControllerExtension $controller */
        if ($controller instanceof PageController) {
            $dataRecord = $controller->data();
            if ($dataRecord && $dataRecord->exists()) {
                if ($this->simpleCachingShouldThisPageBeCachedInFull($controller, $dataRecord)) {
                    return $this->simpleCachingReturnCache($dataRecord);
                }
            }

        }
        return $this->simpleCachingReturnNoCache();
    }

    protected function simpleCachingReturnCache(Page $dataRecord)
    {
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

    protected function simpleCachingReturnNoCache()
    {
        HTTPCacheControlMiddleware::singleton()
            ->disableCache()
        ;
        return null;
    }

    protected static $simpleCachingCanBeCachedAtAllCache = null;

    public function simpleCachingShouldThisPageBeCachedAtAll(ContentController $controller, Page $dataRecord): bool
    {
        if (self::$simpleCachingCanBeCachedAtAllCache !== null) {
            return self::$simpleCachingCanBeCachedAtAllCache;
        }
        self::$simpleCachingCanBeCachedAtAllCache = false;

        if (empty($dataRecord) || ! $dataRecord instanceof Page) {
            return false;
        }
        if (! $dataRecord->canView()) {
            return false;
        }

        if ($controller->hasMethod('canCachePage')) {
            $canCachePage = $controller->canCachePage();
            if (! $canCachePage) {
                return false;
            }
        }

        if (Versioned::get_reading_mode() !== 'Stage.Live') {
            return false;
        }

        // avoid test sites being cached
        if (Director::isTest()) {
            return false;
        }

        // exclude special situations...
        $request = $controller->getRequest();

        if ($request->isAjax()) {
            return false;
        }

        if ($request->getVar('flush')) {
            return false;
        }

        if ($request->postVars()) {
            return false;
        }

        if ($request->isGET() !== true) {
            return false;
        }

        $action = (string) $request->param('Action');
        if ($action !== '' && $action !== '0' && $controller->hasMethod('cacheControlExcludedActions')) {
            $excludeActions = (array) $controller->cacheControlExcludedActions();
            if ($excludeActions !== []) {
                $action = strtolower($action);
                if (in_array($action, $excludeActions)) {
                    return false;
                }
            }
        }
        self::$simpleCachingCanBeCachedAtAllCache = true;

        return true; // if none of the above conditions triggered, the page can be cached

    }

    public function simpleCachingShouldThisPageBeCachedInFull(ContentController$controller, Page $dataRecord): bool
    {
        //make sure that caching is always https

        if (! $dataRecord->PageCanBeCachedEntirely()) {
            return false;
        }

        if (Security::getCurrentUser()) {
            return false;
        }

        return $this->simpleCachingShouldThisPageBeCachedAtAll($controller, $dataRecord);
    }

}
