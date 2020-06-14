<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\base;

use barrelstrength\sproutbase\app\forms\elements\Form;
use barrelstrength\sproutbase\app\forms\events\OnBeforeValidateEntryEvent;
use barrelstrength\sproutbase\SproutBase;
use craft\base\Model;

/**
 * Class Captcha
 *
 * @property null $settings
 * @property string $captchaSettingsHtml
 * @property string $name
 * @property string $description
 * @property string $captchaHtml
 */
abstract class Captcha extends Model
{
    /**
     * Add errors to a Captcha using the error key
     * to support spam error logging and reporting
     */
    const CAPTCHA_ERRORS_KEY = 'captchaErrors';

    /**
     * A unique ID that is generated dynamically using the plugin
     * handle and the captcha class name {pluginhandle}-{captchaclassname}
     *
     * @example
     * pluginname-captchaclassname
     *
     * @var string
     */
    public $captchaId;

    /**
     * The form where the captcha is being output
     *
     * @var Form
     */
    public $form;

    /**
     * The name of the captcha
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * A description of the captcha behavior
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Returns any values saved as settings for this captcha
     *
     * @return array|null
     */
    public function getSettings()
    {
        $settings = SproutBase::$app->settings->getSettingsByKey('forms');

        return $settings->captchaSettings[get_class($this)] ?? null;
    }

    /**
     * Returns html to display for your captcha settings.
     *
     * Sprout Forms will display all captcha settings on the Settings->Spam Prevention tab.
     * An option will be displayed to enable/disable each captcha. If your captcha's
     * settings are enabled, Sprout Forms will output getCaptchaSettingsHtml for users to
     * customize any additional settings your provide.
     *
     * @return string
     */
    public function getCaptchaSettingsHtml(): string
    {
        return '';
    }

    /**
     * Returns whatever is needed to get your captcha working in the front-end form template
     *
     * Sprout Forms will loop through all enabled Captcha integrations and output
     * getCaptchaHtml when the template hook `sproutForms.modifyForm` in form.html
     * is triggered.
     *
     * @return string
     */
    public function getCaptchaHtml(): string
    {
        return '';
    }

    /**
     * Returns if a form submission passes or fails your captcha validation.
     *
     * @param OnBeforeValidateEntryEvent $event
     *
     * @return bool
     */
    public function verifySubmission(OnBeforeValidateEntryEvent $event): bool
    {
        return true;
    }
}
