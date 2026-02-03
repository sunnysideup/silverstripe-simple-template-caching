<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use Exception;
use Page;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;
use Sunnysideup\SimpleTemplateCaching\Model\ObjectsUpdated;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\SimpleTemplateCachingSiteConfigExtension.
 *
 * @property SiteConfig|SimpleTemplateCachingSiteConfigExtension $owner
 * @property bool $HasCaching
 * @property bool $HasPartialCaching
 * @property int $PublicCacheDurationInSeconds
 * @property bool $RecordCacheUpdates
 * @property ?string $CacheKeyLastEdited
 * @property ?string $ClassNameLastEdited
 */
class SimpleTemplateCachingSiteConfigExtension extends Extension
{
    private const MAX_OBJECTS_UPDATED = 1000;

    private static $db = [
        'HasCaching' => 'Boolean(1)',
        'HasPartialCaching' => 'Boolean(1)',
        'PublicCacheDurationInSeconds' => 'Int',
        'RecordCacheUpdates' => 'Boolean(0)',
        'CacheKeyLastEdited' => 'DBDatetime',
        'ClassNameLastEdited' => 'Varchar(200)',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $name = '[none]';
        if (class_exists((string) $owner->ClassNameLastEdited)) {
            $name = Injector::inst()->get($owner->ClassNameLastEdited)->i18n_singular_name();
        }

        // page caching
        $fields->addFieldsToTab(
            'Root.Caching',
            [
                HeaderField::create('FullPageCachingHeader', 'Full Page Caching'),
                CheckboxField::create('HasCaching', 'Allow caching of entire pages?')
                    ->setDescription(
                        'You will also need to set up the cache time below for it to be enabled.
                        You can set a default time below, but you can also set the time for individual pages.'
                    ),
            ]
        );
        if ($owner->HasCaching) {
            $fields->addFieldsToTab(
                'Root.Caching',
                [
                    NumericField::create('PublicCacheDurationInSeconds', 'Cache time for ALL pages')
                        ->setDescription(
                            'USE WITH CARE - This will apply caching to ALL pages on the site.
                            Time is in seconds (e.g. 600 = 10 minutes).
                            Cache time on individual pages will override this value set here.
                            The total number of pages on the site with an individual caching time is: ' . Page::get()->filter('PublicCacheDurationInSeconds:GreaterThan', 0)->count()
                        ),
                ]
            );
        }

        //partial caching
        $fields->addFieldsToTab(
            'Root.Caching',
            [
                HeaderField::create('PartialCachingHeader', 'Partial Caching'),
                CheckboxField::create('HasPartialCaching', 'Allow partial template caching?')
                    ->setDescription(
                        'This should usually be turned on unless you want to make sure no templates are cached in any part at all.'
                    ),
            ]
        );
        if ($owner->HasPartialCaching) {
            $fields->addFieldsToTab(
                'Root.Caching',
                [
                    CheckboxField::create('RecordCacheUpdates', 'Keep a record of what is being changed?')
                        ->setDescription(
                            'To work out when the cache is being cleared,
                            you can keep a record of the last ' . self::MAX_OBJECTS_UPDATED . ' records changed.
                            Only turn this on temporarily for tuning purposes.'
                        ),
                ]
            );
            if ($this->getOwner()->RecordCacheUpdates) {
                $fields->addFieldsToTab(
                    'Root.Caching',
                    [

                        ReadonlyField::create('CacheKeyLastEditedNice', 'Last database change', $owner->dbObject('CacheKeyLastEdited')->ago())
                            ->setDescription(
                                'The frontend template cache will be invalidated every time this value changes.
                                                The value changes every time anything is changed in the database.'
                            ),
                        ReadonlyField::create('ClassNameLastEditedNice', 'Last record updated', $name)
                            ->setDescription('The last record to invalidate the cache.'),

                        GridField::create(
                            'ObjectsUpdated',
                            'Last ' . self::MAX_OBJECTS_UPDATED . ' records updated',
                            ObjectsUpdated::get()->limit(self::MAX_OBJECTS_UPDATED),
                            GridFieldConfig_RecordViewer::create()
                        )
                            ->setDescription(
                                '
                                This is a list of the last ' . self::MAX_OBJECTS_UPDATED . ' records updated.
                                It is used to track changes to the database.
                                It includes: ' . ObjectsUpdated::classes_edited()
                            ),
                    ]
                );
            }
        }
    }

    public static function site_cache_key(): string
    {
        $obj = SiteConfig::current_site_config();
        if ($obj->HasPartialCaching) {
            return 'ts_' . strtotime((string) $obj->CacheKeyLastEdited);
        }

        return 'ts_' . time();
    }

    public static function update_cache_key(?string $className = '')
    {
        // important - avoid endless loop!
        if (SiteConfig::get()->exists()) {
            $howOldIsIt = DB::query('SELECT Created FROM SiteConfig LIMIT 1')->value();
            if ($howOldIsIt && strtotime((string) $howOldIsIt) > strtotime('-5 minutes')) {
                return;
            }
        } else {
            return;
        }
        $obj = null;
        try {
            $obj = SiteConfig::current_site_config();
            if ($obj->HasPartialCaching) {
                DB::query('
                    UPDATE "SiteConfig"
                    SET
                        "CacheKeyLastEdited" = \'' . DBDatetime::now()->Rfc2822() . '\',
                        "ClassNameLastEdited" = \'' . addslashes((string) $className) . '\'
                    WHERE ID = ' . $obj->ID . '
                    LIMIT 1
                ;');
                if ($obj->RecordCacheUpdates) {
                    $recordId = Injector::inst()
                        ->create(ObjectsUpdated::class, ['ClassNameLastEdited' => $className])
                        ->write();
                    DB::query('DELETE FROM "ObjectsUpdated" WHERE "ID" < ' . (int) ($recordId - self::MAX_OBJECTS_UPDATED));
                }
            } else {
                DB::query('TRUNCATE "ObjectsUpdated";');
            }
        } catch (Exception $e) {
            if (isset($obj) && $obj && $obj->ID) {
                DB::query('
                    UPDATE "SiteConfig"
                    SET
                        "CacheKeyLastEdited" = \'' . DBDatetime::now()->Rfc2822() . '\',
                        "ClassNameLastEdited" = \'ERROR\'
                    WHERE ID = ' . $obj->ID . '
                    LIMIT 1
                ;');
            }
        }
    }


    public function DoesNotHaveCaching(): bool
    {
        $owner = $this->getOwner();
        return ! $owner->HasCaching;
    }
}
