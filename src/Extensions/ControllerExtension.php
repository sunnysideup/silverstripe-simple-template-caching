<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

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
        if (Security::getCurrentUser()) {
            return null;
        }
        if (Versioned::LIVE !== Versioned::get_stage()) {
            return null;
        }
        //make sure that caching is always https
        $owner = $this->getOwner();
        if(Director::isLive()) {
            $owner->response->addHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        /** PageController|ControllerExtension $owner */
        if ($owner instanceof ContentController) {
            $extend = $owner->extend('updateCacheControl');
            if ($extend) {
                return null;
            }
            $sc = SiteConfig::current_site_config();
            if (! $sc->HasCaching) {
                return null;
            }
            $request = $owner->getRequest();
            if ($request->param('Action')) {
                return null;
            }
            if ($request->param('ID')) {
                return null;
            }
            if ($request->isAjax()) {
                return null;
            }
            if ($request->getVar('flush')) {
                return null;
            }
            if ($request->requestVars()) {
                return null;
            }
            $dataRecord = $owner->data();
            if (empty($dataRecord)) {
                return null;
            }
            if ($dataRecord->NeverCachePublicly) {
                HTTPCacheControlMiddleware::singleton()
                    ->disableCache()
                ;
                return null;
            }
            $cacheTime = (int) ($dataRecord->PublicCacheDurationInSeconds ?: $sc->PublicCacheDurationInSeconds);
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
}
