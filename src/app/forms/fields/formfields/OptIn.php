<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\fields\formfields;

use barrelstrength\sproutbase\app\forms\base\ConditionInterface;
use barrelstrength\sproutbase\app\forms\base\FormField;
use barrelstrength\sproutbase\app\forms\elements\Entry;
use barrelstrength\sproutbase\app\forms\rules\conditions\IsCheckedCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\IsNotCheckedCondition;
use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\fields\Checkboxes as CraftCheckboxes;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\Lightswitch as CraftLightswitch;
use craft\fields\PlainText as CraftPlainText;
use craft\fields\RadioButtons as CraftRadioButtons;
use craft\helpers\Template as TemplateHelper;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Exception;

/**
 * Class SproutFormsCheckboxesField
 *
 *
 * @property string $svgIconPath
 * @property mixed $settingsHtml
 * @property array $compatibleConditions
 * @property array $compatibleCraftFieldTypes
 * @property mixed $exampleInputHtml
 */
class OptIn extends FormField implements PreviewableFieldInterface
{
    /**
     * @var string
     */
    public $cssClasses;

    /**
     * @var string
     */
    public $optInMessage;

    /**
     * @var bool
     */
    public $selectedByDefault;

    /**
     * @var string
     */
    public $optInValueWhenTrue;

    /**
     * @var string
     */
    public $optInValueWhenFalse;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('sprout', 'Opt-in');
    }

    public function init()
    {
        if ($this->optInMessage === null) {
            $this->optInMessage = Craft::t('sprout', 'Agree to terms?');
        }

        if ($this->optInValueWhenTrue === null) {
            $this->optInValueWhenTrue = Craft::t('sprout', 'Yes');
        }

        if ($this->optInValueWhenFalse === null) {
            $this->optInValueWhenFalse = Craft::t('sprout', 'No');
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function displayLabel(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function displayInstructionsField(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getSvgIconPath(): string
    {
        return '@sproutbaseassets/icons/check-square.svg';
    }

    /**
     * @inheritdoc
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('sprout/forms/_components/fields/formfields/optin/settings',
            [
                'field' => $this,
            ]);
    }

    /**
     * @inheritdoc
     *
     * @param                       $value
     * @param ElementInterface|null $element
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Craft::$app->getView()->formatInputId($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        return Craft::$app->getView()->renderTemplate('sprout/fields/_components/fields/formfields/optin/input',
            [
                'name' => $this->handle,
                'namespaceInputId' => $namespaceInputId,
                'label' => $this->optInMessage,
                'value' => 1,
                'checked' => $value
            ]);
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
        return Craft::$app->getView()->renderTemplate('sprout/forms/_components/fields/formfields/optin/example',
            [
                'field' => $this
            ]
        );
    }

    /**
     * @inheritdoc
     *
     * @param            $value
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
        $rendered = Craft::$app->getView()->renderTemplate('optin/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'entry' => $entry,
                'renderingOptions' => $renderingOptions
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['optInMessage'], 'required'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getCompatibleConditions()
    {
        return [
            new IsCheckedCondition(),
            new IsNotCheckedCondition()
        ];
    }

    /**
     * @inheritdoc
     */
    public function getConditionValueInputHtml(ConditionInterface $condition, $fieldName, $fieldValue): string
    {
        $html = '<input class="text fullwidth" type="text" name="'.$fieldName.'" value="'.$fieldValue.'">';

        $emptyConditionClasses = [
            IsCheckedCondition::class,
            IsNotCheckedCondition::class
        ];

        foreach ($emptyConditionClasses as $selectCondition) {
            if ($condition instanceof $selectCondition) {
                $html = '<input class="text fullwidth" type="hidden" name="'.$fieldName.'" value="'.$fieldValue.'">';
            }
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftDropdown::class,
            CraftCheckboxes::class,
            CraftRadioButtons::class,
            CraftLightswitch::class
        ];
    }
}
