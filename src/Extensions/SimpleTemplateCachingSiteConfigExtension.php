<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;
use Sunnysideup\SimpleTemplateCaching\Model\ObjectsUpdated;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\SimpleTemplateCachingSiteConfigExtension
 *
 * @property SiteConfig|SimpleTemplateCachingSiteConfigExtension $owner
 * @property bool $HasCaching
 * @property bool $RecordCacheUpdates
 * @property string $CacheKeyLastEdited
 * @property string $ClassNameLastEdited
 */
class SimpleTemplateCachingSiteConfigExtension extends DataExtension
{
    private const MAX_OBJECTS_UPDATED = 1000;

    private static $db = [
        'HasCaching' => 'Boolean(1)',
        'RecordCacheUpdates' => 'Boolean(0)',
        'CacheKeyLastEdited' => 'DBDatetime',
        'ClassNameLastEdited' => 'Varchar(200)',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $name = '';
        if (class_exists($this->getOwner()->ClassNameLastEdited)) {
            $name = Injector::inst()->get($this->getOwner()->ClassNameLastEdited)->i18n_singular_name();
        }
        $fields->addFieldsToTab(
            'Root.Caching',
            [
                CheckboxField::create('HasCaching', 'Use caching'),
                CheckboxField::create('RecordCacheUpdates', 'Record every change?')
                    ->setDescription('To work out when the cache is being updated, you can track every change. This will slow down all your edits, so it is recommend only to turn this on temporarily - for tuning purposes.'),
                ReadonlyField::create('CacheKeyLastEdited', 'Content Last Edited')
                    ->setRightTitle('The frontend template cache will be invalidated every time this value changes. It changes every time anything is changed in the database.'),
                ReadonlyField::create('ClassNameLastEdited', 'Last class updated')
                    ->setRightTitle('Last object updated. The name of this object is: ' . $name),
            ]
        );
        if ($this->getOwner()->RecordCacheUpdates) {
            $fields->addFieldsToTab(
                'Root.Caching',
                [
                    GridField::create(
                        'ObjectsUpdated',
                        'Last ' . self::MAX_OBJECTS_UPDATED . ' objects updated',
                        ObjectsUpdated::get()->limit(self::MAX_OBJECTS_UPDATED),
                        GridFieldConfig_RecordViewer::create()
                    ),
                ]
            );
        }
    }

    public static function site_cache_key(): string
    {
        $obj = SiteConfig::current_site_config();
        if ($obj->HasCaching) {
            return (string) 'ts_' . strtotime($obj->CacheKeyLastEdited);
        }

        return  (string) 'ts_' . time();
    }

    public static function update_cache_key(?string $className = '')
    {
        $obj = SiteConfig::current_site_config();
        if ($obj->HasCaching) {
            DB::query('
                UPDATE "SiteConfig"
                SET
                    "CacheKeyLastEdited" = \'' . DBDatetime::now()->Rfc2822() . '\',
                    "ClassNameLastEdited" = \'' . addslashes((string) $className) . '\'
                WHERE ID = ' . $obj->ID . '
                LIMIT 1
            ;');
        }
        if ($obj->RecordCacheUpdates) {
            $recordId = Injector::inst()
                ->create(ObjectsUpdated::class, ['ClassNameLastEdited' => $className])
                ->write()
            ;
            DB::query('DELETE FROM ObjectsUpdated WHERE ID < ' . (int) ($recordId - self::MAX_OBJECTS_UPDATED));
        }
    }
}
