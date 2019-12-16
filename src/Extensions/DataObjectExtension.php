<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\Versioned;

class DataObjectExtension extends DataExtension
{
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $owner = $this->owner;
        $className = $owner->ClassName;

        if(!$owner->hasExtension(Versioned::class)){
            $this->doUpdateCache($className);
        }
        //if the dataobject has the versioned extension then the cache should be invalidated onAfterPublish
        //hasStages function is part of the Versioned class so safe to check here
        else if (!$owner->hasStages()){
            $this->doUpdateCache($className);
        }
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();
        $this->doUpdateCache($this->owner->ClassName);
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

    public function onBeforeRollback(){
        $this->doUpdateCache($this->owner->ClassName);
    }

    public function onAfterPublish(){
        $this->doUpdateCache($this->owner->ClassName);
    }

    public function onAfterArchive(){
        $this->doUpdateCache($this->owner->ClassName);
    }

    public function onAfterUnpublish(){
        $this->doUpdateCache($this->owner->ClassName);
    }

    public function onAfterVersionedPublish(){
        $this->doUpdateCache($this->owner->ClassName);
    }

    public function onAfterWriteToStage($toStage){
        $this->doUpdateCache($this->owner->ClassName);
    }

    private function doUpdateCache($className){
        if($this->canUpdateCache($className)){
            SiteConfigExtension::update_cache_key();
        }
    }

    private function canUpdateCache($className){
        $excludedClasses = Config::inst()->get(self::class, 'excluded_classes');
        return in_array($className, $excludedClasses) ? false : true;
    }
}
