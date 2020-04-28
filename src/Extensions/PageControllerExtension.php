<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;

class PageControllerExtension extends Extension
{

    private static $indexes = [
        'LastEdited' => true,
    ];

    /**
     * @var null|bool
     */
    private static $_can_cache_content = null;
    private static $_can_cache_content_string = '';

    public function HasCacheKeys(): bool
    {
        if (self::$_can_cache_content === null) {
            self::$_can_cache_content_string = '';
            $action = $this->owner->request->param('Action');
            if ($action) {
                self::$_can_cache_content_string .= $action;
            }
            $id = $this->owner->request->param('ID');
            if ($id) {
                self::$_can_cache_content_string .= $id;
            }
            $requestVars = $this->owner->request->requestVars();
            if ($requestVars) {
                self::$_can_cache_content_string .= implode('-', $requestVars);
            }
            $member = Security::getCurrentUser();
            if($member && $member->exists()) {
                self::$_can_cache_content_string .= $member->ID;
            }
            if (self::$_can_cache_content_string !== '') {
                self::$_can_cache_content = false;
            } else {
                self::$_can_cache_content = true;
            }
        }
        return self::$_can_cache_content;
    }

    protected function getCanCacheContentString() : string
    {
        return self::$_can_cache_content_string;
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
         $this->CacheKeyGenerator('H');
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
        if($this->owner->hasMethod('CacheKeyContentCustom')){
            $cacheKey .= '_'.$this->owner->CacheKeyContentCustom();
        }

        return $cacheKey;
    }

    public function CacheKeyGenerator($letter) : string
    {
        if($this->HasCacheKeys()) {
            $string = $letter.'_' .
                $this->cacheKeySiteTreeChanges() . '_' .
                'ID_' . $this->owner->ID;
        } else {
            $string = 'NOT_CACHED'.time().'_'.rand(0,999999999999);
        }

        return $string;
    }



    /**
     *
     * @var null|bool
     */
    private static $_cache_key_sitetree_changes = null;

    protected function cacheKeySiteTreeChanges() : string
    {
        if(self::$_cache_key_sitetree_changes === null) {
            self::$_cache_key_sitetree_changes = SimpleTemplateCachingSiteConfigExtension::site_cache_key();
            self::$_cache_key_sitetree_changes .= $this->getCanCacheContentString();
        }

        return self::$_cache_key_sitetree_changes;
    }

}
