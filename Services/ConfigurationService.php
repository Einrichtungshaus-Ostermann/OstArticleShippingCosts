<?php declare(strict_types=1);
/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Article Shipping Costs
 *
 * Article Shipping Costs calculator
 *
 * @package   OstArticleShippingCosts
 *
 * @author    Tim Windelschmidt <tim.windelschmidt@ostermann.de>
 * @copyright 2018 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstArticleShippingCosts\Services;

use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Shop\Shop;

class ConfigurationService implements ConfigurationServiceInterface
{
    /**
     * ...
     *
     * @var array
     */
    protected $configuration;



    /**
     * ...
     *
     * @param ModelManager $modelManager
     * @param ContextServiceInterface $contextService
     * @param CachedConfigReader $cachedConfigReader
     * @param string $pluginName
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function __construct(ModelManager $modelManager, ContextServiceInterface $contextService, CachedConfigReader $cachedConfigReader, $pluginName)
    {
        // set params
        $this->configuration = $cachedConfigReader->getByPluginName(
            $pluginName,
            $modelManager->find(
                Shop::class,
                $contextService->getShopContext()->getShop()->getId()
            )
        );
    }



    public function get($key = null)
    {
        // none given
        if ($key === null) {
            // return configuration
            return $this->configuration;
        }

        // return by key
        return $this->configuration[$key];
    }
}
