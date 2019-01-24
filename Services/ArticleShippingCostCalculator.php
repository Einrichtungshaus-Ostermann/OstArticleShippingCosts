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

namespace OstArticleShippingCosts\Services;

use OstArticleShippingCosts\Services\ArticleShippingCostAdapter\AdapterInterface;
use Shopware\Models\Article\Detail;

class ArticleShippingCostCalculator implements ArticleShippingCostCalculatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getShippingCosts(Detail $articleDetails): float
    {
        // adapter to foundation configuration
        $adapter = [
            1  => 'ostermann',
            3  => 'trends',
            99 => 'moebel_shop'
        ];

        // get the current company context
        $company = Shopware()->Container()->get('ost_foundation.configuration')['company'];

        /* @var $adapter AdapterInterface */
        $adapter = Shopware()->Container()->get('ost_article_shipping_costs.services.article_shipping_cost_calculator.adapter.' . $adapter[$company]);

        // get the attributes
        $attributes = Shopware()->Models()->toArray($articleDetails->getAttribute());

        // return them
        return $adapter->getShippingCosts($articleDetails, $attributes);
    }
}
