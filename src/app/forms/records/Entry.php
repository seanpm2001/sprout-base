<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\records;

use barrelstrength\sproutbase\app\forms\records\EntryStatus as EntryStatusRecord;
use barrelstrength\sproutbase\app\forms\records\Form as FormRecord;
use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 * Class Entry record.
 *
 * @property int $id
 * @property int $statusId
 * @property string $ipAddress
 * @property string $userAgent
 * @property ActiveQueryInterface $element
 * @property ActiveQueryInterface $entryStatuses
 * @property ActiveQueryInterface $form
 * @property string $formId
 */
class Entry extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%sprout_formentries}}';
    }

    /**
     * Returns the entry’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the form's.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getForm(): ActiveQueryInterface
    {
        return $this->hasMany(FormRecord::class, ['formId' => 'id']);
    }

    /**
     * Returns the Entry Statuses.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getEntryStatuses(): ActiveQueryInterface
    {
        return $this->hasMany(EntryStatusRecord::class, ['statusId' => 'id']);
    }

}