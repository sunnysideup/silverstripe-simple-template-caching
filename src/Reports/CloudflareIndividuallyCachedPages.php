<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\SiteConfig\SiteConfig;

class CloudflareIndividuallyCachedPages extends BaseCachedReport
{
    public function title()
    {
        return 'Pages with individual caching times for Cloudflare cache';
    }

    protected function extraSourceRecords(SiteConfig $sc, $params = null): DataList
    {
        return Page::get()
            ->filter(['CloudflareCacheDurationInSeconds:GreaterThan' => 0, 'NeverCachePublicly' => false]);
    }
}
