<?php

namespace Sunnysideup\SimpleTemplateCaching\Api;

use SilverStripe\SiteConfig\SiteConfig;
class FasterSiteConfig
{
    protected static ?SiteConfig $siteConfig = null;

    public static function current_site_config(): SiteConfig
    {
        return self::$siteConfig ??= SiteConfig::current_site_config();
    }

}
