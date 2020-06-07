<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\config\models\settings;

use barrelstrength\sproutbase\config\base\Settings;
use Craft;

/**
 *
 * @property array $settingsNavItem
 */
class ReportsSettings extends Settings
{
    /**
     * @var string
     */
    public $displayName = '';

    /**
     * @var string
     */
    public $defaultPageLength = 10;

    /**
     * @var string
     */
    public $defaultExportDelimiter = ',';

    public function getSettingsNavItem(): array
    {
        return [
            'label' => Craft::t('sprout', 'Reports'),
            'url' => 'sprout/settings/reports',
            'icon' => '@sproutbaseicons/plugins/reports/icon.svg',
            'subnav' => [
                'reports' => [
                    'label' => Craft::t('sprout', 'Reports'),
                    'url' => 'sprout/settings/reports',
                    'template' => 'sprout-base-reports/settings/general'
                ]
            ]
        ];
    }
}

