<?php

namespace Sunnysideup\SimpleTemplateCaching\Model;

use SilverStripe\ORM\DataObject;

use SilverStripe\Core\Injector\Injector;

/**
 * A blog category for generalising blog posts.
 *
*
 * @method Blog Blog()
 *
 * @property string $Title
 * @property string $URLSegment
 * @property int $BlogID
 */
class ObjectsUpdated extends DataObject
{

    /**
     * {@inheritDoc}
     * @var string
     */
    private static $table_name = 'ObjectsUpdated';

    /**
     * @var array
     */
    private static $db = [
        'ClassNameLastEdited'      => 'Varchar(255)',
    ];

    private static $default_sort = [
        'ID' => 'DESC',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Created'      => 'Updated',
        'ClassNameTitle' => 'Record name    ',
    ];

    /**
     * @var array
     */
    private static $field_labels = [
        'Created'      => 'Updated',
        'ClassNameLastEdited'      => 'Code name',
        'ClassNameTitle'      => 'Human readable name',
    ];
    /**
     * @var array
     */
    private static $casting = [
        'ClassNameTitle'      => 'Varchar',
    ];

    public function getClassNameTitle() : string
    {
        if(class_exists($this->getOwner()->ClassNameLastEdited)) {
            return Injector::inst()->get($this->getOwner()->ClassNameLastEdited)->i18n_singular_name();
        }
        return 'class not found';
    }

}
