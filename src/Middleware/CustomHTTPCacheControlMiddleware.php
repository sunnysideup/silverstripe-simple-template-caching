<?php

namespace Sunnysideup\SimpleTemplateCaching\Middleware;

use Override;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\SecurityToken;

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

    /**
     * Last line of defence: a response that contains the security token (SecurityID) of the current session
     * is personal to that visitor and must NEVER be cached (not by the browser, not by a CDN, not by a proxy).
     *
     * This does not matter how the token got into the response (a form, a link, JSON, an echo in a controller...)
     * and it does not depend on every piece of code remembering to disable caching.
     * It also beats caching that has been forced on a page (see the controller extension of this module),
     * because disableCache(true) is forced as well and has the highest priority.
     */
    #[Override]
    protected function augmentState(HTTPRequest $request, HTTPResponse $response)
    {
        parent::augmentState($request, $response);

        if ($this->responseContainsSecurityToken($request, $response)) {
            $this->disableCache(true);
        }
    }

    protected function responseContainsSecurityToken(HTTPRequest $request, HTTPResponse $response): bool
    {
        try {
            // NB: we only look at a token that already exists. We never create one here (creating a token starts a session).
            $token = $request->getSession()->get(SecurityToken::inst()->getName());
        } catch (\Throwable) {
            return false;
        }

        if (! is_string($token) || '' === $token) {
            return false;
        }

        // only look in things that can contain a token (not in images, downloads, etc...)
        $contentType = strtolower((string) $response->getHeader('Content-Type'));
        if ('' !== $contentType && ! preg_match('#text/|json|javascript|xml#', $contentType)) {
            return false;
        }

        $body = $response->getBody();

        return is_string($body) && str_contains($body, $token);
    }

    private function shouldSetCloudflareCacheControl(): bool
    {
        return !array_intersect($this->directivesExcludedFromCDNCache, $this->getDirectives());
    }
}
