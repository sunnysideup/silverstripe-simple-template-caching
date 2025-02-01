<?php

namespace Sunnysideup\SimpleTemplateCaching\Model;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * A blog category for generalising blog posts.
 *
 * @property string $ClassNameLastEdited
 */
class ObjectsUpdated extends DataObject
{

    public static function classes_edited(): string
    {
        $query = DB::query('
            SELECT "ClassNameLastEdited", COUNT(*) AS "Count"
            FROM "ObjectsUpdated"
            GROUP BY "ClassNameLastEdited"
            ORDER BY "ClassNameLastEdited" ASC
        ');
        foreach ($query as $row) {
            $array[$row['ClassNameLastEdited']] =
                Injector::inst()->get($row['ClassNameLastEdited'])->i18n_singular_name() .
                ' (' . $row['Count'] . ')';
        }
        return implode(', ', $array);
    }

    /**
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
        'Title' => 'Record name    ',
        'LastEdited' => 'Last Edited',
    ];

    /**
     * @var array
     */
    private static $field_labels = [
        'Created' => 'Updated',
        'Title' => 'Human readable name',
        'LastEdited' => 'Code name',
    ];

    /**
     * @var array
     */
    private static $casting = [
        'Title' => 'Varchar',
    ];

    public function getTitle(): string
    {
        if (class_exists((string) $this->ClassNameLastEdited)) {
            return Injector::inst()->get($this->ClassNameLastEdited)->i18n_singular_name();
        }

        return 'ERROR: class not found';
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('ClassNameLastEdited');
        $fields->addFieldsToTab(
            'Root.Main',
            [
                ReadonlyField::create('Title', 'Title', $this->getTitle()),
                ReadonlyField::create('Created', 'Recorded'),
            ]
        );
        return $fields;
    }
}
