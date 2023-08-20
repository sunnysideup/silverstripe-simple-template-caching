<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\PageExtension
 *
 * @property PageExtension $owner
 * @property bool $PublicCacheDurationInSeconds
 */
class PageExtension extends DataExtension
{
    private static $db = [
        'PublicCacheDurationInSeconds' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Cache',
            NumericField::create(
                'PublicCacheDurationInSeconds',
                'Number of caching seconds for public users (0 = no caching)'
            )
                ->setDescription('Use with care. This should only be used on pages that should be the same for all users and that should be accessible publicly.'),
            'Content'
        );
    }
}
