<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Article Shipping Costs
 *
 * Calculates the shipping costs for every article considerung optional assembly
 * surcharge, given shipping costs from the erp and the configuration for
 * free shipping.
 *
 * 1.0.0
 * - initial release
 *
 * 1.0.1
 * - fixed faulty configuration
 * - fixed plugin name
 * - changed console command to ost-article-shipping-costs:calculate
 *
 * 1.0.2
 * - added negative hwg-uwg configuration
 *
 * 1.0.3
 * - code beautify
 *
 * 1.1.0
 * - added trends and moebel-shop
 *
 * 1.1.1
 * - added differentiation for inhouse or online shop calculation
 *
 * 1.1.2
 * - fixed free shipping for hwg-uwg constellation
 *
 * 1.1.3
 * - changed article number as highest priority for trends truck articles
 * - changed lowest price as default for trends truck articles
 *
 * 1.1.4
 * - fixed free shipping articles with default shopware free-shipping flag
 * - fixed faulty string match for article numbers and names
 *
 * 1.1.5
 * - fixed check for free-shipping for invalid articles
 * - changed order of reading articles to be more predictive
 *
 * 1.1.6
 * - fixed check for free-shipping for invalid articles
 *
 * @package   OstArticleShippingCosts
 *
 * @author    Tim Windelschmidt <tim.windelschmidt@ostermann.de>
 * @copyright 2018 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstArticleShippingCosts;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OstArticleShippingCosts extends Plugin
{
    /**
     * ...
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        // set plugin parameters
        $container->setParameter('ost_article_shipping_costs.plugin_dir', $this->getPath() . '/');
        $container->setParameter('ost_article_shipping_costs.view_dir', $this->getPath() . '/Resources/views/');

        // call parent builder
        parent::build($container);
    }

    /**
     * Activate the plugin.
     *
     * @param Context\ActivateContext $context
     */
    public function activate(Context\ActivateContext $context)
    {
        // clear complete cache after we activated the plugin
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }

    /**
     * Install the plugin.
     *
     * @param Context\InstallContext $context
     *
     * @throws \Exception
     */
    public function install(Context\InstallContext $context)
    {
        // install the plugin
        $installer = new Setup\Install(
            $this,
            $context,
            $this->container->get('models'),
            $this->container->get('shopware_attribute.crud_service')
        );
        $installer->install();

        // update it to current version
        $updater = new Setup\Update(
            $this,
            $context
        );
        $updater->install();

        // call default installer
        parent::install($context);
    }

    /**
     * Update the plugin.
     *
     * @param Context\UpdateContext $context
     */
    public function update(Context\UpdateContext $context)
    {
        // update the plugin
        $updater = new Setup\Update(
            $this,
            $context
        );
        $updater->update($context->getCurrentVersion());

        // call default updater
        parent::update($context);
    }

    /**
     * Uninstall the plugin.
     *
     * @param Context\UninstallContext $context
     *
     * @throws \Exception
     */
    public function uninstall(Context\UninstallContext $context)
    {
        // uninstall the plugin
        $uninstaller = new Setup\Uninstall(
            $this,
            $context,
            $this->container->get('models'),
            $this->container->get('shopware_attribute.crud_service')
        );
        $uninstaller->uninstall();

        // clear complete cache
        $context->scheduleClearCache($context::CACHE_LIST_ALL);

        // call default uninstaller
        parent::uninstall($context);
    }
}
