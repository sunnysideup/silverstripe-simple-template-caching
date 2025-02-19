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
                    This should be checked if this page can show different information for different users or different situations
                    or if it contains forms (some search forms may be excempted).'
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
        if ($owner->PageCanBeCachedEntirely()) {
            $fields->push(
                LiteralField::create(
                    'CacheInfo',
                    '<p class="message warning">Careful: this page can be cached publicly for up to ' . $owner->PageCanBeCachedEntirelyDuration() . ' seconds.</p>'
                )
            );
        }
        return $fields;
    }

    public function PageCanBeCachedEntirely(): bool
    {
        $owner = $this->getOwner();

        if ($owner->NeverCachePublicly) {
            return false;
        }
        $sc = SiteConfig::current_site_config();
        if (!$sc->HasCaching) {
            return false;
        }
        if ($owner->PageCanBeCachedEntirelyDuration() <= 0) {
            return false;
        }
        if ($owner->hasMethod('updateCacheControl')) {
            user_error('Please use canCachePage instead of updateCacheControl', E_USER_ERROR);
        }
        if ($owner->hasMethod('canCachePage')) {
            user_error('Please add the canCachePage method to your controller', E_USER_ERROR);
        }

        return true;
    }

    public function PageCanBeCachedEntirelyDuration(): int
    {
        return (int) (
            $this->getOwner()->PublicCacheDurationInSeconds ?:
            SiteConfig::current_site_config()->PublicCacheDurationInSeconds);
    }

    public function EditCacheSettingsLink(): string
    {
        return str_replace(
            'admin/pages/edit/show/',
            'admin/pages/settings/show/',
            $this->getOwner()->CMSEditLink()
        ) . '#Root_Cache';
    }
}
