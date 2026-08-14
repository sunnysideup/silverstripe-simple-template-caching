<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\SiteConfig\SiteConfig;

class IndividuallyCachedPages extends BaseCachedReport
{
    public function title()
    {
        return 'Pages with individual caching times for browser cache';
    }

    protected function extraSourceRecords(SiteConfig $sc, $params = null): DataList
    {
        return Page::get()
            ->filter(['PublicCacheDurationInSeconds:GreaterThan' => 0, 'NeverCachePublicly' => false]);
    }
}
