<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\SimpleTemplateCaching\Extensions\AddLastEditedIndex
 *
 * @property SiteTree|AddLastEditedIndex $owner
 */
class AddLastEditedIndex extends DataExtension
{
    private static $indexes = [
        'LastEdited' => true,
    ];
}
