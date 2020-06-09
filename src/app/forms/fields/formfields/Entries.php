<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\fields\formfields;

use barrelstrength\sproutbase\app\forms\base\FormFieldTrait;
use barrelstrength\sproutbase\app\forms\elements\Entry;
use barrelstrength\sproutbase\SproutBase;
use Craft;
use craft\fields\Entries as CraftEntries;
use craft\helpers\Template as TemplateHelper;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Exception;

/**
 * @property string $svgIconPath
 * @property array  $compatibleCraftFields
 * @property array  $compatibleCraftFieldTypes
 * @property mixed  $exampleInputHtml
 */
class Entries extends CraftEntries
{
    use FormFieldTrait;

    /**
     * @var string
     */
    public $cssClasses;

    /**
     * @var string Template to use for settings rendering
     */
    protected $settingsTemplate = 'sprout/forms/_components/fields/formfields/elementfieldsettings';

    /**
     * @return string
     */
    public function getSvgIconPath(): string
    {
        return '@sproutbaseassets/icons/newspaper-o.svg';
    }

    /**
     * @inheritdoc
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout/forms/_components/fields/formfields/entries/example',
            [
                'field' => $this
            ]
        );
    }

    /**
     * @param mixed      $value
     * @param Entry      $entry
     * @param array|null $renderingOptions
     *
     * @return Markup
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getFrontEndInputHtml($value, Entry $entry, array $renderingOptions = null): Markup
    {
        $entries = SproutBase::$app->frontEndFields->getFrontEndEntries($this->getSettings());

        $rendered = Craft::$app->getView()->renderTemplate('entries/input',
            [
                'name' => $this->handle,
                'value' => $value->ids(),
                'field' => $this,
                'entry' => $entry,
                'renderingOptions' => $renderingOptions,
                'entries' => $entries,
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    /**
     * @inheritdoc
     */
    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftEntries::class
        ];
    }
}
