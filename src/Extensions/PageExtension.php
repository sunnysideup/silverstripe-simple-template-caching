<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use Sunnysideup\SimpleTemplateCaching\Api\FasterSiteConfig;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\PageExtension.
 *
 * @property Page|PageExtension $owner
 * @property bool $NoCachingAtAll
 * @property bool $NeverCachePublicly
 * @property int $PublicCacheDurationInSeconds
 */
class PageExtension extends Extension
{
    private static $db = [
        'NoCachingAtAll' => 'Boolean',
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
                    'NoCachingAtAll',
                    'No caching at all.
                    This remove partial template caching. This is not recommended as it can cause performance issues and it is not needed in most cases. '
                ),
                CheckboxField::create(
                    'NeverCachePublicly',
                    'Never cache this page.
                    This should be checked if this page can show different information for different users or different situations
                    or if it contains forms (some search forms may be exempted).'
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
                            Caching is ' . (FasterSiteConfig::current_site_config()->HasCaching ? '' : 'NOT') . ' allowed on for this site.<br />
                            The current value for the whole site is ' . FasterSiteConfig::current_site_config()->PublicCacheDurationInSeconds . ' seconds.<br />
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
        $sc = FasterSiteConfig::current_site_config();
        if (! $sc->HasCaching) {
            return false;
        }
        if (! Director::isLive()) {
            if ($owner->hasMethod('updateCacheControl')) {
                user_error('The updateCacheControl has been deprecated', E_USER_ERROR);
            }
            if ($owner->hasMethod('canCachePage')) {
                user_error('Please add the canCachePage method to your controller, not your page.', E_USER_ERROR);
            }
        }

        return true;
    }

    public function PageCanBeCachedEntirelyDuration(): int
    {
        return (int) (
            $this->getOwner()->PublicCacheDurationInSeconds ?:
            FasterSiteConfig::current_site_config()->PublicCacheDurationInSeconds
        );
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
