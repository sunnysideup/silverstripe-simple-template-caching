<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\SiteConfig\SiteConfig;
use Sunnysideup\SimpleTemplateCaching\Api\FasterSiteConfig;

abstract class BaseCachedReport extends Report
{
    public function group()
    {
        return _t(static::class . '.ContentGroupTitle', 'Content reports');
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
        $sc = FasterSiteConfig::current_site_config();
        if (! $sc->HasCaching) {
            return Page::get()->filter(['ID' => 0]);
        } else {
            return $this->extraSourceRecords($sc, $params);
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

    abstract protected function extraSourceRecords(SiteConfig $sc, $params = null): DataList;
}
