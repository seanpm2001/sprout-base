<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\config\configs;

use barrelstrength\sproutbase\config\base\Config;
use barrelstrength\sproutbase\config\models\settings\SitemapsSettings;
use barrelstrength\sproutbase\migrations\sitemaps\Install;
use barrelstrength\sproutbase\SproutBase;
use Craft;

/**
 *
 * @property array $cpNavItem
 * @property array|string[] $cpUrlRules
 * @property \barrelstrength\sproutbase\config\configs\MetadataConfig $configGroup
 * @property string $description
 * @property array[]|array $userPermissions
 * @property array|string[] $siteUrlRules
 * @property string $key
 */
class SitemapsConfig extends Config
{
    public function getKey(): string
    {
        return 'sitemaps';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout', 'Sitemaps');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout', 'Manage XML sitemaps');
    }

    public function getConfigGroup()
    {
        return new MetadataConfig();
    }

    public function createSettingsModel()
    {
        return new SitemapsSettings();
    }

    public function createInstallMigration()
    {
        return new Install();
    }

    public function getCpNavItem(): array
    {
        return [
            'label' => Craft::t('sprout', 'Sitemaps'),
            'url' => 'sprout/sitemaps'
        ];
    }

    public function getUserPermissions(): array
    {
        return [
            'sprout:sitemaps:editSitemaps' => [
                'label' => Craft::t('sprout', 'Edit Sitemaps')
            ],
        ];
    }

    public function getCpUrlRules(): array
    {
        return [
            // Sitemaps
            'sprout/sitemaps/edit/<sitemapSectionId:\d+>/<siteHandle:[^\/]+>' =>
                'sprout/sitemaps/sitemap-edit-template',
            'sprout/sitemaps/new/<siteHandle:[^\/]+>' =>
                'sprout/sitemaps/sitemap-edit-template',
            'sprout/sitemaps/<siteHandle:[^\/]+>' =>
                'sprout/sitemaps/sitemap-index-template',
            'sprout/sitemaps' =>
                'sprout/sitemaps/sitemap-index-template'
        ];
    }

    /**
     * Match dynamic sitemap URLs
     *
     * Example matches include:
     *
     * Sitemap Index Page
     * - sitemap.xml
     *
     * URL-Enabled Sections
     * - sitemap-t6PLT5o43IFG-1.xml
     * - sitemap-t6PLT5o43IFG-2.xml
     *
     * Special Groupings
     * - sitemap-singles.xml
     * - sitemap-custom-pages.xml
     *
     * @return array
     */
    public function getSiteUrlRules(): array
    {
        $settings = SproutBase::$app->settings->getSettingsByKey('sitemaps');

        if ($this->getEdition() === Config::EDITION_PRO && $settings->getIsEnabled()) {

            return [
                'sitemap-<sitemapKey:.*>-<pageNumber:\d+>.xml' =>
                    'sprout/xml-sitemap/render-xml-sitemap',
                'sitemap-?<sitemapKey:.*>.xml' =>
                    'sprout/xml-sitemap/render-xml-sitemap',
            ];
        }

        return [];
    }

    public function setEdition()
    {
        $sproutSitemapsIsPro = SproutBase::$app->config->isPluginEdition('sprout-sitemaps', Config::EDITION_STANDARD);
        $sproutSeoIsPro = SproutBase::$app->config->isPluginEdition('sprout-seo', Config::EDITION_PRO);

        if ($sproutSeoIsPro || $sproutSitemapsIsPro) {
            $this->_edition = Config::EDITION_PRO;
        }
    }

    public function getControllerMapKeys(): array
    {
        return [
            'sitemaps',
            'xml-sitemaps'
        ];
    }
}

