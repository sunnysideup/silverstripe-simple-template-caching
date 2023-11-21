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
        $owner = $this->getOwner();
        if ($owner instanceof ContentController) {
            if (empty($owner->dataRecord)) {
                return;
            }
            if(Security::getCurrentUser()) {
                return;
            }
            if (Versioned::LIVE !== Versioned::get_stage()) {
                return;
            }
            $sc = SiteConfig::current_site_config();
            if($sc->HasCaching) {
                $cacheTime = $sc->PublicCacheDurationInSeconds ?: $owner->dataRecord->PublicCacheDurationInSeconds;
                HTTPCacheControlMiddleware::singleton()
                    ->enableCache()
                    ->publicCache(true)
                    ->setMaxAge($cacheTime)
                ;
            }
        }
    }
}
