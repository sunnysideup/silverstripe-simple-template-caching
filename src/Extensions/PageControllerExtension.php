<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;

class PageControllerExtension extends Extension
{
    /**
     * @var string|null
     */
    protected static $_cache_key_sitetree_changes = null;

    /**
     * @var bool|null
     */
    private static $_can_cache_content = null;

    /**
     * @var string
     */
    private static $_can_cache_content_string = '';

    public function HasCacheKeys(): bool
    {
        if (self::$_can_cache_content === null) {
            self::$_can_cache_content_string = '';
            if ($this->owner->hasMethod('canCachePage')) {
                self::$_can_cache_content_string = $this->owner->canCachePage() ? '' : 'can-no-cache-' . $this->owner->ID;
                if ($this->canCacheCheck() === false) {
                    return false;
                }
            }

            //action
            $action = $this->owner->request->param('Action');
            if ($action) {
                self::$_can_cache_content_string .= $action;
                if ($this->canCacheCheck() === false) {
                    return false;
                }
            }
            $id = $this->owner->request->param('ID');
            // id
            if ($id) {
                self::$_can_cache_content_string .= $id;
                if ($this->canCacheCheck() === false) {
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
                    if ($this->canCacheCheck() === false) {
                        return false;
                    }
                }
            }

            //member
            $member = Security::getCurrentUser();
            if ($member && $member->exists()) {
                self::$_can_cache_content_string .= $member->ID;
                if ($this->canCacheCheck() === false) {
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

    public function CacheKeyHeader(): string
    {
        return $this->CacheKeyGenerator('H');
    }

    public function CacheKeyMenu(): string
    {
        return $this->CacheKeyGenerator('M');
    }

    public function CacheKeyFooter(): string
    {
        return $this->CacheKeyGenerator('F');
    }

    public function CacheKeyContent(): string
    {
        $cacheKey = $this->CacheKeyGenerator('C');
        if ($this->owner->hasMethod('CacheKeyContentCustom')) {
            $cacheKey .= '_' . $this->owner->CacheKeyContentCustom();
        }

        return $cacheKey;
    }

    public function CacheKeyGenerator($letter): string
    {
        if ($this->HasCacheKeys()) {
            $string = $letter . '_' .
                $this->cacheKeySiteTreeChanges() . '_' .
                'ID_' . $this->owner->ID;
        } else {
            $string = 'NOT_CACHED' . time() . '_' . rand(0, 999999999999);
        }

        return $string;
    }

    protected function canCacheCheck(): bool
    {
        if (self::$_can_cache_content_string !== '') {
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
        if (self::$_cache_key_sitetree_changes === null) {
            self::$_cache_key_sitetree_changes = SimpleTemplateCachingSiteConfigExtension::site_cache_key();
            self::$_cache_key_sitetree_changes .= $this->getCanCacheContentString();
        }

        return self::$_cache_key_sitetree_changes;
    }
}
