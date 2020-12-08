<?php

namespace Sunnysideup\SimpleTemplateCaching\Extensions;

use SilverStripe\ORM\DataExtension;

class AddLastEditedIndex extends DataExtension
{
    private static $indexes = [
        'LastEdited' => true,
    ];
}
