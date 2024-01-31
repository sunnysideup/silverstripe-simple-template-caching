<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\PageExtension.
 *
 * @property Page|PageExtension $owner
 * @property bool $NeverCachePublicly
 * @property bool $PublicCacheDurationInSeconds
 */
class PageExtension extends DataExtension
{
    private static $db = [
        'NeverCachePublicly' => 'Boolean',
        'PublicCacheDurationInSeconds' => 'Int',
    ];

    public function updateSettingsFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Cache',
            [
                CheckboxField::create('NeverCachePublicly', 'Never cache this page publicly (so that all users see the same page)'),
                NumericField::create(
                    'PublicCacheDurationInSeconds',
                    'Number of caching seconds for public users (0 = no caching)'
                )
                    ->setDescription(
                        '
                        Use with care!
                        This should only be used on pages that should be the same for all users and that should be accessible publicly.
                        You can also set this value <a href="/admin/settings#Root_Caching">for the whole site</a> .
                        The current value is ' . SiteConfig::current_site_config()->PublicCacheDurationInSeconds . ' seconds.'
                    ),

            ]
        );
    }
}
