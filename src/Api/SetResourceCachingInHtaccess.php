<?php

namespace Sunnysideup\SimpleTemplateCaching\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;

class SetResourceCachingInHtaccess implements Flushable
{

    use Configurable;


    public static function flush()
    {
        if (Director::isDev() && Security::database_is_ready()) {
            Injector::inst()->get(self::class)
                ->updateHtaccess(true);
        }
    }

    /**
     * 864000 = ten days
     */
    private static string $image_cache_directive = '
# Uploaded assets (filenames may change less predictably) - cache lighter - one day
Header always set Cache-Control "public, max-age=86400" "expr=%{REQUEST_URI} =~ m#^/assets/.*\.(?:png|jpe?g|gif|webp|svg|avif)$#"';

    private static string $pdf_cache_directive = '
# PDFs/XML - don\'t cache
Header always set Cache-Control "no-store, no-cache, must-revalidate" "expr=%{REQUEST_URI} =~ m#^/(?:assets|_resources/themes)/.*\.(?:pdf|xml)$#"';

    private static string $css_and_js_cache_directive = '
# Theme-built assets (usually cache-busted) - cache hard
Header always set Cache-Control "public, max-age=31536000, immutable" "expr=%{REQUEST_URI} =~ m#^/_resources/themes/.*\.(?:css|js)$#"';

    private static string $font_cache_directive = '
# Font - just cache hard
Header always set Cache-Control "public, max-age=31536000, immutable" "expr=%{REQUEST_URI} =~ m#^/_resources/themes/.*\.(?:woff2?|ttf|otf|eot)$#"';


    public function updateHtaccess(?bool $verbose = false)
    {
        SiteConfig::current_site_config();
        foreach (
            [
                'IMAGE_CACHE_DIRECTIVE' => $this->config()->get('image_cache_directive'),
                'PDF_CACHE_DIRECTIVE' => $this->config()->get('pdf_cache_directive'),
                'CSS_JS_CACHE_DIRECTIVE' => $this->config()->get('css_and_js_cache_directive'),
                'FONT_CACHE_DIRECTIVE' => $this->config()->get('font_cache_directive'),
            ] as $key => $value
        ) {
            $this->updateHtaccessForOne($key, $value, $verbose);
        }
    }


    protected function updateHtaccessForOne(string $code, string $toAdd, ?bool $verbose = false)
    {
        $htaccessPath = Controller::join_links(Director::publicFolder(), '.htaccess');
        $htaccessContent = file_get_contents($htaccessPath);
        $originalContent = $htaccessContent;

        // Define start and end comments
        $startComment = PHP_EOL . "# auto add start " . $code;
        $endComment = PHP_EOL . "# auto add end " . $code . PHP_EOL . PHP_EOL;

        // Full content to replace or add
        $toAddFull = $startComment . $toAdd . $endComment;

        // Check if the section already exists
        $pattern = "/" . preg_quote($startComment, '/') . ".*?" . preg_quote($endComment, '/') . "/s";
        if (preg_match($pattern, $htaccessContent)) {
            // Replace existing content between the start and end comments
            $htaccessContent = preg_replace($pattern, $toAddFull, $htaccessContent);
        } else {
            // Prepend the new content if not found
            $htaccessContent = $toAddFull . $htaccessContent;
        }
        if ($originalContent !== $htaccessContent) {
            // Save the updated .htaccess file
            if ($verbose) {
                DB::alteration_message('Updating .htaccess file with ' . $code . ' cache directive', 'created');
            }
            if (!is_writable($htaccessPath) && $verbose) {
                DB::alteration_message('The .htaccess file is not writable: ' . $htaccessPath, 'deleted');
            }
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }
}
