<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;

class NeverCachedPages extends Report
{
    public function title()
    {
        return 'Pages that are never cached';
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
            ->filter(['NeverCachePublicly' => 1]);
    }

    public function columns()
    {
        return [
            "Title" => [
                "title" => "Title",
                "link" => true,
            ],
            'ShowInSearch' => [
                'title' => 'Edit Cache Settings',
                'formatting' => function ($value, $item) {
                    return '<a href="' . $item->EditCacheSettingsLink() . '" target="_blank">Edit Settings</a>';
                },
            ],
        ];
    }
}
