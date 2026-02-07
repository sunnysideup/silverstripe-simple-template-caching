<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\SiteConfig\SiteConfig;

class CachedPages extends Report
{
    public function title()
    {
        return 'Pages with caching';
    }

    public function group()
    {
        return _t(__CLASS__ . '.ContentGroupTitle', 'Content reports');
    }

    public function sort()
    {
        return 100;
    }

    /**
     * Gets the source records
     *
     * @param array $params
     * @return DataList<Page>
     */
    public function sourceRecords($params = null)
    {
        $sc = SiteConfig::current_site_config();
        if (! $sc->HasCaching) {
            return Page::get()->filter(['ID' => 0]);
        } elseif ($sc->PublicCacheDurationInSeconds > 0) {
            return Page::get()->filter(['NeverCachePublicly' => false]);
        } else {
            return Page::get()
                ->filter(['PublicCacheDurationInSeconds:GreaterThan' => 0, 'NeverCachePublicly' => false]);
        }
    }

    public function columns()
    {
        return [
            'Title' => [
                'title' => 'Title',
                'link' => true,
            ],
            // 'ShowInMenus' => [
            //     'title' => 'Cache Link',
            //     'formatting' => function ($value, $item) {
            //         return '<a href="' . $item->Link() . '?flush=all" target="_blank">Flush Cache</a>';
            //     },
            // ],
            'ShowInSearch' => [
                'title' => 'Edit Cache Settings',
                'formatting' => function ($value, $item) {
                    return '<a href="' . $item->EditCacheSettingsLink() . '" target="_blank">Edit Settings</a>';
                },
            ],
        ];
    }
}
