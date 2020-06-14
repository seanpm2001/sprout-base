<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutbase\config\services;

use barrelstrength\sproutbase\config\base\Config as BaseConfig;
use barrelstrength\sproutbase\config\base\ConfigInterface;
use barrelstrength\sproutbase\config\base\SproutBasePlugin;
use barrelstrength\sproutbase\config\configs\ControlPanelConfig;
use barrelstrength\sproutbase\config\models\settings\ControlPanelSettings;
use barrelstrength\sproutbase\SproutBase;
use Craft;
use craft\base\Plugin;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\Plugins;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 *
 * @property \barrelstrength\sproutbase\config\base\SproutBasePlugin[] $sproutBasePlugins
 * @property \barrelstrength\sproutbase\config\models\settings\ControlPanelSettings $controlPanelSettings
 * @property array $sproutCpSettings
 */
class Config extends Component
{
    const EVENT_REGISTER_SPROUT_CONFIG = 'registerSproutConfig';

    /** The Project Config key where Sprout settings are stored */
    const CONFIG_SPROUT_KEY = Plugins::CONFIG_PLUGINS_KEY.'.sprout';

    /**
     * @var array
     */
    protected $_configs = [];

    /**
     * @var ControlPanelSettings
     */
    private $_controlPanelSettings;

    private $_configLoadStatus = 'not-loaded';

    private $_cpSettingsLoadStatus = 'not-loaded';

    public function getControlPanelSettings(): ControlPanelSettings
    {
        $this->initControlPanelSettings();

        return $this->_controlPanelSettings;
    }

    /**
     * @return SproutBasePlugin[]
     */
    public function getSproutBasePlugins(): array
    {
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        // All Sprout plugins with shared settings extend SproutBasePlugin
        $sproutCentralPlugins = array_filter($plugins, static function($plugin) {
            return $plugin instanceof SproutBasePlugin;
        });

        return $sproutCentralPlugins;
    }

    /**
     * @param bool $includeFileSettings
     *
     * @return array [
     *     'campaigns' => CampaignsConfig(),
     *     'control-panel' => ControlPanelConfig(),
     *     'email' => EmailConfig(),
     *     'fields' => FieldsConfig(),
     *     'forms' => FormsConfig(),
     *     'lists' => ListsConfig(),
     *     'metadata' => MetadataConfig(),
     *     'redirects' => RedirectsConfig(),
     *     'reports' => ReportsConfig(),
     *     'sent-email' => SentEmailConfig(),
     *     'sitemaps' => SitemapsConfig()
     * ]
     */
    public function getConfigs($includeFileSettings = true): array
    {
        $this->initConfigs($includeFileSettings);

        return $this->_configs;
    }

    /**
     * @param string $handle
     * @param bool $includeFileSettings
     *
     * @return BaseConfig
     */
    public function getConfigByKey(string $handle, $includeFileSettings = true): BaseConfig
    {
        $this->getConfigs($includeFileSettings);

        return $this->_configs[$handle];
    }

    /**
     * @param bool $includeFileSettings
     */
    private function initConfigs($includeFileSettings = true)
    {
        $this->prepareContext($includeFileSettings);

        $cpSettings = $this->getControlPanelSettings();

        if ($this->_configLoadStatus === 'loaded' || $this->_configLoadStatus === 'loading') {
            return;
        }

        $this->_configLoadStatus = 'loading';

        $plugins = $this->getSproutBasePlugins();

        foreach ($plugins as $plugin) {

            $isPluginEnabled = Craft::$app->getPlugins()->isPluginEnabled($plugin->handle);
            if (!$isPluginEnabled) {
                continue;
            }

            $configTypes = $plugin::getSproutConfigs();

            foreach ($configTypes as $configType) {
                /** @var BaseConfig $config */
                $config = new $configType();

                $config->setEdition();

                $moduleSettings = $cpSettings->modules[$config->getKey()] ?? null;

                $alternateName = !empty($moduleSettings['alternateName'])
                    ? $moduleSettings['alternateName']
                    : null;

                $config->setAlternateName($alternateName);

                if ($settings = SproutBase::$app->settings->getSettingsByConfig($config)) {
                    $config->setSettings($settings);
                }

                $this->_configs[$config->getKey()] = $config;
            }
        }

        $this->_configLoadStatus = 'loaded';
    }

