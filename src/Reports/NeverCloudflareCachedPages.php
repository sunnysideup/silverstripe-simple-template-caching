<?php

namespace Sunnysideup\SimpleTemplateCaching\Reports;

use Override;
use Page;
use SilverStripe\ORM\DataList;
use SilverStripe\SiteConfig\SiteConfig;

class NeverCloudflareCachedPages extends BaseCachedReport
{
    #[Override]
    public function title()
    {
        return 'Pages that are never cached by Cloudflare';
    }

    #[Override]
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
