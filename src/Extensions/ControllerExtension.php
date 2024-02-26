<?php

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
            if($owner->param('Action')) {
                return;
            }
            if($owner->param('ID')) {
                return;
            }
            if($owner->request->isAjax()) {
                return;
            }
            if($owner->request->getVar('flush')) {
                return;
            }
            if($owner->request->requestVars()) {
                return;
            }
            $dataRecord = $owner->dataRecord;
            if (empty($dataRecord)) {
                return;
            }
            if($dataRecord->NeverCachePublicly) {
                HTTPCacheControlMiddleware::singleton()
                    ->disableCache()
                ;
                return;
            }
            if($dataRecord->PublicCacheDurationInSeconds === -1 || $dataRecord->PublicCacheDurationInSeconds === 0) {
                HTTPCacheControlMiddleware::singleton()
                    ->disableCache()
                ;
                return;
            }
            $sc = SiteConfig::current_site_config();
            if($sc->HasCaching) {
                $cacheTime = $dataRecord->PublicCacheDurationInSecond ?: $sc->PublicCacheDurationInSeconds;
                HTTPCacheControlMiddleware::singleton()
                    ->enableCache()
                    ->publicCache(true)
                    ->setMaxAge($cacheTime)
                ;
            }
        }
    }
}
