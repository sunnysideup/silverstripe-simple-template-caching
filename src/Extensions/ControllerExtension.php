<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

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
 * @property PageController|ControllerExtension $controller
 */
class ControllerExtension extends Extension
{
    public function onBeforeInit()
    {
        //make sure that caching is always https
        $controller = $this->getOwner();
        if (Director::isLive()) {
            $controller->response->addHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        /** PageController|ControllerExtension $controller */
        if ($controller instanceof PageController) {
            $dataRecord = $controller->data();
            if (empty($dataRecord)) {
                return $this->returnNoCache();
            }
            if ($dataRecord->PageCanBeCached() !== true) {
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
            if ($request->isPOST()) {
                return $this->returnNoCache();
            }

            $cacheTime = $dataRecord->CacheDurationInSeconds();
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
