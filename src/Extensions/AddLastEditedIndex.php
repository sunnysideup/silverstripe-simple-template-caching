<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\AddLastEditedIndex.
 *
 * @property AddLastEditedIndex|SiteTree $owner
 */
class AddLastEditedIndex extends DataExtension
{
    private static $indexes = [
        'LastEdited' => true,
    ];
}
