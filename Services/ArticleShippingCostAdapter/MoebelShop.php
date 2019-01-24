<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Article Shipping Costs
 *
 * @package   OstArticleShippingCosts
 *
 * @author    Tim Windelschmidt <tim.windelschmidt@ostermann.de>
 * @copyright 2018 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstArticleShippingCosts\Services\ArticleShippingCostAdapter;

use Shopware\Models\Article\Detail;

class MoebelShop extends Adapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $configuration = [
        'attributeShippingCosts'    => null,
        'attributeIwmHwg'           => null,
        'attributeIwmUwg'           => null,
        'attributeIwmShippingCosts' => null,
        'attributeIwmFullService'   => null,
        'attributeIwmShippingType'  => null
    ];

    /**
     * ...
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        // set default configuration
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingCosts(Detail $articleDetails, array $attributes): float
    {
        // always remove iwm shipping costs and calculate it with trends logic
        $attributes[$this->configuration['attributeIwmShippingCosts']] = 0.0;

        // get the trends adapter
        /* @var $adapter Trends */
        $adapter = Shopware()->Container()->get('ost_article_shipping_costs.services.article_shipping_cost_calculator.adapter.trends');

        // and calculate it
        return $adapter->getShippingCosts($articleDetails, $attributes);
    }
}
