<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\SimpleTemplateCaching\Model\ObjectsUpdated;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\DataObjectExtension.
 *
 * @property DataObject|DataObjectExtension $owner
 */
class DataObjectExtension extends Extension
{

    private static array $excluded_classes_for_caching = [
        ObjectsUpdated::class,
    ];

    private static $included_classes_for_caching = [
    ];

    public function onAfterWrite()
    {
        $owner = $this->getOwner();

        // NB.
        // if the dataobject has the versioned extension then the cache should be invalidated onAfterPublish
        // hasStages function is part of the Versioned class so safe to check here
        if (! $owner->hasExtension(Versioned::class)) {
            $this->doUpdateCache();
        } elseif (! $owner->hasStages()) {
            $this->doUpdateCache();
        }
    }

    public function onAfterDelete()
    {
        $this->doUpdateCache();
    }

    //* this function needs further consideration as it is called many times on the front end */
    // public function updateManyManyComponents()
    // {
    //     $owner = $this->owner;
    //     $className = $owner->ClassName;

    //     if(!$owner->hasExtension(Versioned::class)){
    //         $this->doUpdateCache($className);
    //     }
    //     //if the dataobject has the versioned extension then the cache should be invalidated onAfterPublish
    //     else if (!$owner->hasStages()){
    //         $this->doUpdateCache($className);
    //     }
    // }

    public function onBeforeRollback()
    {
        $this->doUpdateCache();
    }

    public function onAfterPublish()
    {
        $this->doUpdateCache();
    }

    public function onAfterArchive()
    {
        $this->doUpdateCache();
    }

    public function onAfterUnpublish()
    {
        $this->doUpdateCache();
    }

    public function onAfterVersionedPublish()
    {
        $this->doUpdateCache();
    }

    public function onAfterWriteToStage($toStage)
    {
        $this->doUpdateCache();
    }

    private function doUpdateCache()
    {
        $className = (string) $this->getOwner()->ClassName;
        if ($className && $this->canUpdateCache($className)) {
            SimpleTemplateCachingSiteConfigExtension::update_cache_key($className);
        }
    }

    /**
     * @var array<string, bool>
     */
    protected static array $canUpdateCache = [];

    protected function canUpdateCache(string $className): bool
    {
        if (isset(self::$canUpdateCache[$className])) {
            return self::$canUpdateCache[$className];
        }

        $included = (array) Config::inst()->get(self::class, 'included_classes_for_caching');

        if (!empty($included)) {
            return self::$canUpdateCache[$className] = $this->matchesAny($className, $included);
        }

        $excluded = (array) Config::inst()->get(self::class, 'excluded_classes_for_caching');

        return self::$canUpdateCache[$className] = ! $this->matchesAny($className, $excluded);
    }

    private function matchesAny(string $className, array $classes): bool
    {
        foreach ($classes as $class) {
            if (ClassInfo::exists($className) && is_a($className, $class, true)) {
                return true;
            }
        }

        return false;
    }
}
