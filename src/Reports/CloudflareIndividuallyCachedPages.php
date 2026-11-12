<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Override;
use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\SiteConfig\SiteConfig;

class CloudflareIndividuallyCachedPages extends BaseCachedReport
{
    #[Override]
    public function title()
    {
        return 'Pages with individual caching times for Cloudflare cache';
    }

    #[Override]
    protected function extraSourceRecords(SiteConfig $sc, $params = null): DataList
    {
        return Page::get()
            ->filter(['CloudflareCacheDurationInSeconds:GreaterThan' => 0, 'NeverCachePublicly' => false]);
    }
}
