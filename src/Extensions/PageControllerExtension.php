<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;

class PageControllerExtension extends Extension
{
    /**
     * @var null|string
     */
    protected static $_cache_key_sitetree_changes;

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
        if (null === self::$_can_cache_content) {
            self::$_can_cache_content_string = '';
            if ($this->owner->hasMethod('canCachePage')) {
                self::$_can_cache_content_string = $this->owner->canCachePage() ? '' : 'can-no-cache-' . $this->owner->ID;
                if (! $this->canCacheCheck()) {
                    return false;
                }
            }

            //action
            $action = $this->owner->request->param('Action');
            if ($action) {
                self::$_can_cache_content_string .= $action;
                if (! $this->canCacheCheck()) {
                    return false;
                }
            }

            $id = $this->owner->request->param('ID');
            // id
            if ($id) {
                self::$_can_cache_content_string .= $id;
                if (! $this->canCacheCheck()) {
                    return false;
                }
            }

            //request vars
            $requestVars = $this->owner->request->requestVars();
            if ($requestVars) {
                foreach ($this->owner->request->requestVars() as $item) {
                    if (is_string($item)) {
                        self::$_can_cache_content_string .= $item;
                    } elseif (is_numeric($item)) {
                        self::$_can_cache_content_string .= $item;
                    }

                    if (! $this->canCacheCheck()) {
                        return false;
                    }
                }
            }

            //member
            $member = Security::getCurrentUser();
            if ($member && $member->exists()) {
                self::$_can_cache_content_string .= $member->ID;
                if (! $this->canCacheCheck()) {
                    return false;
                }
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

    public function CacheKeyHeader($includePageId = true): string
    {
        return $this->CacheKeyGenerator('H', $includePageId);
    }

    public function CacheKeyMenu($includePageId = true): string
    {
        return $this->CacheKeyGenerator('M', $includePageId);
    }

    public function CacheKeyFooter($includePageId = true): string
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
            $string = $letter . '_' .
                $this->cacheKeySiteTreeChanges();

            if ($includePageId) {
                $string .= '_ID_' . $this->owner->ID;
            }

        } else {
            $string = 'NOT_CACHED' . time() . '_' . rand(0, 999999999999);
        }

        return $string;
    }

    protected function canCacheCheck(): bool
    {
        if ('' !== self::$_can_cache_content_string) {
            self::$_can_cache_content = false;

            return false;
        }

        return true;
    }

    protected function getCanCacheContentString(): string
    {
        return self::$_can_cache_content_string;
    }

    protected function cacheKeySiteTreeChanges(): string
    {
        if (null === self::$_cache_key_sitetree_changes) {
            self::$_cache_key_sitetree_changes = SimpleTemplateCachingSiteConfigExtension::site_cache_key();
            self::$_cache_key_sitetree_changes .= $this->getCanCacheContentString();
        }

        return self::$_cache_key_sitetree_changes;
    }
}
