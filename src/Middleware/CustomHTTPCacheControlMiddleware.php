<?php

namespace Sunnysideup\SimpleTemplateCaching\Middleware;

use Override;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injectable;

class CustomHTTPCacheControlMiddleware extends HTTPCacheControlMiddleware
{
    use Injectable;

    /**
     * Cloudflare-CDN-Cache-Control max-age in seconds.
     * @var int
     */
    protected int $cloudflareCacheControlMaxAge = 0;

    /**
     * List of directives that, if present in the Cache-Control header, will prevent the CDN-specific Cache-Control header from being set.
     * @var string[]
     */
    protected $directivesExcludedFromCDNCache = [
        'no-cache',
        'no-store',
        'must-revalidate',
    ];

    /**
     * Set the Cloudflare-CDN-Cache-Control max-age in seconds.
     * @param int $seconds
     * @return CustomHTTPCacheControlMiddleware
     */
    public function setCloudflareCacheControlMaxAge(int $seconds): self
    {
        $this->cloudflareCacheControlMaxAge = $seconds;
        return $this;
    }

    #[Override]
    public function generateHeadersFor(HTTPResponse $response)
    {
        $headers = parent::generateHeadersFor($response);

        if ($this->cloudflareCacheControlMaxAge > 0 && $this->shouldSetCloudflareCacheControl()) {
            $headers['Cloudflare-CDN-Cache-Control'] = 'max-age=' . $this->cloudflareCacheControlMaxAge;
        }
        return $headers;
    }

    private function shouldSetCloudflareCacheControl(): bool
    {
        return !array_intersect($this->directivesExcludedFromCDNCache, $this->getDirectives());
    }
}
