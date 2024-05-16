<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;

/**
 * Class \ControllerExtension.
 *
 * @property ControllerExtension $owner
 */
class ControllerExtension extends Extension
{
    public function onBeforeInit()
    {
        if(Security::getCurrentUser()) {
            return;
        }
        if (Versioned::LIVE !== Versioned::get_stage()) {
            return;
        }
        /** PageController|ControllerExtension $owner */
        $owner = $this->getOwner();
        if ($owner instanceof ContentController) {
            $extend = $owner->extend('updateCacheControl');
            if($extend) {
                return;
            }
            $sc = SiteConfig::current_site_config();
            if(! $sc->HasCaching) {
                return;
            }
            $request = $owner->getRequest();
            if($request->param('Action')) {
                return;
            }
            if($request->param('ID')) {
                return;
            }
            if($request->isAjax()) {
                return;
            }
            if($request->getVar('flush')) {
                return;
            }
            if($request->requestVars()) {
                return;
            }
            $dataRecord = $owner->data();
            if (empty($dataRecord)) {
                return;
            }
            if($dataRecord->NeverCachePublicly) {
                HTTPCacheControlMiddleware::singleton()
                ->disableCache()
                ;
                return;
            }
            $cacheTime = (int) ($dataRecord->PublicCacheDurationInSeconds ?: $sc->PublicCacheDurationInSeconds);
            if($cacheTime > 0) {
                return HTTPCacheControlMiddleware::singleton()
                    ->enableCache()
                    ->setMaxAge($cacheTime)
                    ->publicCache(true)
                ;
            }
        }
    }
}
