<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Override;
use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\SiteConfig\SiteConfig;

class CloudflareCachedPages extends BaseCachedReport
{
    #[Override]
    public function title()
    {
        return 'Pages with caching time for Cloudflare cache';
    }

    #[Override]
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
