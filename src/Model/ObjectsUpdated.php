<?php

namespace Sunnysideup\SimpleTemplateCaching\Model;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;

/**
 * A blog category for generalising blog posts.
 *
 * @property string $ClassNameLastEdited
 */
class ObjectsUpdated extends DataObject
{
    /**
     * {@inheritDoc}
     *
     * @var string
     */
    private static $table_name = 'ObjectsUpdated';

    /**
     * @var array
     */
    private static $db = [
        'ClassNameLastEdited' => 'Varchar(255)',
    ];

    private static $default_sort = [
        'ID' => 'DESC',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Created' => 'Updated',
        'ClassNameTitle' => 'Record name    ',
    ];

    /**
     * @var array
     */
    private static $field_labels = [
        'Created' => 'Updated',
        'Title' => 'Human readable name',
        'ClassNameLastEdited' => 'Code name',
    ];
    /**
     * @var array
     */
    private static $casting = [
        'Title' => 'Varchar',
    ];

    public function getitle(): string
    {
        if (class_exists($this->getOwner()->ClassNameLastEdited)) {
            return Injector::inst()->get($this->getOwner()->ClassNameLastEdited)->i18n_singular_name();
        }

        return 'ERROR: class not found';
    }
}
