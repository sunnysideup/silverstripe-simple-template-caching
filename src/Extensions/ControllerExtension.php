<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use Page;
use PageController;
use PhpParser\Node\Scalar\MagicConst\Dir;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
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
            if (empty($dataRecord)) {
                return $this->returnNoCache();
            }
            if (!$dataRecord instanceof Page) {
                return $this->returnNoCache();
            }
            if ( $dataRecord->PageCanBeCachedEntirely() !== true) {
                return $this->returnNoCache();
            }
            if (Security::getCurrentUser()) {
                return $this->returnNoCache();
            }
            if (Versioned::get_reading_mode() !== 'Stage.Live') {
                return $this->returnNoCache();
            }

            // exclude special situations...
            $request = $controller->getRequest();
            if ($controller->hasMethod('cacheControlExcludedActions')) {
                $excludeActions = $controller->cacheControlExcludedActions();
                $action = strtolower($request->param('Action'));
                if ($action) {
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
            if ($request->requestVars()) {
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
