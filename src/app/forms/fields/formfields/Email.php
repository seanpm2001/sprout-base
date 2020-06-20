<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\fields\formfields;

use barrelstrength\sproutbase\app\forms\base\FormField;
use barrelstrength\sproutbase\app\forms\elements\Entry;
use barrelstrength\sproutbase\app\forms\rules\conditions\ContainsCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\DoesNotContainCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\DoesNotEndWithCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\DoesNotStartWithCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\EndsWithCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\IsCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\IsNotCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\StartsWithCondition;
use barrelstrength\sproutbase\SproutBase;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\Email as CraftEmail;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Template as TemplateHelper;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Exception;

/**
 *
 * @property array $elementValidationRules
 * @property string $svgIconPath
 * @property mixed $settingsHtml
 * @property array $compatibleCraftFields
 * @property array $compatibleCraftFieldTypes
 * @property array $compatibleConditions
 * @property mixed $exampleInputHtml
 */
class Email extends FormField implements PreviewableFieldInterface
{
    /**
     * @var string|null
     */
    public $customPattern;

    /**
     * @var bool
     */
    public $customPatternToggle;

    /**
     * @var string|null
     */
    public $customPatternErrorMessage;

    /**
     * @var bool
     */
    public $uniqueEmail;

    /**
     * @var string
     */
    public $cssClasses;

    /**
     * @var string|null
     */
    public $placeholder;

    public static function displayName(): string
    {
        return Craft::t('sprout', 'Email');
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout/forms/_components/fields/formfields/email/example',
            [
                'field' => $this,
            ]
        );
    }

    /**
     * @return string
     */
    public function getSvgIconPath(): string
    {
        return '@sproutbaseassets/icons/envelope.svg';
    }

    /**
     * @return string|null
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getSettingsHtml()
    {
        return SproutBase::$app->emailField->getSettingsHtml($this);
    }

    /**
     * @param mixed $value
     * @param ElementInterface|null $element
     *
     * @return string
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return SproutBase::$app->emailField->getInputHtml($this, $value, $element);
    }

    /**
     * @param mixed $value
     * @param Entry $entry
     * @param array|null $renderingOptions
     *
     * @return Markup
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getFrontEndInputHtml($value, Entry $entry, array $renderingOptions = null): Markup
    {
        $errorMessage = SproutBase::$app->emailField->getErrorMessage($this);
        $placeholder = $this['placeholder'] ?? '';

        $rendered = Craft::$app->getView()->renderTemplate('email/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'entry' => $entry,
                'errorMessage' => $errorMessage,
                'renderingOptions' => $renderingOptions,
                'placeholder' => $placeholder,
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validateEmail';

        if ($this->uniqueEmail) {
            $rules[] = 'validateUniqueEmail';
        }

        return $rules;
    }

    /**
     * Validates our fields submitted value beyond the checks
     * that were assumed based on the content attribute.
     *
     * @param Element|ElementInterface $element
     *
     * @return void
     */
    public function validateEmail(ElementInterface $element)
    {
        $value = $element->getFieldValue($this->handle);
        $isValid = SproutBase::$app->emailField->validateEmail($value, $this);

        if (!$isValid) {
            $message = SproutBase::$app->emailField->getErrorMessage($this);
            $element->addError($this->handle, $message);
        }
    }

    /**
     * @param ElementInterface $element
     */
    public function validateUniqueEmail(ElementInterface $element)
    {
        $value = $element->getFieldValue($this->handle);
        $isValid = SproutBase::$app->emailField->validateUniqueEmail($value, $this, $element);

        if (!$isValid) {
            $message = Craft::t('sprout', $this->name.' must be a unique email.');
            $element->addError($this->handle, $message);
        }
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftEmail::class,
            CraftDropdown::class,
        ];
    }

    public function getCompatibleConditions()
    {
        return [
            new IsCondition(),
            new IsNotCondition(),
            new ContainsCondition(),
            new DoesNotContainCondition(),
            new StartsWithCondition(),
            new DoesNotStartWithCondition(),
            new EndsWithCondition(),
            new DoesNotEndWithCondition(),
        ];
    }
}
