<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;
use Sunnysideup\SimpleTemplateCaching\Model\ObjectsUpdated;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\SimpleTemplateCachingSiteConfigExtension.
 *
 * @property SiteConfig|SimpleTemplateCachingSiteConfigExtension $owner
 * @property bool $HasCaching
 * @property int $PublicCacheDurationInSeconds
 * @property bool $RecordCacheUpdates
 * @property string $CacheKeyLastEdited
 * @property string $ClassNameLastEdited
 */
class SimpleTemplateCachingSiteConfigExtension extends Extension
{
    private const MAX_OBJECTS_UPDATED = 1000;

    private static string $image_cache_directive = '
<IfModule mod_headers.c>
  <FilesMatch "\.(jpg|jpeg|png|gif|webp|svg|avif)$">
    Header set Cache-Control "public, max-age=86400"
  </FilesMatch>
</IfModule>
    ';

    private static string $css_and_js_cache_directive = '
<IfModule mod_headers.c>
  <FilesMatch "\.(js|css)$">
    Header set Cache-Control "public, max-age=86400"
  </FilesMatch>
</IfModule>
    ';

    private static $db = [
        'HasCaching' => 'Boolean(1)',
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
        $fields->addFieldsToTab(
            'Root.Caching',
            [
                CheckboxField::create('HasCaching', 'Use caching'),
                NumericField::create('PublicCacheDurationInSeconds', 'Cache time for ALL pages')
                    ->setDescription(
                        'USE WITH CARE - This will apply caching to ALL pages on the site.
                        Time is in seconds (e.g. 600 = 10 minutes).'
                    ),
                ReadonlyField::create('CacheKeyLastEdited', 'Content Last Edited')
                    ->setDescription(
                        'The frontend template cache will be invalidated every time this value changes.
                        It changes every time anything is changed in the database.'
                    ),
                ReadonlyField::create('ClassNameLastEditedNice', 'Last class updated', $name)
                    ->setDescription('Last record updated, invalidating the cache.'),
                CheckboxField::create('RecordCacheUpdates', 'Record every change?')
                    ->setDescription(
                        'To work out when the cache is being updated, you can track every change.
                        This will slow down all your edits, so it is recommend only to turn this on temporarily - for tuning purposes.'
                    ),
            ]
        );
        if ($owner->RecordCacheUpdates) {
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
        try {
            $obj = SiteConfig::current_site_config();
        } catch (\Exception $e) {
            $obj = null;
        }
        if ($obj && $obj->HasCaching) {
            DB::query('
                UPDATE "SiteConfig"
                SET
                    "CacheKeyLastEdited" = \'' . DBDatetime::now()->Rfc2822() . '\',
                    "ClassNameLastEdited" = \'' . addslashes((string) $className) . '\'
                WHERE ID = ' . $obj->ID . '
                LIMIT 1
            ;');
        }
        if ($obj && $obj->RecordCacheUpdates) {
            $recordId = Injector::inst()
                ->create(ObjectsUpdated::class, ['ClassNameLastEdited' => $className])
                ->write();
            DB::query('DELETE FROM ObjectsUpdated WHERE ID < ' . (int) ($recordId - self::MAX_OBJECTS_UPDATED));
        }
    }

    public function requireDefaultRecords()
    {
        if ((int) SiteConfig::get()->count() > 100) {
            $currentSiteConfig = SiteConfig::current_site_config();
            if ($currentSiteConfig) {
                DB::alteration_message('Deleting all SiteConfig records except for the current one.', 'deleted');
                DB::query('DELETE FROM "SiteConfig" WHERE ID <> ' . $currentSiteConfig->ID);
            }
        }
        foreach (
            [
                'IMAGE_CACHE_DIRECTIVE' => $this->getOwner()->config()->get('image_cache_directive'),
                'CSS_JS_CACHE_DIRECTIVE' => $cssJsCacheDirective = $this->getOwner()->config()->get('css_and_js_cache_directive'),
            ] as $key => $value
        ) {
            $this->updateHtaccess($value, $key);
        }
    }

    protected function updateHtaccess(string $toAdd, string $code)
    {
        $htaccessPath = Controller::join_links(Director::publicFolder(), '.htaccess');
        $htaccessContent = file_get_contents($htaccessPath);

        // Define start and end comments
        $startComment = PHP_EOL . "# auto add start " . $code . PHP_EOL;
        $endComment = PHP_EOL . "# auto add end " . $code . PHP_EOL;

        // Full content to replace or add
        $toAddFull = $startComment . $toAdd . $endComment;

        // Check if the section already exists
        $pattern = "/" . preg_quote($startComment, '/') . ".*?" . preg_quote($endComment, '/') . "/s";
        if (preg_match($pattern, $htaccessContent)) {
            // Replace existing content between the start and end comments
            $htaccessContent = preg_replace($pattern, $toAddFull, $htaccessContent);
        } else {
            // Append the new content at the end if the section is not found
            $htaccessContent = $toAddFull . $htaccessContent;
        }

        // Save the updated .htaccess file
        file_put_contents($htaccessPath, $htaccessContent);
    }
}
