<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\SiteConfig\SiteConfig;

class CloudflareCachedPages extends BaseCachedReport
{
    public function title()
    {
        return 'Pages with caching time for Cloudflare cache';
    }

    protected function extraSourceRecords(SiteConfig $sc, $params = null): DataList
    {
        if ($sc->CloudflareCacheDurationInSeconds > 0) {
            return Page::get()->filter(['NeverCachePublicly' => false]);
        } else {
            return Page::get()
                ->filter(['CloudflareCacheDurationInSeconds:GreaterThan' => 0, 'NeverCachePublicly' => false]);
        }
    }
}
