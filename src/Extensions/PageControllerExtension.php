<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use PageController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\PageControllerExtension.
 *
 * @property PageController|PageControllerExtension $owner
 */
class PageControllerExtension extends Extension
{
    private static bool $unique_cache_for_each_member = true;

    /**
     * make sure to set unique_cache_for_each_member to false
     * to use this.
     */
    private static bool $unique_cache_for_each_member_group_combo = false;

    /**
     * @var null|string
     */
    protected static $_cache_key_any_data_object_changes;

    /**
     * @var null|bool
     */
    private static $_can_cache_content;

    /**
     * @var string
     */
    private static string $_can_cache_content_string = '';

    /**
     * does the page have cache keys AKA can it be cached?
     */
    public function HasCacheKeys(): bool
    {
        $owner = $this->getOwner();
        if (null === self::$_can_cache_content) {
            self::$_can_cache_content_string = '';
            if ($owner->hasMethod('canCachePage')) {
                // if it can cache the page, then it the cache string will remain empty.
                self::$_can_cache_content_string .= $owner->canCachePage() ? '' : $this->getRandomKey();
            }

            //action
            $action = $owner->request->param('Action');
            if ($action) {
                self::$_can_cache_content_string .= 'UA' . $action;
            }

            // id
            $id = $owner->request->param('ID');
            if ($id) {
                self::$_can_cache_content_string .= 'UI' . $id;
            }

            // otherid
            $otherId = $owner->request->param('OtherID');
            if ($otherId) {
                self::$_can_cache_content_string .= 'UI' . $otherId;
            }

            //request vars
            $requestVars = $owner->request->requestVars();
            if ($requestVars) {
                foreach ($owner->request->requestVars() as $key => $item) {
                    if(! $item) {
                        $item = '';
                    }
                    self::$_can_cache_content_string .= serialize($key . '_'. serialize($item));
                }
            }

            //member
            $member = Security::getCurrentUser();
            if ($member && $member->exists()) {
                if (Config::inst()->get(self::class, 'unique_cache_for_each_member')) {
                    self::$_can_cache_content_string .= 'UM' . $member->ID;
                } elseif (Config::inst()->get(self::class, 'unique_cache_for_each_member_group_combo')) {
                    $groupIds = $member->Groups()->columnUnique();
                    sort($groupIds, SORT_NUMERIC);
                    self::$_can_cache_content_string .= 'UG' . implode(',', $groupIds);
                }
            }
            // crucial
            self::$_can_cache_content = ('' === trim(self::$_can_cache_content_string));
        }

        return self::$_can_cache_content;
    }

    public function HasCacheKeyHeader(): bool
    {
        return $this->HasCacheKeys();
    }

    public function HasCacheKeyMenu(): bool
    {
        return $this->HasCacheKeys();
    }

    public function HasCacheKeyContent(): bool
    {
        if ($this->getOwner()->NeverCachePublicly) {
            return false;
        }
        return $this->HasCacheKeys();
    }

    public function HasCacheKeyFooter(): bool
    {
        return $this->HasCacheKeys();
    }

    public function CacheKeyHeader(?bool $includePageId = false, ?bool $ignoreHasCacheKeys = false): string
    {
        return $this->CacheKeyGenerator('H', $includePageId, $ignoreHasCacheKeys);
    }

    public function CacheKeyMenu(?bool $includePageId = true, ?bool $ignoreHasCacheKeys = false): string
    {
        return $this->CacheKeyGenerator('M', $includePageId, $ignoreHasCacheKeys);
    }

    public function CacheKeyFooter(?bool $includePageId = false, ?bool $ignoreHasCacheKeys = false): string
    {
        return $this->CacheKeyGenerator('F', $includePageId, $ignoreHasCacheKeys);
    }

    public function CacheKeyContent(?bool $ignoreHasCacheKeys = false): string
    {
        $owner = $this->getOwner();
        if ($owner->NeverCachePublicly) {
            return $this->getRandomKey();
        }
        $cacheKey = $this->CacheKeyGenerator('C');
        if ($owner->hasMethod('CacheKeyContentCustom')) {
            $cacheKey .= '_' . $owner->CacheKeyContentCustom();
        }

        return $cacheKey;
    }

    public function CacheKeyGenerator(string $letter, ?bool $includePageId = true, ?bool $ignoreHasCacheKeys = false): string
    {
        $owner = $this->getOwner();
        if ($this->HasCacheKeys() || $ignoreHasCacheKeys) {
            $string = $letter . '_' . $this->getCanCacheContentString() . '_' . $this->cacheKeyAnyDataObjectChanges();

            if ($includePageId) {
                $string .= '_ID_' . $owner->ID;
            }
        } else {
            $string = 'NOT_CACHED__ID_' . $this->getRandomKey();
        }

        return $string;
    }

    /**
     * if the cache string is NOT empty then we cannot cache
     * as there are specific caching values that indicate the page can not be cached.
     */
    protected function canCacheCheck(): bool
    {
        // back to source
        return $this->HasCacheKeys();
    }

    protected function getRandomKey()
    {
        $uniqueId = uniqid('', true);

        // Combine it with some random data
        $randomData = bin2hex(random_bytes(16));

        // Create a SHA-256 hash
        return hash('sha256', $uniqueId . $randomData);
    }

    protected function getCanCacheContentString(): string
    {
        return self::$_can_cache_content_string;
    }

    protected function cacheKeyAnyDataObjectChanges(): string
    {
        if (null === self::$_cache_key_any_data_object_changes) {
            self::$_cache_key_any_data_object_changes = SimpleTemplateCachingSiteConfigExtension::site_cache_key();
        }

        return self::$_cache_key_any_data_object_changes;
    }
}