    public function initControlPanelSettings()
    {
        if ($this->_controlPanelSettings ||
            $this->_cpSettingsLoadStatus === 'loaded' ||
            $this->_cpSettingsLoadStatus === 'loading') {
            return;
        }

        $this->_cpSettingsLoadStatus = 'loading';

        $cpConfig = new ControlPanelConfig();

        /** @var ControlPanelSettings $cpSettings */
        $cpSettings = SproutBase::$app->settings->mergeSettings($cpConfig);

        // Control Panel module has unique needs when returning settings
        $moduleSettings = $cpSettings->modules;

        // Update settings to be indexed by module key
        $moduleKeys = array_column($moduleSettings, 'moduleKey');
        $moduleSettings = array_combine($moduleKeys, $moduleSettings);

        $cpSettings->modules = $moduleSettings;

        $this->_controlPanelSettings = $cpSettings;

        $this->_cpSettingsLoadStatus = 'loaded';
    }


    /**
     * Returns true if the given dependency exists in any other plugin
     *
     * @param        $pluginHandle
     * @param string $configType
     *
     * @return bool
     */
    public function isDependencyInUse($pluginHandle, string $configType): bool
    {
        $dependenciesInUse = $this->getDependenciesInUse($pluginHandle);

        if (in_array($configType, $dependenciesInUse, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param SproutBasePlugin $plugin
     *
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function runInstallMigrations(SproutBasePlugin $plugin)
    {
        $sproutConfigTypes = $plugin->getSproutConfigs();

        foreach ($sproutConfigTypes as $sproutConfigType) {

            // Run the safeUp method if our module has an Install migration
            if ($migration = $sproutConfigType->createInstallMigration()) {
                ob_start();
                $migration->safeUp();
                ob_end_clean();
            }

            $this->addConfigSettingsToProjectConfig($sproutConfigType);
        }
    }

    /**
     * Runs all Install::safeDown() migrations for Sprout Central plugins
     *
     * @param SproutBasePlugin $plugin
     *
     */
    public function runUninstallMigrations(SproutBasePlugin $plugin)
    {
        $sproutConfigTypes = $plugin->getSproutConfigs();

        foreach ($sproutConfigTypes as $sproutConfigType) {
            $isDependencyInUse = SproutBase::$app->config->isDependencyInUse('sprout-seo', $sproutConfigType);

            if ($isDependencyInUse) {
                continue;
            }

            // Run the safeDown method if our module has an Install migration
            if ($migration = $sproutConfigType->createInstallMigration()) {
                ob_start();
                $migration->safeDown();
                ob_end_clean();
            }

            $this->removeConfigSettingsToProjectConfig($sproutConfigType);
        }
    }

    /**
     * @param ConfigInterface $config
     *
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function addConfigSettingsToProjectConfig(ConfigInterface $config)
    {
        if ($settings = $config->createSettingsModel()) {

            $settings->beforeAddDefaultSettings();

            $projectConfigSettingsKey = self::CONFIG_SPROUT_KEY.'.'.$config->getKey();
            $newSettings = ProjectConfigHelper::packAssociativeArrays($settings->toArray());

            Craft::$app->getProjectConfig()->set($projectConfigSettingsKey, $newSettings, "Added default Sprout Settings for “{$config->getKey()}”");
        }
    }

    /**
     * @param ConfigInterface $config
     */
    public function removeConfigSettingsToProjectConfig(ConfigInterface $config)
    {
        $projectConfigSettingsKey = self::CONFIG_SPROUT_KEY.'.'.$config->getKey();

        Craft::$app->getProjectConfig()->remove($projectConfigSettingsKey);
    }

    /**
     * @return array
     */
    public function getSproutCpSettings(): array
    {
        $configTypes = $this->getConfigs(false);

        $cpConfig = $configTypes['control-panel'];

        $settingsPages[] = [
            'label' => $cpConfig::displayName(),
            'url' => 'sprout/settings/'.$cpConfig->getKey(),
            'icon' => Craft::getAlias('@sproutbaseassets/sprout/icons/'.$cpConfig->getKey().'/icon.svg'),
        ];

        foreach ($configTypes as $configType) {

            $settings = $configType->getSettings();

            if (!$settings || !$settings->getIsEnabled()) {
                continue;
            }

            $navItem = $settings->getSettingsNavItem();

            if (count($navItem) === 0) {
                continue;
            }

            $label = $configType->getName();

            $settingsPages[] = [
                'label' => $label,
                'url' => 'sprout/settings/'.$configType->getKey(),
                'icon' => Craft::getAlias('@sproutbaseassets/sprout/icons/'.$configType->getKey().'/icon.svg'),
            ];
        }

        return $settingsPages;
    }

    public function buildSproutNavItems(): array
    {
        $configTypes = $this->getConfigs(false);

        $cpNavItems = [];
        foreach ($configTypes as $key => $config) {
            $navItem = $config->getCpNavItem();

            if (empty($navItem)) {
                continue;
            }

            $settings = SproutBase::$app->settings->getSettingsByKey($config->getKey());

            if (!$settings->getIsEnabled()) {
                continue;
            }

            $label = !empty($config->getName())
                ? $config->getName()
                : $navItem['label'];

            $cpNavItems[$key] = [
                'label' => $label,
                'url' => $navItem['url'],
                'icon' => Craft::getAlias('@sproutbaseassets/sprout/icons/'.$config->getKey().'/icon-mask.svg'),
            ];

            if (!isset($navItem['subnav']) || count($navItem['subnav']) === 0) {
                continue;
            }

            $cpNavItems[$key]['subnav'] = $navItem['subnav'];
        }

        return $cpNavItems;
    }

    /**
     * @param array $cpNavItems
     * @param array $sproutNavItems
     *
     * @return array
     */
    public function updateCpNavItems(array $cpNavItems, array $sproutNavItems): array
    {
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        // Filter out all plugins that don't extend SproutBasePlugin
        // and exclude the plugin calling this method
        $sproutPluginKeys = array_keys(array_filter($plugins, static function($plugin) {
            return $plugin instanceof SproutBasePlugin;
        }));

        $defaultSproutCpNavItems = array_filter($cpNavItems, static function($navItem) use ($sproutPluginKeys) {
            return in_array($navItem['url'], $sproutPluginKeys, true);
        });

        $firstPosition = null;
        foreach ($defaultSproutCpNavItems as $key => $defaultSproutCpNavItem) {
            if ($firstPosition === null) {
                $firstPosition = $key;
            }
            unset($cpNavItems[$key]);
        }

        foreach ($sproutNavItems as $sproutNavItem) {
            $cpNavItems[] = $sproutNavItem;
        }

        return $cpNavItems;
    }

    /**
     * Check if a plugin is a specific Edition
     *
     * @param $handle
     * @param $targetEdition
     *
     * @return bool
     */
    public function isEdition($handle, $targetEdition): bool
    {
        $config = $this->getConfigByKey($handle);

        if (!$config) {
            return false;
        }

        $currentEdition = $config->getEdition();

        return $currentEdition === $targetEdition;
    }

    public function isPluginEdition($pluginHandle, $edition): bool
    {
        /** @var Plugin $plugin */
        $plugin = Craft::$app->plugins->getPlugin($pluginHandle);

        return $plugin !== null ? $plugin->is($edition) : false;
    }

    public function getEdition($handle): string
    {
        $config = $this->getConfigByKey($handle);

        return $config->getEdition();
    }

    private function getDependenciesInUse($pluginHandle): array
    {
        $plugins = $this->getSproutBasePlugins();

        $configDependencies = [];
        foreach ($plugins as $key => $plugin) {
            // Exclude the plugin called in this method
            if ($plugin->getHandle() === $pluginHandle) {
                continue;
            }

            $configTypes = $plugin::getSproutConfigs();
            foreach ($configTypes as $configType) {
                $configDependencies[] = $configType;
            }
        }

        return array_unique($configDependencies);
    }

    /**
     * @param bool $includeFileSettings
     */
    private function prepareContext(bool $includeFileSettings)
    {
        $oldContext = SproutBase::$app->settings->context;

        if ($includeFileSettings === false) {
            $newContext = Settings::SETTINGS_CONTEXT_SETTINGS;
        } else {
            $newContext = Settings::SETTINGS_CONTEXT_APP;
        }

        if ($oldContext !== $newContext) {
            SproutBase::$app->settings->context = $newContext;

            // Rebuild the config array if a new context triggered
            $this->_configLoadStatus = 'not-loaded';
        }
    }
}
