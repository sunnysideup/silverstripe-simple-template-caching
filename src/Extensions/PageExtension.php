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
                CheckboxField::create(
                    'NeverCachePublicly',
                    'Never cache this page.
                    This should be checked if the page can be different for different users.'
                ),
            ]
        );
        if((bool) $this->getOwner()->NeverCachePublicly !== true) {
            $fields->addFieldsToTab(
                'Root.Cache',
                [
                    NumericField::create(
                        'PublicCacheDurationInSeconds',
                        'In seconds, how long can this be cached for?'
                    )
                        ->setDescription(
                            'Use with care!<br /><br />
                            Leave empty or zero to use the default value for the site<br /><br />
                            This should only be used on pages that should be the same for all users and that should be accessible publicly.<br /><br />
                            You can also set this value <a href="/admin/settings#Root_Caching">for the whole site</a>.<br /><br />
                            The current value for the whole site is ' . SiteConfig::current_site_config()->PublicCacheDurationInSeconds . ' seconds.<br /><br />
                            Caching is ' . (SiteConfig::current_site_config()->HasCaching ? '' : 'NOT') . ' turned on for this site.
                            '
                        ),

                ]
            );
        }
    }
}
