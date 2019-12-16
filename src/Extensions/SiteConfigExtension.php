<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Forms\DatetimeField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;

class SiteConfigExtension extends DataExtension
{

    private static $db = [
        'CacheKeyLastEdited' => 'DBDatetime',
    ];

    public function updateCMSFields($fields)
    {
        $fields->addFieldsToTab(
            'Root.Main',
            [
                DatetimeField::create('CacheKeyLastEdited', 'Content Last Edited')->setRightTitle('The frontend template cache will be invalidated every time this value changes.')
            ]
        );
    }

    public static function site_cache_key()
    {
        $obj = SiteConfig::current_site_config();

        return strtotime($obj->CacheKeyLastEdited);
    }

    public static function update_cache_key()
    {
        DB::query("UPDATE \"SiteConfig\" SET \"CacheKeyLastEdited\" = '" . DBDatetime::now()->Rfc2822() . "';");
    }

}
