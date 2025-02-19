<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;

class CachedPages extends Report
{
    public function title()
    {
        return 'Pages with caching';
    }

    public function group()
    {
        return _t(__CLASS__ . '.ContentGroupTitle', "Content reports");
    }

    public function sort()
    {
        return 100;
    }

    /**
     * Gets the source records
     *
     * @param array $params
     * @return DataList<SiteTree>
     */
    public function sourceRecords($params = null)
    {
        return Page::get()
            ->filter(['PublicCacheDurationInSeconds:GreaterThan' => 0, 'NeverCachePublicly' => 0]);
    }

    public function columns()
    {
        return [
            "Title" => [
                "title" => "Title",
                "link" => true,
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
