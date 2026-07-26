<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use PageController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

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

    private static string $_can_cache_content_string = '';

    /**
     * does the page have cache keys AKA can it be cached?
     * Here we also set a basic cache key string that is used to generate the cache keys.
     * `self::$_can_cache_content_string` is set with stuff you can't see (logged-in, etc...)
     */
    public function HasCacheKeys(): bool
    {
        $owner = $this->getOwner();
        if (null === self::$_can_cache_content) {

            // we assume we can cache
            $canCache = true;

            $request = $owner->getRequest();
            if ($request->IsGet() && $request->getVar('flush')) {
                $canCache = false;
            }

            // set basics
            self::$_can_cache_content_string = $this->cacheSafeDomainId();

            // override
            if ($owner->hasMethod('canCachePage')) {
                // if it can cache the page, then it the cache string will remain empty.
                $canCache = $owner->canCachePage();
            }

            // stage!
            if (Versioned::get_reading_mode() !== 'Stage.Live') {
                self::$_can_cache_content_string .= 'V' . Versioned::get_reading_mode();
                $canCache = false;
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
                } else {
                    $canCache = false;
                }
            }

            // crucial
            self::$_can_cache_content = (bool) $canCache;
        }
        return self::$_can_cache_content;
    }

    public function HasCacheKeyMeta(): bool
    {
        return $this->HasCacheKeys();
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

    public function CacheKeyMeta(?bool $includePageId = true, ?bool $forceCaching = false): string
    {
        return $this->CacheKeyGenerator('META', $includePageId, $forceCaching);
    }

    public function CacheKeyHeader(?bool $includePageId = false, ?bool $forceCaching = false): string
    {
        return $this->CacheKeyGenerator('H', $includePageId, $forceCaching);
    }

    public function CacheKeyMenu(?bool $includePageId = true, ?bool $forceCaching = false): string
    {
        return $this->CacheKeyGenerator('M', $includePageId, $forceCaching);
    }

    public function CacheKeyFooter(?bool $includePageId = false, ?bool $forceCaching = false): string
    {
        return $this->CacheKeyGenerator('F', $includePageId, $forceCaching);
    }

    public function CacheKeyContent(?bool $forceCaching = false): string
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

    public function CacheKeyGenerator(string $letter, ?bool $includePageId = true, ?bool $forceCaching = false): string
    {
        $owner = $this->getOwner();
        if ($this->HasCacheKeys() || $forceCaching) {
            $string = $letter . '_' . $this->getCanCacheContentString() . '_' . $this->cacheKeyAnyDataObjectChanges();

            if ($includePageId) {
                $string .= '_ID_' . $owner->ID;
                $string .= $this->cacheSafeUrlId();
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

    public function cacheSafeUrlId()
    {
        // note that
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // 2. Hash it to generate a safe, unique, a-z/0-9 string
        return md5($uri);
    }

    public function cacheSafeDomainId()
    {
        // 1. Safely extract the pieces of the current URL
        // Fallbacks are included just in case the script is run from a command line
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // 2. Hash it to generate a safe, unique, a-z/0-9 string
        return md5($protocol . $host);
    }
}
