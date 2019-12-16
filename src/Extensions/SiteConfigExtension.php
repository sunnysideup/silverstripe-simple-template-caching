<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

class SiteConfigExtension extends DataExtension
{

    private static $db = [
        'CacheKeyLastEdited' => 'DateTime', //like LastEdited
    ];

    public function updateCMSFields($fields)
    {

    }

    public static function site_cache_key()
    {
        $obj = SiteConfig::current_site_config();

        return strtotime($obj->CacheKeyLastEdited);
    }

    public static function update_ache_key()
    {
        DB::query('UPDATE "SiteConfig" SET "CacheKeyLastEdited" = NOW() ;'); //see LastEdited in DataObject for best approach.
    }

}
