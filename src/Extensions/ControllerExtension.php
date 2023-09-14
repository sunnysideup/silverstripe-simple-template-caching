<?php

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Extension;
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
            if ('Live' !== Versioned::get_stage()) {
                return;
            }
            if (empty($owner->dataRecord) || empty($owner->dataRecord->PublicCacheDurationInSeconds)) {
                return;
            }
            HTTPCacheControlMiddleware::singleton()
                ->enableCache()
                ->publicCache(true)
                ->setMaxAge($owner->dataRecord->PublicCacheDurationInSeconds)
            ;
        }
    }
}
