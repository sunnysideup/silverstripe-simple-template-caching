<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use PageController;
use SilverStripe\Control\Director;
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
    protected static ?string $_cache_key_any_data_object_changes = null;

    /**
     * @var bool
     */
    protected static ?bool $_can_cache_content = null;

    protected static string $_can_cache_content_string = '';

    private static array $cache_key_ignore_params = [
        // Google (Ads + Analytics)
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'utm_id', 'utm_name', 'utm_cid', 'utm_reader', 'utm_referrer',
        'gclid', 'gclsrc', 'dclid', 'gbraid', 'wbraid', '_ga', '_gl',
        // Microsoft / Bing
        'msclkid',
        // Meta / Instagram / TikTok / X / LinkedIn / Yandex
        'fbclid', 'igshid', 'ttclid', 'twclid', 'li_fat_id', 'yclid', '_openstat',
        // Email platforms
        'mc_cid', 'mc_eid',                                  // Mailchimp
        'mkt_tok',                                           // Marketo
        'vero_id', 'vero_conv',                              // Vero
        // HubSpot
        '_hsenc', '_hsmi', '__hstc', '__hssc', '__hsfp', 'hsCtaTracking',
        'hsa_acc', 'hsa_cam', 'hsa_grp', 'hsa_ad', 'hsa_src',
        'hsa_tgt', 'hsa_kw', 'hsa_mt', 'hsa_net', 'hsa_ver',
        // Matomo / Piwik
        'pk_campaign', 'pk_kwd', 'pk_source', 'pk_medium', 'pk_content',
        'mtm_campaign', 'mtm_kwd', 'mtm_source', 'mtm_medium', 'mtm_content',
        // Adobe
        's_kwcid', 'ef_id',
    ];

    /**
     * does the page have cache keys AKA can it be cached?
     * Here we also set a basic cache key string that is used to generate the cache keys.
     * `self::$_can_cache_content_string` is set with stuff you can't see (logged-in, etc...)
     */
    public function HasCacheKeys(): bool
    {
        if (null === self::$_can_cache_content) {

            // we assume we can cache
            $canCache = true;

            $owner = $this->getOwner();
            $request = $owner->getRequest();
            if (!$request || $request->IsGet() !== true || $request->getVar('flush')) {
                $canCache = false;
            }

            // set basics
            self::$_can_cache_content_string = $this->cacheSafeDomainId();

            // override
            if ($owner->hasMethod('canCachePage')) {
                if (!$owner->canCachePage()) {
                    $canCache = false;
                }
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

    public function CacheKeyMeta(?bool $includePageId = true): string
    {
        return $this->CacheKeyGenerator('META', $includePageId);
    }

    public function CacheKeyHeader(?bool $includePageId = false): string
    {
        return $this->CacheKeyGenerator('H', $includePageId);
    }

    public function CacheKeyMenu(?bool $includePageId = true): string
    {
        return $this->CacheKeyGenerator('M', $includePageId);
    }

    public function CacheKeyFooter(?bool $includePageId = false): string
    {
        return $this->CacheKeyGenerator('F', $includePageId);
    }

    public function CacheKeyContent(): string
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

    public function CacheKeyGenerator(string $letter, ?bool $includePageId = true): string
    {
        $owner = $this->getOwner();
        if ($this->HasCacheKeys()) {
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

    protected function getRandomKey(): string
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

    public function cacheSafeUrlId(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // split path and query out of the raw URI
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $vars);

        // drop only the tracking params — keep everything else that changes output
        $ignore = array_flip(Config::inst()->get(self::class, 'cache_key_ignore_params'));
        $vars   = array_diff_key($vars, $ignore);

        // stable order so ?a=1&b=2 and ?b=2&a=1 hit the same key
        ksort($vars);

        $query = $vars ? '?' . http_build_query($vars) : '';

        return md5($path . $query);
    }

    public function cacheSafeDomainId(): string
    {
        // 1. Safely extract the pieces of the current URL
        // Fallbacks are included just in case the script is run from a command line
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = Director::host();

        // 2. Hash it to generate a safe, unique, a-z/0-9 string
        return md5($protocol . $host);
    }
}
