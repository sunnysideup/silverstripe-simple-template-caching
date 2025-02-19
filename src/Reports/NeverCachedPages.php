<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\SiteConfig\SiteConfig;

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
        $sc = SiteConfig::current_site_config();
        if (! $sc->HasCaching) {
            return Page::get();
        } elseif ($sc->PublicCacheDurationInSeconds > 0) {
            return Page::get()->filter(['NeverCachePublicly' => true]);
        } else {
            return Page::get()
                ->filterAny(['PublicCacheDurationInSeconds' => 0, 'NeverCachePublicly' => true]);
        }
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
