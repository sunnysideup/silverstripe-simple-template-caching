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

    public function HasCacheKeys(): bool
    {
        if (self::$_can_cache_content === null) {
            if ($this->owner->request->param('Action')
                ||
                $this->owner->request->param('ID')
                ||
                count($this->owner->request->requestVars())
                ||
                Security::getCurrentUser()
            ) {
                self::$_can_cache_content = false;
            } else {
                self::$_can_cache_content = true;
            }
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
        return 'H_' .
            $this->cacheKeySiteTreeChanges() . '_' .
            'ID_' . $this->owner->ID . '_';
    }

    public function CacheKeyMenu(): string
    {
        return 'M_' .
            $this->cacheKeySiteTreeChanges() . '_' .
            'ID_' . $this->owner->ID . '_';
    }
    
    public function CacheKeyContent(): string
    {
        return 'C_' .
            $this->cacheKeySiteTreeChanges() . '_' .
            'ID_' . $this->owner->ID . '_';

        return self::$_cache_key_content;
    }

    public function CacheKeyFooter(): string
    {
        return 'F_' .
            $this->cacheKeySiteTreeChanges() . '_' .
            'ID_' . $this->owner->ID . '_';
    }

    /**
     *
     * @var null|bool
     */
    private static $_cache_key_sitetree_changes = null;

    protected function cacheKeySiteTreeChanges() : string
    {
        if(self::$_cache_key_sitetree_changes === null) {
            self::$_cache_key_sitetree_changes = 
                SiteTree::get()->count() . '_' .
                strtotime(SiteTree::get()->Max('LastEdited'));        
        }
        
        return self::$_cache_key_sitetree_changes;
    }
    
}
