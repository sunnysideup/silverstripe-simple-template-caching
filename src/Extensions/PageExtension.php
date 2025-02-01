<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\LiteralField;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\PageExtension.
 *
 * @property Page|PageExtension $owner
 * @property bool $NeverCachePublicly
 * @property int $PublicCacheDurationInSeconds
 */
class PageExtension extends Extension
{
    private static $db = [
        'NeverCachePublicly' => 'Boolean',
        'PublicCacheDurationInSeconds' => 'Int',
    ];

    public function updateSettingsFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $fields->addFieldsToTab(
            'Root.Cache',
            [
                CheckboxField::create(
                    'NeverCachePublicly',
                    'Never cache this page.
                    This should be checked if this page can show different information for different users or different situations.'
                ),
            ]
        );
        if (! (bool) $owner->NeverCachePublicly) {
            $fields->addFieldsToTab(
                'Root.Cache',
                [
                    NumericField::create(
                        'PublicCacheDurationInSeconds',
                        'In seconds, how long can this be cached for?'
                    )
                        ->setDescription(
                            'Use with care!<br />
                            Leave empty or zero to use the default value for the site<br />
                            This should only be used on pages that should be the same for all users and that should be accessible publicly.<br />
                            You can also set this value <a href="/admin/settings#Root_Caching">for the whole site</a>.<br />
                            Caching is ' . (SiteConfig::current_site_config()->HasCaching ? '' : 'NOT') . ' allowed on for this site.<br />
                            The current value for the whole site is ' . SiteConfig::current_site_config()->PublicCacheDurationInSeconds . ' seconds.<br />
                            '
                        ),

                ]
            );
        }
    }

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {

        $owner = $this->getOwner();
        $sc = SiteConfig::current_site_config();
        if ($sc->HasCaching || $owner->PublicCacheDurationInSeconds) {
            if (! $owner->NeverCachePublicly) {
                $fields->push(
                    LiteralField::create(
                        'CacheInfo',
                        '<p class="message warning">This page can be cached for ' . $this->owner->PublicCacheDurationInSeconds . ' seconds.</p>'
                    )
                );
            }
        }
        return $fields;
    }
}
