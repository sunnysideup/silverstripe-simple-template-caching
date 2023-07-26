<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use PageController;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\PageControllerExtension.
 *
 * @property PageController|PageControllerExtension $owner
 */
class PageControllerExtension extends Extension
{
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
    private static $_can_cache_content_string = '';

    public function HasCacheKeys(): bool
    {
        /** @var PageController owner */
        $owner = $this->owner;
        if (null === self::$_can_cache_content) {
            $canCache = true;
            self::$_can_cache_content_string = '';
            if ($this->owner->hasMethod('canCachePage')) {
                // if it can cache the page, then it the cache string will remain empty.
                self::$_can_cache_content_string .= $this->owner->canCachePage() ? '' : 'can-no-cache-' . $this->owner->ID. '_'.rand(0, 99999999999999999);
            }

            //action
            $action = $this->owner->request->param('Action');
            if ($action) {
                self::$_can_cache_content_string .= $action;
            }

            $id = $this->owner->request->param('ID');
            // id
            if ($id) {
                self::$_can_cache_content_string .= $id;
            }

            //request vars
            $requestVars = $this->owner->request->requestVars();
            if ($requestVars) {
                foreach ($this->owner->request->requestVars() as $item) {
                    self::$_can_cache_content_string .= (string) $item;
                }
            }

            //member
            $member = Security::getCurrentUser();
            if ($member && $member->exists()) {
                self::$_can_cache_content_string .= $member->ID;
            }
            if (! $this->canCacheCheck()) {
                return false;
            }
            // we are ok!
            self::$_can_cache_content = true;
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
        return $this->HasCacheKeys();
    }

    public function HasCacheKeyFooter(): bool
    {
        return $this->HasCacheKeys();
    }

    public function CacheKeyHeader(?bool $includePageId = true): string
    {
        return $this->CacheKeyGenerator('H', $includePageId);
    }

    public function CacheKeyMenu(?bool $includePageId = true): string
    {
        return $this->CacheKeyGenerator('M', $includePageId);
    }

    public function CacheKeyFooter(?bool $includePageId = true): string
    {
        return $this->CacheKeyGenerator('F', $includePageId);
    }

    public function CacheKeyContent(): string
    {
        $cacheKey = $this->CacheKeyGenerator('C');
        if ($this->owner->hasMethod('CacheKeyContentCustom')) {
            $cacheKey .= '_' . $this->owner->CacheKeyContentCustom();
        }

        return $cacheKey;
    }

    public function CacheKeyGenerator($letter, $includePageId = true): string
    {
        if ($this->HasCacheKeys()) {
            $string = $letter . '_' . $this->cacheKeyAnyDataObjectChanges();

            if ($includePageId) {
                $string .= '_ID_' . $this->owner->ID;
            }
        } else {
            $string = 'NOT_CACHED_' . '_ID_' . $this->owner->ID . time() . '_' . rand(0, 999);
        }

        return $string;
    }

    /**
     * if the cache string is NOT empty then we cannot cache.
     *
     * @return boolean
     */
    protected function canCacheCheck(): bool
    {
        if ('' !== trim(self::$_can_cache_content_string)) {
            self::$_can_cache_content = false;

            return false;
        }

        return true;
    }

    protected function getCanCacheContentString(): string
    {
        return self::$_can_cache_content_string;
    }

    protected function cacheKeyAnyDataObjectChanges(): string
    {
        if (null === self::$_cache_key_any_data_object_changes) {
            self::$_cache_key_any_data_object_changes = SimpleTemplateCachingSiteConfigExtension::site_cache_key();
            self::$_cache_key_any_data_object_changes .= $this->getCanCacheContentString();
        }

        return self::$_cache_key_any_data_object_changes;
    }
}
