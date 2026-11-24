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
     * Which query parameters are part of the cache key (e.g. 'start', 'searchfilter').
     *
     * - Empty (default): the whole query string is part of the cache key, except for the
     *   tracking parameters listed in `cache_key_ignore_params`.
     * - Not empty: ONLY the parameters listed here are part of the cache key,
     *   everything else is ignored (so ?x=random can not create unlimited cache entries).
     *
     * @var array<int, string>
     */
    private static array $cache_relevant_query_vars = [];

    /**
     * the longest value of a relevant query parameter that is used in the cache key.
     */
    private static int $cache_relevant_query_var_max_length = 100;

    /**
     * Fail closed. A request is only cached (template fragments, browser, CDN) if its query string
     * is cache safe, see `hasCacheSafeQueryVars()`.
     *
     * Every non-empty value of every (non-tracking) query parameter must match this pattern.
     * The default only allows short tokens / ids (letters, numbers, underscore, dash).
     * This means that free text (e.g. a search phrase) is never cached and that nobody can fill
     * the cache with endless unique values.
     * Set to an empty string to switch the check off (not recommended).
     */
    private static string $cache_safe_query_var_pattern = '/^[A-Za-z0-9_-]{1,30}$/';

    /**
     * Stricter (or different) patterns for specific parameters, e.g. ['start' => '/^[0-9]{1,4}$/'].
     *
     * @var array<string, string>
     */
    private static array $cache_safe_query_var_patterns = [
        'start' => '/^[0-9]{1,4}$/',
    ];

    /**
     * More non-empty values than this in one query string: do not cache.
     * Set to zero for no limit (not recommended).
     */
    private static int $cache_safe_query_var_max_count = 6;

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

            // a query string that is not part of the cache key (or not safe) must never end up in the cache
            if (!$this->hasCacheSafeQueryVars()) {
                $canCache = false;
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

    /**
     * Fail closed: is the query string safe to cache?
     *
     * A request is NOT cached if:
     *
     * - `cache_relevant_query_vars` is set and a (non-empty) query parameter is neither listed there nor a
     *   tracking parameter (`cache_key_ignore_params`). Such a parameter is left out of the cache key, so the
     *   first visitor's input (e.g. a search phrase that is echoed in a form) would be cached and shown to
     *   everybody. This also protects you against a field that somebody adds later and forgets to list.
     * - `cache_relevant_query_vars` is empty (whole query string is in the key) and the name of a parameter
     *   does not match `cache_safe_query_var_pattern` (stops endless unique keys).
     * - a value does not match `cache_safe_query_var_pattern` / `cache_safe_query_var_patterns`.
     *   The allow-list limits the NAMES, not the VALUES, so without this anyone can fill the cache with
     *   ?topic=1, ?topic=2, ... and long or free text values are never cached.
     * - there are more than `cache_safe_query_var_max_count` values.
     *
     * Empty values (e.g. ?keyword=&topic=12, as sent by a filter form) are ignored: they do not change the page.
     *
     * We look at both the raw URL (what the cache key is based on) and the parsed request,
     * so that a parameter can not slip through one of them.
     */
    public function hasCacheSafeQueryVars(): bool
    {
        $vars = $this->getQueryVarsForCacheCheck();
        if ($vars === []) {
            return true;
        }

        $config = Config::inst();
        $relevant = (array) $config->get(self::class, 'cache_relevant_query_vars');
        $ignored = (array) $config->get(self::class, 'cache_key_ignore_params');
        $defaultPattern = (string) $config->get(self::class, 'cache_safe_query_var_pattern');
        $patterns = (array) $config->get(self::class, 'cache_safe_query_var_patterns');
        $maxCount = (int) $config->get(self::class, 'cache_safe_query_var_max_count');

        $count = 0;
        foreach ($vars as $name => $values) {
            $name = (string) $name;
            if (in_array($name, $ignored, true)) {
                continue;
            }

            // only non-empty values matter
            $values = array_filter($values, static fn ($v): bool => $v !== '' && $v !== null);
            if ($values === []) {
                continue;
            }

            if ($relevant !== []) {
                if (!in_array($name, $relevant, true)) {
                    return false;
                }
            } elseif ($defaultPattern !== '' && !preg_match($defaultPattern, $name)) {
                return false;
            }

            $pattern = (string) ($patterns[$name] ?? $defaultPattern);
            foreach ($values as $v) {
                ++$count;
                if ($maxCount > 0 && $count > $maxCount) {
                    return false;
                }

                if (!is_scalar($v)) {
                    return false;
                }

                if ($pattern !== '' && !preg_match($pattern, (string) $v)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * The query parameters as seen by the cache key (raw URL) AND by the application (request):
     * for each parameter name, all (flattened, unique) values from both.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function getQueryVarsForCacheCheck(): array
    {
        $fromUrl = [];
        parse_str((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY), $fromUrl);

        $fromRequest = [];
        $request = $this->getOwner()->getRequest();
        if ($request) {
            $fromRequest = $request->getVars();
        }

        $result = [];
        foreach ([$fromUrl, $fromRequest] as $source) {
            // 'url' is used by the rewrite rules, it is not a user parameter
            unset($source['url']);
            foreach ($source as $name => $value) {
                $name = (string) $name;
                $result[$name] ??= [];
                if (is_array($value)) {
                    array_walk_recursive($value, function ($v) use (&$result, $name): void {
                        $result[$name][] = $v;
                    });
                } else {
                    $result[$name][] = $value;
                }
            }
        }

        // array_walk_recursive only gives us scalars (or null). Compare as strings, never loosely ("1" == "1.0").
        foreach ($result as $name => $values) {
            $result[$name] = array_values(array_unique($values, SORT_STRING));
        }

        return $result;
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
        $path = substr((string) (parse_url($uri, PHP_URL_PATH) ?: '/'), 0, 500);
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $vars);

        $relevant = (array) Config::inst()->get(self::class, 'cache_relevant_query_vars');
        if ($relevant !== []) {
            // an allow-list is defined: only use those query parameters, anything else is ignored
            $vars = array_intersect_key($vars, array_flip($relevant));
        } else {
            // no allow-list: use the whole query string, except for tracking parameters
            $ignore = (array) Config::inst()->get(self::class, 'cache_key_ignore_params');
            $vars = array_diff_key($vars, array_flip($ignore));
        }

        $maxLength = max(1, (int) Config::inst()->get(self::class, 'cache_relevant_query_var_max_length'));
        $vars = $this->normaliseQueryVars($vars, $maxLength);

        $query = $vars ? '?' . http_build_query($vars) : '';

        return md5($path . $query);
    }

    /**
     * Keeps keys short and sorts them (recursively) so that
     * ?a=1&b=2 and ?b=2&a=1 hit the same key.
     *
     * Values longer than $maxLength are shortened BUT a hash of the full value is added.
     * Otherwise two different long values with the same first characters would share
     * one cache entry, and one visitor would be served the other visitor's page.
     *
     * @param array<string|int, mixed> $vars
     * @return array<string|int, mixed>
     */
    protected function normaliseQueryVars(array $vars, int $maxLength): array
    {
        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                $vars[$key] = $this->normaliseQueryVars($value, $maxLength);
            } elseif (is_scalar($value)) {
                $value = (string) $value;
                $vars[$key] = strlen($value) > $maxLength
                    ? substr($value, 0, $maxLength) . '~' . md5($value)
                    : $value;
            } else {
                $vars[$key] = '';
            }
        }

        ksort($vars);

        return $vars;
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
