<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\SiteConfig\SiteConfig;

class NeverCloudflareCachedPages extends BaseCachedReport
{
    public function title()
    {
        return 'Pages that are never cached by Cloudflare';
    }

    protected function extraSourceRecords(SiteConfig $sc, $params = null): DataList
    {
        if ($sc->CloudflareCacheDurationInSeconds > 0) {
            return Page::get()->filter(['NeverCachePublicly' => true]);
        } else {
            return Page::get()
                ->filterAny(['CloudflareCacheDurationInSeconds' => 0, 'NeverCachePublicly' => true]);
        }
    }
}
